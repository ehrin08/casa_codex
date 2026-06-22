<?php

namespace App\Http\Requests\Management;

use App\Services\AppointmentSlotFinder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWalkInAppointmentRequest extends FormRequest
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
            'customer_profile_id' => [
                'required',
                'integer',
                Rule::exists('customer_profiles', 'id')->where('is_active', true),
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
