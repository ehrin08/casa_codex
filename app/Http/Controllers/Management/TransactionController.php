<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreTransactionRequest;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Services\TransactionRecorder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(Request $request): View
    {
        $eligibleAppointments = $this->eligibleAppointments()
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        $selectedAppointment = null;

        if ($request->filled('appointment_id')) {
            $selectedAppointment = $eligibleAppointments
                ->firstWhere('id', $request->integer('appointment_id'));

            abort_if(! $selectedAppointment, 404);
        }

        return view('management.transactions.create', compact('eligibleAppointments', 'selectedAppointment'));
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
            'therapistCommission',
        ]);

        return view('management.transactions.show', compact('transaction'));
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
}
