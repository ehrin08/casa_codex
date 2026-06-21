<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Services\ManagementFinancialReportBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ManagementFinancialReportBuilder $reportBuilder): View
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'this_week', 'custom'])],
            'date_from' => ['nullable', 'date', 'required_if:period,custom'],
            'date_to' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:date_from'],
        ]);

        $period = $validated['period'] ?? 'today';
        [$dateFrom, $dateTo] = $this->dateRange($period, $validated);
        $filters = [
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
        ];
        $rangeLabel = $dateFrom->format('M j, Y').' to '.$dateTo->format('M j, Y');
        $report = $reportBuilder->build($dateFrom, $dateTo);

        return view('management.reports.index', compact('filters', 'rangeLabel', 'report'));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function dateRange(string $period, array $filters): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'this_week' => [$today->startOfWeek(), $today->endOfWeek()],
            'custom' => [
                CarbonImmutable::parse($filters['date_from']),
                CarbonImmutable::parse($filters['date_to']),
            ],
            default => [$today, $today],
        };
    }
}
