<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\TherapistAvailabilityRequest;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TherapistAvailabilityController extends Controller
{
    public function index(): View
    {
        $availabilities = TherapistAvailability::with('therapistProfile')
            ->orderByDesc('availability_date')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(15);

        return view('management.availability.index', [
            'availabilities' => $availabilities,
            'therapists' => $this->therapists(),
        ]);
    }

    public function create(): View
    {
        return view('management.availability.form', [
            'availability' => new TherapistAvailability,
            'therapists' => $this->therapists(),
        ]);
    }

    public function store(TherapistAvailabilityRequest $request): RedirectResponse
    {
        TherapistAvailability::create($request->validated());

        return redirect()
            ->route('management.availability.index')
            ->with('success', 'Availability created successfully.');
    }

    public function edit(TherapistAvailability $availability): View
    {
        return view('management.availability.form', [
            'availability' => $availability,
            'therapists' => $this->therapists(),
        ]);
    }

    public function update(
        TherapistAvailabilityRequest $request,
        TherapistAvailability $availability
    ): RedirectResponse {
        $availability->update($request->validated());

        return redirect()
            ->route('management.availability.index')
            ->with('success', 'Availability updated successfully.');
    }

    public function toggleStatus(TherapistAvailability $availability): RedirectResponse
    {
        $availability->update([
            'status' => $availability->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Availability status updated successfully.');
    }

    private function therapists(): Collection
    {
        return TherapistProfile::orderBy('first_name')->orderBy('last_name')->get();
    }
}
