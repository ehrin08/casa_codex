<?php

namespace App\Http\Requests\Management;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
        $emailRules = ['nullable', 'email', 'max:255'];

        if ($this->boolean('create_account')) {
            array_unshift($emailRules, 'required');
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            'create_account' => [
                'sometimes',
                'boolean',
                Rule::prohibitedIf((bool) $therapist?->user_id),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::prohibitedIf($this->boolean('create_account')),
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
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'hired_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'account_password' => [
                Rule::requiredIf($this->boolean('create_account')),
                'nullable',
                'confirmed',
                Password::defaults(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        return $this->safe()->except([
            'create_account',
            'account_password',
            'account_password_confirmation',
        ]);
    }
}
