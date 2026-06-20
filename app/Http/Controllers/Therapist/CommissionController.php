<?php

namespace App\Http\Controllers\Therapist;

use App\Http\Controllers\Controller;
use App\Models\TherapistCommission;
use App\Models\TherapistProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $therapistProfile = $this->therapistProfile($request);
        $commissions = TherapistCommission::query()
            ->where('therapist_profile_id', $therapistProfile->id)
            ->with(['transaction', 'appointment.customerProfile', 'appointment.service'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('therapist.commissions.index', compact('commissions'));
    }

    public function show(Request $request, TherapistCommission $commission): View
    {
        $therapistProfile = $this->therapistProfile($request);

        abort_unless($commission->therapist_profile_id === $therapistProfile->id, 404);

        $commission->load([
            'transaction',
            'appointment.customerProfile',
            'appointment.service',
        ]);

        return view('therapist.commissions.show', compact('commission'));
    }

    private function therapistProfile(Request $request): TherapistProfile
    {
        $therapistProfile = $request->user()->therapistProfile;

        abort_unless($therapistProfile, 403, 'A linked therapist profile is required to view commissions.');

        return $therapistProfile;
    }
}
