<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\MarkCommissionPaidRequest;
use App\Models\TherapistCommission;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TherapistCommissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'therapist_profile_id' => ['nullable', 'integer', Rule::exists('therapist_profiles', 'id')],
            'status' => ['nullable', Rule::in(TherapistCommission::STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $commissions = TherapistCommission::query()
            ->with(['therapistProfile', 'therapistUser', 'transaction', 'appointment.customerProfile'])
            ->when($filters['therapist_profile_id'] ?? null, fn ($query, $therapistId) => $query->where('therapist_profile_id', $therapistId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereDate('transaction_date', '>=', $date)))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereDate('transaction_date', '<=', $date)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $therapists = TherapistProfile::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('management.commissions.index', compact('commissions', 'filters', 'therapists'));
    }

    public function show(TherapistCommission $commission): View
    {
        $commission->load([
            'therapistProfile',
            'therapistUser',
            'transaction.cashier',
            'appointment.customerProfile',
            'appointment.service',
        ]);

        return view('management.commissions.show', compact('commission'));
    }

    public function markPaid(
        MarkCommissionPaidRequest $request,
        TherapistCommission $commission,
    ): RedirectResponse {
        DB::transaction(function () use ($commission): void {
            $commission = TherapistCommission::query()
                ->with('transaction')
                ->lockForUpdate()
                ->findOrFail($commission->id);

            if ($commission->status !== TherapistCommission::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending commissions can be marked as paid.',
                ]);
            }

            if ($commission->transaction?->payment_status !== Transaction::STATUS_PAID) {
                throw ValidationException::withMessages([
                    'status' => 'The related transaction must be paid before the commission can be settled.',
                ]);
            }

            $commission->update([
                'status' => TherapistCommission::STATUS_PAID,
                'paid_at' => now(),
            ]);
        });

        return redirect()
            ->route('management.commissions.show', $commission)
            ->with('success', 'Therapist commission marked as paid.');
    }
}
