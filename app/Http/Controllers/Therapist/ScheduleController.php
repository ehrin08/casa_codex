<?php

namespace App\Http\Controllers\Therapist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\TherapistProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $therapistProfile = $this->therapistProfile($request);
        $baseQuery = Appointment::query()
            ->where('therapist_profile_id', $therapistProfile->id)
            ->with('customerProfile');

        $todayAppointments = (clone $baseQuery)
            ->whereDate('appointment_date', today())
            ->orderBy('start_time')
            ->get();
        $upcomingAppointments = (clone $baseQuery)
            ->whereDate('appointment_date', '>', today())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate(15);

        return view('therapist.schedule', compact('todayAppointments', 'upcomingAppointments'));
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $therapistProfile = $this->therapistProfile($request);

        abort_unless($appointment->therapist_profile_id === $therapistProfile->id, 404);

        $appointment->load(['customerProfile', 'service']);

        return view('therapist.appointments.show', compact('appointment'));
    }

    private function therapistProfile(Request $request): TherapistProfile
    {
        $therapistProfile = $request->user()->therapistProfile;

        abort_unless($therapistProfile, 403, 'A linked therapist profile is required to view the schedule.');

        return $therapistProfile;
    }
}
