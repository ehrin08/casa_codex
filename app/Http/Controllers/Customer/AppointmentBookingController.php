<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAppointmentRequest;
use App\Models\CustomerProfile;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Services\AppointmentScheduler;
use App\Services\AppointmentSlotFinder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    public function create(Request $request): View
    {
        $this->activeCustomerProfile($request);

        return view('customer.book-appointment', [
            'services' => Service::query()
                ->with('category')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'therapists' => TherapistProfile::query()
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    public function store(
        StoreAppointmentRequest $request,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        $validated = $request->validated();
        $customerProfile = $this->activeCustomerProfile($request);
        $appointment = $scheduler->schedule(
            $customerProfile,
            $validated['service_id'],
            $validated['therapist_profile_id'],
            $validated['appointment_date'],
            $validated['appointment_time'],
            $validated['notes'] ?? null,
        );

        return redirect()
            ->route('customer.appointments.show', $appointment)
            ->with('success', 'Your appointment request has been submitted successfully.');
    }

    public function slots(Request $request, AppointmentSlotFinder $slotFinder): JsonResponse
    {
        $this->activeCustomerProfile($request);

        $validated = $request->validate([
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('status', 'active'),
            ],
            'therapist_profile_id' => [
                'required',
                'integer',
                Rule::exists('therapist_profiles', 'id')->where('status', 'active'),
            ],
            'appointment_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        return response()->json([
            'slots' => $slotFinder->availableSlots(
                $validated['service_id'],
                $validated['therapist_profile_id'],
                $validated['appointment_date'],
            ),
        ]);
    }

    private function activeCustomerProfile(Request $request): CustomerProfile
    {
        $customerProfile = $request->user()->customerProfile;

        abort_unless($customerProfile?->is_active, 403, 'An active customer profile is required to book an appointment.');

        return $customerProfile;
    }
}
