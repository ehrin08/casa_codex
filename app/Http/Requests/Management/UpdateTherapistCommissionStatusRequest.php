<?php

namespace App\Http\Requests\Management;

use App\Models\TherapistCommission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTherapistCommissionStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(TherapistCommission::TERMINAL_STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
