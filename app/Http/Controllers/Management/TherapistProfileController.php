<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\TherapistProfileRequest;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TherapistProfileController extends Controller
{
    public function index(): View
    {
        $therapists = TherapistProfile::with('user')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15);

        return view('management.therapists.index', compact('therapists'));
    }

    public function create(): View
    {
        return view('management.therapists.form', [
            'therapist' => new TherapistProfile,
            'users' => $this->availableUsers(),
        ]);
    }

    public function store(TherapistProfileRequest $request): RedirectResponse
    {
        TherapistProfile::create($request->validated());

        return redirect()
            ->route('management.therapists.index')
            ->with('success', 'Therapist profile created successfully.');
    }

    public function edit(TherapistProfile $therapist): View
    {
        return view('management.therapists.form', [
            'therapist' => $therapist,
            'users' => $this->availableUsers($therapist),
        ]);
    }

    public function update(TherapistProfileRequest $request, TherapistProfile $therapist): RedirectResponse
    {
        $therapist->update($request->validated());

        return redirect()
            ->route('management.therapists.index')
            ->with('success', 'Therapist profile updated successfully.');
    }

    public function toggleStatus(TherapistProfile $therapist): RedirectResponse
    {
        $therapist->update([
            'status' => $therapist->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Therapist status updated successfully.');
    }

    private function availableUsers(?TherapistProfile $therapist = null): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'therapist'))
            ->where(function ($query) use ($therapist) {
                $query->whereDoesntHave('therapistProfile');

                if ($therapist?->user_id) {
                    $query->orWhereKey($therapist->user_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
