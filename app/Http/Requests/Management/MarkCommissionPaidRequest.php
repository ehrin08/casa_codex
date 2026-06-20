<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class MarkCommissionPaidRequest extends FormRequest
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
        return [];
    }
}
