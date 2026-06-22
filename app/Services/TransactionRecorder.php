<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionRecorder
{
    public function __construct(
        private readonly PromotionEngine $promotionEngine,
    ) {}

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
            $transactionDate = CarbonImmutable::parse($data['transaction_date']);
            $promotion = null;

            if (($data['promotion_id'] ?? null) !== null) {
                $promotion = Promotion::query()
                    ->lockForUpdate()
                    ->find($data['promotion_id']);

                if (! $promotion) {
                    throw ValidationException::withMessages([
                        'promotion_id' => 'The selected promotion no longer exists.',
                    ]);
                }

                $evaluation = $this->promotionEngine->evaluate(
                    $promotion,
                    $appointment,
                    $transactionDate,
                );

                if (! $evaluation['eligible']) {
                    throw ValidationException::withMessages([
                        'promotion_id' => 'The selected promotion is not eligible: '.$evaluation['reason'],
                    ]);
                }

                $discountCents = $evaluation['discount_cents'];
            } else {
                $discountCents = $this->toCents($data['discount_amount'] ?? 0);
            }

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

            $transaction = Transaction::create([
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
                'transaction_date' => $transactionDate,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($promotion !== null) {
                PromotionUsage::create([
                    'promotion_id' => $promotion->id,
                    'transaction_id' => $transaction->id,
                    'customer_profile_id' => $transaction->customer_profile_id,
                    'discount_amount' => $transaction->discount_amount,
                    'used_at' => $transaction->transaction_date,
                ]);
            }

            return $transaction;
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
