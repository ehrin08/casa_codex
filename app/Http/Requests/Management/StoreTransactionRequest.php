<?php

namespace App\Http\Requests\Management;

use App\Models\Appointment;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManagement() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'appointment_id' => [
                'required',
                'integer',
                Rule::exists('appointments', 'id'),
                Rule::unique('transactions', 'appointment_id'),
            ],
            'promotion_id' => ['nullable', 'integer', Rule::exists('promotions', 'id')],
            'discount_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'payment_status' => ['required', Rule::in(Transaction::PAYMENT_STATUSES)],
            'amount_tendered' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
                'required_if:payment_status,'.Transaction::STATUS_PAID,
                'prohibited_unless:payment_status,'.Transaction::STATUS_PAID,
            ],
            'transaction_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('appointment_id')) {
                    return;
                }

                $appointment = Appointment::query()
                    ->with('service')
                    ->find($this->integer('appointment_id'));

                if (! $appointment) {
                    return;
                }

                if ($appointment->status !== Appointment::STATUS_COMPLETED) {
                    $validator->errors()->add(
                        'appointment_id',
                        'Only completed appointments can have a cash transaction.',
                    );

                    return;
                }

                $subtotal = $appointment->service_price_snapshot ?? $appointment->service?->price;

                if ($subtotal === null) {
                    $validator->errors()->add(
                        'appointment_id',
                        'The appointment does not have a service amount that can be recorded.',
                    );

                    return;
                }

                $subtotalCents = $this->toCents($subtotal);

                if ($this->filled('promotion_id')) {
                    return;
                }

                $discountCents = $this->toCents($this->input('discount_amount', 0));

                if ($discountCents > $subtotalCents) {
                    $validator->errors()->add(
                        'discount_amount',
                        'The discount amount cannot exceed the appointment subtotal.',
                    );
                }

                if ($this->input('payment_status') === Transaction::STATUS_PAID
                    && is_numeric($this->input('amount_tendered'))
                    && $this->toCents($this->input('amount_tendered')) < ($subtotalCents - $discountCents)) {
                    $validator->errors()->add(
                        'amount_tendered',
                        'The cash tendered must cover the total amount.',
                    );
                }
            },
        ];
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
