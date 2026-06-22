<?php

namespace App\Http\Requests\Management;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePromotionRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', Rule::in(Promotion::DISCOUNT_TYPES)],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
                Rule::when(
                    $this->input('discount_type') === Promotion::DISCOUNT_TYPE_PERCENTAGE,
                    ['max:100'],
                ),
            ],
            'rfm_segment_label' => ['nullable', Rule::in(Promotion::RFM_SEGMENTS)],
            'rule_min_recency_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rule_min_frequency_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rule_min_monetary_score' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rule_payload' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(Promotion::STATUSES)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->hasTargetingCondition()) {
                    return;
                }

                $validator->errors()->add(
                    'rfm_segment_label',
                    'Choose an RFM segment or set at least one minimum RFM score.',
                );
            },
        ];
    }

    private function hasTargetingCondition(): bool
    {
        return collect([
            $this->input('rfm_segment_label'),
            $this->input('rule_min_recency_score'),
            $this->input('rule_min_frequency_score'),
            $this->input('rule_min_monetary_score'),
        ])->contains(fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
