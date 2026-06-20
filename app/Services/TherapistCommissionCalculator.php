<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TherapistCommission;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TherapistCommissionCalculator
{
    public function sync(Transaction $transaction): ?TherapistCommission
    {
        return DB::transaction(function () use ($transaction): ?TherapistCommission {
            $transaction = Transaction::query()
                ->with('appointment.therapistProfile')
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            $commission = TherapistCommission::query()
                ->where('transaction_id', $transaction->id)
                ->lockForUpdate()
                ->first();

            $appointment = $transaction->appointment;
            $therapist = $appointment?->therapistProfile;
            $qualifies = $transaction->payment_method === Transaction::PAYMENT_METHOD_CASH
                && $transaction->payment_status === Transaction::STATUS_PAID
                && $appointment?->status === Appointment::STATUS_COMPLETED
                && $therapist !== null;

            if (! $qualifies) {
                if ($commission?->status === TherapistCommission::STATUS_PENDING) {
                    $commission->update([
                        'status' => TherapistCommission::STATUS_VOID,
                        'paid_at' => null,
                    ]);
                }

                return $commission;
            }

            if ($commission?->status === TherapistCommission::STATUS_PAID) {
                return $commission;
            }

            $rate = $commission?->commission_rate ?? $therapist->commission_rate;
            $baseAmount = $transaction->subtotal;
            $amount = $this->calculateAmount($baseAmount, $rate);

            if ($commission) {
                $commission->update([
                    'therapist_profile_id' => $therapist->id,
                    'therapist_user_id' => $commission->therapist_user_id ?? $therapist->user_id,
                    'appointment_id' => $appointment->id,
                    'commission_base_amount' => $baseAmount,
                    'commission_amount' => $amount,
                    'status' => TherapistCommission::STATUS_PENDING,
                    'paid_at' => null,
                ]);

                return $commission->refresh();
            }

            return TherapistCommission::create([
                'therapist_profile_id' => $therapist->id,
                'therapist_user_id' => $therapist->user_id,
                'transaction_id' => $transaction->id,
                'appointment_id' => $appointment->id,
                'commission_rate' => $rate,
                'commission_base_amount' => $baseAmount,
                'commission_amount' => $amount,
                'status' => TherapistCommission::STATUS_PENDING,
            ]);
        });
    }

    private function calculateAmount(mixed $baseAmount, mixed $rate): string
    {
        $baseCents = (int) round(((float) $baseAmount) * 100);
        $rateBasisPoints = (int) round(((float) $rate) * 100);
        $commissionCents = (int) round(($baseCents * $rateBasisPoints) / 10000);

        return number_format($commissionCents / 100, 2, '.', '');
    }
}
