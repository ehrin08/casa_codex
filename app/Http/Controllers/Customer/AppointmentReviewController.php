<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreAppointmentReviewRequest;
use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerReview;
use App\Services\CustomerReviewSentimentClassifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentReviewController extends Controller
{
    public function create(Request $request, Appointment $appointment): View
    {
        $customerProfile = $this->customerProfile($request);

        abort_unless(
            $customerProfile->is_active
                && $appointment->customer_profile_id === $customerProfile->id
                && $appointment->status === Appointment::STATUS_COMPLETED
                && ! $appointment->review()->exists(),
            403,
        );

        $appointment->load(['service', 'therapistProfile']);

        return view('customer.appointments.review', compact('appointment'));
    }

    public function store(
        StoreAppointmentReviewRequest $request,
        Appointment $appointment,
        CustomerReviewSentimentClassifier $classifier,
    ): RedirectResponse {
        $validated = $request->validated();
        $customerProfile = $request->user()->customerProfile;

        CustomerReview::create([
            'customer_profile_id' => $customerProfile->id,
            'appointment_id' => $appointment->id,
            'service_id' => $appointment->service_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'sentiment_label' => $classifier->classify($validated['rating'], $validated['comment'] ?? null),
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('customer.appointments.show', $appointment)
            ->with('success', 'Thank you. Your review has been submitted.');
    }

    private function customerProfile(Request $request): CustomerProfile
    {
        $customerProfile = $request->user()->customerProfile;

        abort_unless($customerProfile, 403, 'A linked customer profile is required to review appointments.');

        return $customerProfile;
    }
}
