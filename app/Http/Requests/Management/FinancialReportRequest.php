<?php

namespace App\Http\Requests\Management;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(['today', 'this_week', 'custom'])],
            'date_from' => ['nullable', 'date', 'required_if:period,custom'],
            'date_to' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:date_from'],
        ];
    }

    /**
     * @return array{filters: array{period: string, date_from: string, date_to: string}, rangeLabel: string, dateFrom: CarbonImmutable, dateTo: CarbonImmutable}
     */
    public function reportPeriod(): array
    {
        $validated = $this->validated();
        $period = $validated['period'] ?? 'today';
        $today = CarbonImmutable::today();
        [$dateFrom, $dateTo] = match ($period) {
            'this_week' => [$today->startOfWeek(), $today->endOfWeek()],
            'custom' => [
                CarbonImmutable::parse($validated['date_from']),
                CarbonImmutable::parse($validated['date_to']),
            ],
            default => [$today, $today],
        };

        return [
            'filters' => [
                'period' => $period,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
            'rangeLabel' => $dateFrom->format('M j, Y').' to '.$dateTo->format('M j, Y'),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }
}
