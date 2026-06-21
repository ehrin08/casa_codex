<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class MarkTransactionPaidRequest extends FormRequest
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
            'amount_tendered' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
            ],
        ];
    }
}
