<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\FinancialReportRequest;
use App\Services\ManagementFinancialReportBuilder;
use Illuminate\View\View;

class ReportPrintController extends Controller
{
    public function show(FinancialReportRequest $request, ManagementFinancialReportBuilder $reportBuilder): View
    {
        ['filters' => $filters, 'rangeLabel' => $rangeLabel, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo] = $request->reportPeriod();
        $report = $reportBuilder->build($dateFrom, $dateTo);

        return view('management.reports.print', compact('filters', 'rangeLabel', 'report'));
    }
}
