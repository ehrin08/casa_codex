<?php

namespace App\Http\Requests\Customer;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        $customerProfile = $this->user()?->customerProfile;

        return ($this->user()?->isCustomer() ?? false)
            && $customerProfile?->is_active
            && $appointment instanceof Appointment
            && $appointment->customer_profile_id === $customerProfile->id
            && $appointment->status === Appointment::STATUS_COMPLETED
            && ! $appointment->review()->exists();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
