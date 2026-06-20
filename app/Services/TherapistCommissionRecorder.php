<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TherapistCommission;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TherapistCommissionRecorder
{
    public function __construct(
        private readonly TherapistCommissionCalculator $calculator,
    ) {}

    public function recordFor(Transaction $transaction): ?TherapistCommission
    {
        return DB::transaction(function () use ($transaction): ?TherapistCommission {
            $lockedTransaction = Transaction::query()
                ->with('appointment.therapistProfile')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($lockedTransaction->payment_status !== Transaction::STATUS_PAID) {
                return null;
            }

            $appointment = $lockedTransaction->appointment;
            $therapist = $appointment?->therapistProfile;

            if (! $appointment
                || $appointment->status !== Appointment::STATUS_COMPLETED
                || ! $therapist) {
                return null;
            }

            $existingCommission = TherapistCommission::query()
                ->where('transaction_id', $lockedTransaction->id)
                ->first();

            if ($existingCommission) {
                return $existingCommission;
            }

            // Commission uses the pre-discount transaction subtotal so spa discounts do not reduce therapist pay.
            return TherapistCommission::create([
                'therapist_profile_id' => $therapist->id,
                'transaction_id' => $lockedTransaction->id,
                'appointment_id' => $appointment->id,
                'commission_rate' => $therapist->commission_rate,
                'commission_amount' => $this->calculator->calculate(
                    $lockedTransaction->subtotal,
                    $therapist->commission_rate,
                ),
                'status' => TherapistCommission::STATUS_PENDING,
            ]);
        });
    }

    public function updateStatus(
        TherapistCommission $commission,
        string $status,
        User $manager,
        ?string $notes = null,
    ): TherapistCommission {
        if (! $manager->isManagement()) {
            throw new AuthorizationException('Only management users can update commission status.');
        }

        return DB::transaction(function () use ($commission, $status, $notes): TherapistCommission {
            $lockedCommission = TherapistCommission::query()
                ->lockForUpdate()
                ->findOrFail($commission->id);

            if ($lockedCommission->status !== TherapistCommission::STATUS_PENDING
                || ! in_array($status, TherapistCommission::TERMINAL_STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only pending commissions can be marked as paid or void.',
                ]);
            }

            $lockedCommission->update([
                'status' => $status,
                'paid_at' => $status === TherapistCommission::STATUS_PAID ? now() : null,
                'notes' => $notes ?? $lockedCommission->notes,
            ]);

            return $lockedCommission->refresh();
        });
    }
}
