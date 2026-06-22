<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StorePromotionRequest;
use App\Http\Requests\Management\UpdatePromotionRequest;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $status = in_array($status, Promotion::STATUSES, true) ? $status : null;

        $segment = $request->string('segment')->toString();
        $segment = in_array($segment, Promotion::RFM_SEGMENTS, true) ? $segment : null;

        $promotions = Promotion::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($segment, fn ($query) => $query->where('rfm_segment_label', $segment))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Promotion::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('management.promotions.index', [
            'promotions' => $promotions,
            'statuses' => Promotion::STATUSES,
            'segments' => Promotion::RFM_SEGMENTS,
            'selectedStatus' => $status,
            'selectedSegment' => $segment,
            'summary' => [
                'total' => $statusCounts->sum(),
                'active' => (int) $statusCounts->get(Promotion::STATUS_ACTIVE, 0),
                'draft' => (int) $statusCounts->get(Promotion::STATUS_DRAFT, 0),
                'inactive' => (int) $statusCounts->get(Promotion::STATUS_INACTIVE, 0),
            ],
        ]);
    }

    public function create(): View
    {
        return view('management.promotions.create', [
            'promotion' => new Promotion,
            'discountTypes' => Promotion::DISCOUNT_TYPES,
            'statuses' => Promotion::STATUSES,
            'segments' => Promotion::RFM_SEGMENTS,
        ]);
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        Promotion::create($request->validated());

        return redirect()
            ->route('management.promotions.index')
            ->with('success', 'Promotion rule created successfully.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('management.promotions.edit', [
            'promotion' => $promotion,
            'discountTypes' => Promotion::DISCOUNT_TYPES,
            'statuses' => Promotion::STATUSES,
            'segments' => Promotion::RFM_SEGMENTS,
        ]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated());

        return redirect()
            ->route('management.promotions.index')
            ->with('success', 'Promotion rule updated successfully.');
    }

    public function toggleStatus(Promotion $promotion): RedirectResponse
    {
        $promotion->update([
            'status' => $promotion->status === Promotion::STATUS_ACTIVE
                ? Promotion::STATUS_INACTIVE
                : Promotion::STATUS_ACTIVE,
        ]);

        return back()->with('success', 'Promotion rule status updated successfully.');
    }
}
