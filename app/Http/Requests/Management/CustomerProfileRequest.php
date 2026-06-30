<?php

namespace App\Http\Requests\Management;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerProfileRequest extends FormRequest
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
        $customer = $this->route('customer');
        $customerRoleId = Role::where('name', 'customer')->value('id');
        $emailRules = ['nullable', 'email', 'max:255'];

        if ($this->boolean('create_account')) {
            array_unshift($emailRules, 'required');
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            'create_account' => [
                'sometimes',
                'boolean',
                Rule::prohibitedIf((bool) $customer?->user_id),
            ],
            'user_id' => [
                'nullable',
                'integer',
                Rule::prohibitedIf($this->boolean('create_account')),
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role_id', $customerRoleId)),
                Rule::unique('customer_profiles', 'user_id')->ignore($customer),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other', 'prefer_not_to_say'])],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
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
