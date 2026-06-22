<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CustomerRfmScore;
use App\Services\CustomerRfmScorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerRfmController extends Controller
{
    public function index(Request $request): View
    {
        $segment = $request->string('segment')->toString();
        $segment = in_array($segment, CustomerRfmScore::SEGMENTS, true) ? $segment : null;

        $scores = CustomerRfmScore::query()
            ->with('customerProfile.user')
            ->when($segment, fn ($query) => $query->where('segment_label', $segment))
            ->orderBy('segment_label')
            ->orderByDesc('calculated_at')
            ->paginate(20)
            ->withQueryString();

        $segmentCounts = CustomerRfmScore::query()
            ->selectRaw('segment_label, COUNT(*) as aggregate')
            ->groupBy('segment_label')
            ->pluck('aggregate', 'segment_label');

        $summary = [
            'total' => $segmentCounts->sum(),
            'champions' => (int) $segmentCounts->get(CustomerRfmScore::SEGMENT_CHAMPION, 0),
            'at_risk' => (int) $segmentCounts->get(CustomerRfmScore::SEGMENT_AT_RISK, 0),
            'new_low_activity' => (int) $segmentCounts->get(CustomerRfmScore::SEGMENT_NEW_LOW_ACTIVITY, 0),
        ];

        return view('management.rfm.index', [
            'scores' => $scores,
            'segments' => CustomerRfmScore::SEGMENTS,
            'selectedSegment' => $segment,
            'summary' => $summary,
        ]);
    }

    public function recalculate(CustomerRfmScorer $scorer): RedirectResponse
    {
        $summary = $scorer->recalculate();

        return redirect()
            ->route('management.rfm.index')
            ->with('success', sprintf(
                'RFM scores recalculated for %d customers (%d created, %d updated).',
                $summary['processed'],
                $summary['created'],
                $summary['updated'],
            ));
    }
}
