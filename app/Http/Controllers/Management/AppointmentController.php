<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use App\Models\CustomerProfile;
use App\Models\TherapistProfile;
use App\Services\AppointmentNotificationService;
use App\Services\TherapistAssignmentRecommender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'appointment_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(Appointment::STATUSES)],
            'therapist_profile_id' => ['nullable', 'integer', Rule::exists('therapist_profiles', 'id')],
            'customer_profile_id' => ['nullable', 'integer', Rule::exists('customer_profiles', 'id')],
        ]);

        $appointments = Appointment::query()
            ->with(['customerProfile', 'therapistProfile'])
            ->when($filters['appointment_date'] ?? null, fn ($query, $date) => $query->whereDate('appointment_date', $date))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['therapist_profile_id'] ?? null, fn ($query, $therapistId) => $query->where('therapist_profile_id', $therapistId))
            ->when($filters['customer_profile_id'] ?? null, fn ($query, $customerId) => $query->where('customer_profile_id', $customerId))
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        $therapists = TherapistProfile::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $customers = CustomerProfile::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('management.appointments.index', compact('appointments', 'therapists', 'customers', 'filters'));
    }

    public function show(
        Appointment $appointment,
        TherapistAssignmentRecommender $recommender,
    ): View {
        $appointment->load([
            'customerProfile',
            'therapistProfile',
            'service',
            'transaction',
            'statusHistories' => fn ($query) => $query
                ->with('changedBy')
                ->orderByDesc('changed_at'),
        ]);

        $therapistRecommendations = $recommender->recommend($appointment);

        return view('management.appointments.show', compact('appointment', 'therapistRecommendations'));
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
        AppointmentNotificationService $notificationService,
    ): RedirectResponse {
        $validated = $request->validated();

        $statusChanged = DB::transaction(function () use ($request, $appointment, $validated, $notificationService): bool {
            $lockedAppointment = Appointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->id);

            if ($lockedAppointment->status === $validated['status']) {
                return false;
            }

            $previousStatus = $lockedAppointment->status;

            $lockedAppointment->update(['status' => $validated['status']]);

            AppointmentStatusHistory::create([
                'appointment_id' => $lockedAppointment->id,
                'changed_by_user_id' => $request->user()->id,
                'from_status' => $previousStatus,
                'to_status' => $validated['status'],
                'note' => $validated['status_notes'] ?? null,
                'changed_at' => now(),
            ]);

            $notificationService->appointmentStatusChanged($lockedAppointment, $previousStatus);

            return true;
        });

        if (! $statusChanged) {
            return back()->with('success', 'Appointment status is already up to date.');
        }

        return redirect()
            ->route('management.appointments.show', $appointment)
            ->with('success', 'Appointment status updated successfully.');
    }
}
