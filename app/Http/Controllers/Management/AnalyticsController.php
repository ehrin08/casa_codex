<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\AnalyticsRequest;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Services\ManagementAnalyticsBuilder;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(AnalyticsRequest $request, ManagementAnalyticsBuilder $analyticsBuilder): View
    {
        ['filters' => $filters, 'rangeLabel' => $rangeLabel] = $request->analyticsPeriod();

        $analytics = $analyticsBuilder->build($filters);
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $therapists = TherapistProfile::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('management.analytics.index', compact(
            'analytics',
            'filters',
            'rangeLabel',
            'services',
            'therapists',
        ));
    }
}
