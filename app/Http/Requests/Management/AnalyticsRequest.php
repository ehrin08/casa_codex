<?php

namespace App\Http\Requests\Management;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'range' => ['nullable', Rule::in(['today', 'week', 'month', 'custom'])],
            'date_from' => ['nullable', 'date', 'required_if:range,custom'],
            'date_to' => ['nullable', 'date', 'required_if:range,custom', 'after_or_equal:date_from'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'therapist_profile_id' => ['nullable', 'integer', 'exists:therapist_profiles,id'],
        ];
    }

    /**
     * @return array{filters: array<string, int|string|null>, rangeLabel: string}
     */
    public function analyticsPeriod(): array
    {
        $validated = $this->validated();
        $range = $validated['range'] ?? 'month';
        $today = CarbonImmutable::today();

        [$dateFrom, $dateTo] = match ($range) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'custom' => [
                CarbonImmutable::parse($validated['date_from']),
                CarbonImmutable::parse($validated['date_to']),
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        return [
            'filters' => [
                'range' => $range,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'service_id' => isset($validated['service_id']) ? (int) $validated['service_id'] : null,
                'therapist_profile_id' => isset($validated['therapist_profile_id'])
                    ? (int) $validated['therapist_profile_id']
                    : null,
            ],
            'rangeLabel' => $dateFrom->format('M j, Y').' to '.$dateTo->format('M j, Y'),
        ];
    }
}
