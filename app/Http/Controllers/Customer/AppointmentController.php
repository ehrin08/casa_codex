<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $customerProfile = $this->customerProfile($request);
        $baseQuery = Appointment::query()
            ->where('customer_profile_id', $customerProfile->id)
            ->with('therapistProfile');

        $upcomingAppointments = (clone $baseQuery)
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->paginate(10, ['*'], 'upcoming_page')
            ->withQueryString();
        $pastAppointments = (clone $baseQuery)
            ->whereDate('appointment_date', '<', today())
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(10, ['*'], 'past_page')
            ->withQueryString();

        return view('customer.appointments.index', compact('upcomingAppointments', 'pastAppointments'));
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $customerProfile = $this->customerProfile($request);

        abort_unless($appointment->customer_profile_id === $customerProfile->id, 404);

        $appointment->load(['service', 'therapistProfile', 'review']);

        return view('customer.appointments.show', compact('appointment'));
    }

    private function customerProfile(Request $request): CustomerProfile
    {
        $customerProfile = $request->user()->customerProfile;

        abort_unless($customerProfile, 403, 'A linked customer profile is required to view appointments.');

        return $customerProfile;
    }
}
