<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TherapistCommission;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            $hasPaymentEligibility = $transaction->payment_method === Transaction::PAYMENT_METHOD_CASH
                && $transaction->payment_status === Transaction::STATUS_PAID
                && $appointment?->status === Appointment::STATUS_COMPLETED;

            if (! $hasPaymentEligibility) {
                return $this->voidPending($commission);
            }

            if (! $therapist) {
                Log::warning('Therapist commission was not created because the completed paid transaction has no assigned therapist.', [
                    'transaction_id' => $transaction->id,
                    'appointment_id' => $appointment?->id,
                ]);

                return $this->voidPending($commission);
            }

            if ($commission?->status === TherapistCommission::STATUS_PAID) {
                return $commission;
            }

            $therapistChanged = $commission !== null
                && $commission->therapist_profile_id !== $therapist->id;
            $rate = $commission === null || $therapistChanged
                ? $therapist->commission_rate
                : $commission->commission_rate;
            $baseAmount = $transaction->subtotal;
            $amount = $this->calculateAmount($baseAmount, $rate);

            if ((float) $rate <= 0 || (float) $rate > 100 || (float) $amount <= 0) {
                Log::notice('Therapist commission was not created because the configured rate or calculated amount is not payable.', [
                    'transaction_id' => $transaction->id,
                    'appointment_id' => $appointment->id,
                    'therapist_profile_id' => $therapist->id,
                    'commission_rate' => $rate,
                    'commission_amount' => $amount,
                ]);

                return $this->voidPending($commission);
            }

            if ($commission) {
                $commission->update([
                    'therapist_profile_id' => $therapist->id,
                    'therapist_user_id' => $therapistChanged
                        ? $therapist->user_id
                        : ($commission->therapist_user_id ?? $therapist->user_id),
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

    public function hasValidAmount(TherapistCommission $commission): bool
    {
        if ((float) $commission->commission_base_amount <= 0
            || (float) $commission->commission_rate <= 0
            || (float) $commission->commission_rate > 100
            || (float) $commission->commission_amount <= 0) {
            return false;
        }

        return $this->toCents($commission->commission_amount)
            === $this->toCents($this->calculateAmount(
                $commission->commission_base_amount,
                $commission->commission_rate,
            ));
    }

    private function voidPending(?TherapistCommission $commission): ?TherapistCommission
    {
        if ($commission?->status === TherapistCommission::STATUS_PENDING) {
            $commission->update([
                'status' => TherapistCommission::STATUS_VOID,
                'paid_at' => null,
            ]);
        }

        return $commission;
    }

    private function calculateAmount(mixed $baseAmount, mixed $rate): string
    {
        $baseCents = $this->toCents($baseAmount);
        $rateBasisPoints = (int) round(((float) $rate) * 100);
        $commissionCents = (int) round(($baseCents * $rateBasisPoints) / 10000);

        return number_format($commissionCents / 100, 2, '.', '');
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
