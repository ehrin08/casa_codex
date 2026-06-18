<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TherapistAvailabilityRequest extends FormRequest
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
            'therapist_profile_id' => ['required', 'integer', Rule::exists('therapist_profiles', 'id')],
            'availability_date' => [
                'nullable',
                'date',
                'required_without:day_of_week',
                Rule::prohibitedIf($this->filled('day_of_week')),
            ],
            'day_of_week' => [
                'nullable',
                'integer',
                'between:0,6',
                'required_without:availability_date',
                Rule::prohibitedIf($this->filled('availability_date')),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
