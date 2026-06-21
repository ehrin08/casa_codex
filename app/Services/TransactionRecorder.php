<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionRecorder
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function record(int $appointmentId, User $cashier, array $data): Transaction
    {
        if (! $cashier->isManagement()) {
            throw new AuthorizationException('Only management users can record transactions.');
        }

        return DB::transaction(function () use ($appointmentId, $cashier, $data): Transaction {
            // Locking the appointment serializes competing transaction submissions for the same booking.
            $appointment = Appointment::query()
                ->with('service')
                ->lockForUpdate()
                ->findOrFail($appointmentId);

            if ($appointment->status !== Appointment::STATUS_COMPLETED) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'Only completed appointments can have a cash transaction.',
                ]);
            }

            if (Transaction::query()->where('appointment_id', $appointment->id)->exists()) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'A transaction has already been recorded for this appointment.',
                ]);
            }

            $subtotal = $appointment->service_price_snapshot ?? $appointment->service?->price;

            if ($subtotal === null) {
                throw ValidationException::withMessages([
                    'appointment_id' => 'The appointment does not have a service amount that can be recorded.',
                ]);
            }

            $subtotalCents = $this->toCents($subtotal);
            $discountCents = $this->toCents($data['discount_amount'] ?? 0);

            if ($discountCents < 0 || $discountCents > $subtotalCents) {
                throw ValidationException::withMessages([
                    'discount_amount' => 'The discount amount must be between zero and the subtotal.',
                ]);
            }

            $totalCents = $subtotalCents - $discountCents;
            $isPaid = $data['payment_status'] === Transaction::STATUS_PAID;
            $amountTenderedCents = $isPaid ? $this->toCents($data['amount_tendered']) : null;

            if ($isPaid && $amountTenderedCents < $totalCents) {
                throw ValidationException::withMessages([
                    'amount_tendered' => 'The cash tendered must cover the total amount.',
                ]);
            }

            return Transaction::create([
                'appointment_id' => $appointment->id,
                'customer_profile_id' => $appointment->customer_profile_id,
                'cashier_user_id' => $cashier->id,
                'subtotal' => $this->fromCents($subtotalCents),
                'discount_amount' => $this->fromCents($discountCents),
                'total_amount' => $this->fromCents($totalCents),
                'amount_tendered' => $amountTenderedCents === null ? null : $this->fromCents($amountTenderedCents),
                'change_due' => $amountTenderedCents === null ? null : $this->fromCents($amountTenderedCents - $totalCents),
                'payment_method' => Transaction::PAYMENT_METHOD_CASH,
                'payment_status' => $data['payment_status'],
                'paid_by_user_id' => $isPaid ? $cashier->id : null,
                'paid_at' => $isPaid ? now() : null,
                'transaction_date' => $data['transaction_date'],
                'notes' => $data['notes'] ?? null,
            ]);
        });
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
