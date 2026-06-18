<?php

namespace App\Http\Requests\Management;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TherapistProfileRequest extends FormRequest
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
        $therapist = $this->route('therapist');
        $therapistRoleId = Role::where('name', 'therapist')->value('id');

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role_id', $therapistRoleId)),
                Rule::unique('therapist_profiles', 'user_id')->ignore($therapist),
            ],
            'employee_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('therapist_profiles', 'employee_code')->ignore($therapist),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'hired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
