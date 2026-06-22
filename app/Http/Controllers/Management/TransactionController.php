<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\MarkTransactionPaidRequest;
use App\Http\Requests\Management\StoreTransactionRequest;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Services\PromotionEngine;
use App\Services\TransactionRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(): View
    {
        $transactions = Transaction::query()
            ->with([
                'appointment.customerProfile',
                'appointment.therapistProfile',
                'appointment.service',
                'cashier',
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('management.transactions.index', compact('transactions'));
    }

    public function create(Request $request, PromotionEngine $promotionEngine): View
    {
        $eligibleAppointments = $this->eligibleAppointments()
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        $selectedAppointment = null;
        $promotionRecommendations = [];

        if ($request->filled('appointment_id')) {
            $selectedAppointment = $eligibleAppointments
                ->firstWhere('id', $request->integer('appointment_id'));

            abort_if(! $selectedAppointment, 404);

            $promotionRecommendations = $promotionEngine->recommendationsForAppointment($selectedAppointment);
        }

        return view('management.transactions.create', compact(
            'eligibleAppointments',
            'selectedAppointment',
            'promotionRecommendations',
        ));
    }

    public function store(
        StoreTransactionRequest $request,
        TransactionRecorder $recorder,
    ): RedirectResponse {
        $validated = $request->validated();
        $transaction = $recorder->record(
            (int) $validated['appointment_id'],
            $request->user(),
            $validated,
        );

        return redirect()
            ->route('management.transactions.show', $transaction)
            ->with('success', 'Cash transaction recorded successfully.');
    }

    public function show(Transaction $transaction): View
    {
        $transaction->load([
            'appointment.customerProfile',
            'appointment.therapistProfile',
            'appointment.service',
            'cashier',
            'paidBy',
            'therapistCommission',
            'promotionUsage.promotion',
        ]);

        return view('management.transactions.show', compact('transaction'));
    }

    public function markPaid(
        MarkTransactionPaidRequest $request,
        Transaction $transaction,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $transaction): void {
            $transaction = Transaction::query()
                ->with('appointment')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($transaction->payment_method !== Transaction::PAYMENT_METHOD_CASH) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Only cash transactions can be confirmed through this payment flow.',
                ]);
            }

            if ($transaction->payment_status !== Transaction::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'payment_status' => 'Only pending transactions can be marked as paid.',
                ]);
            }

            if (! $transaction->appointment
                || $transaction->appointment->status !== Appointment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'payment_status' => 'The linked appointment must still be completed before payment can be confirmed.',
                ]);
            }

            $amountTenderedCents = $this->toCents($request->validated('amount_tendered'));
            $totalCents = $this->toCents($transaction->total_amount);

            if ($amountTenderedCents < $totalCents) {
                throw ValidationException::withMessages([
                    'amount_tendered' => 'The cash tendered must cover the total amount.',
                ]);
            }

            $transaction->update([
                'payment_status' => Transaction::STATUS_PAID,
                'amount_tendered' => $this->fromCents($amountTenderedCents),
                'change_due' => $this->fromCents($amountTenderedCents - $totalCents),
                'paid_by_user_id' => $request->user()->id,
                'paid_at' => now(),
            ]);
        });

        return redirect()
            ->route('management.transactions.show', $transaction)
            ->with('success', 'Cash payment confirmed successfully.');
    }

    /**
     * @return Builder<Appointment>
     */
    private function eligibleAppointments(): Builder
    {
        return Appointment::query()
            ->with(['customerProfile', 'therapistProfile', 'service'])
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereDoesntHave('transaction');
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
