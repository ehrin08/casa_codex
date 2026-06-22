<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\StoreWalkInAppointmentRequest;
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

class WalkInAppointmentController extends Controller
{
    public function create(): View
    {
        return view('management.walk-ins.create', [
            'customers' => CustomerProfile::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),
            'services' => Service::query()
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

    public function slots(Request $request, AppointmentSlotFinder $slotFinder): JsonResponse
    {
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

    public function store(
        StoreWalkInAppointmentRequest $request,
        AppointmentScheduler $scheduler,
    ): RedirectResponse {
        $validated = $request->validated();
        $customer = CustomerProfile::query()
            ->whereKey($validated['customer_profile_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $context = 'Walk-in booking created by '.$request->user()->name.'.';
        $notes = filled($validated['notes'] ?? null)
            ? $context.PHP_EOL.$validated['notes']
            : $context;

        $appointment = $scheduler->schedule(
            $customer,
            $validated['service_id'],
            $validated['therapist_profile_id'],
            $validated['appointment_date'],
            $validated['appointment_time'],
            $notes,
        );

        return redirect()
            ->route('management.appointments.show', $appointment)
            ->with('success', 'Walk-in appointment was created successfully.');
    }
}
