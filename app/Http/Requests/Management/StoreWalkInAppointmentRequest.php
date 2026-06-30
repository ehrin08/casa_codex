<?php

namespace App\Http\Requests\Management;

use App\Services\AppointmentSlotFinder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWalkInAppointmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('customer_type') && $this->filled('customer_profile_id')) {
            $this->merge(['customer_type' => 'existing']);
        }
    }

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
            'customer_type' => ['required', Rule::in(['existing', 'guest'])],
            'customer_profile_id' => [
                'exclude_unless:customer_type,existing',
                'required',
                'integer',
                Rule::exists('customer_profiles', 'id')->where('is_active', true),
            ],
            'guest_name' => [
                'exclude_unless:customer_type,guest',
                'required',
                'string',
                'max:255',
            ],
            'guest_contact' => [
                'exclude_unless:customer_type,guest',
                'nullable',
                'string',
                'max:30',
            ],
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('status', 'active'),
            ],
            'therapist_profile_id' => [
                'required',
                'integer',
                Rule::exists('therapist_profiles', 'id')->where('status', 'active'),
            ],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $slots = app(AppointmentSlotFinder::class)->availableSlots(
                $this->integer('service_id'),
                $this->integer('therapist_profile_id'),
                $this->string('appointment_date')->toString(),
            );

            if (! in_array($this->string('appointment_time')->toString(), $slots, true)) {
                $validator->errors()->add(
                    'appointment_time',
                    'The selected appointment time is no longer available.',
                );
            }
        }];
    }
}
