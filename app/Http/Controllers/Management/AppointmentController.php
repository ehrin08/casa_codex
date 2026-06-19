<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\AppointmentStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::query()
            ->with(['customerProfile', 'therapistProfile'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        return view('management.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment): View
    {
        $appointment->load([
            'customerProfile',
            'therapistProfile',
            'service',
            'statusHistories' => fn ($query) => $query
                ->with('changedBy')
                ->orderByDesc('changed_at'),
        ]);

        return view('management.appointments.show', compact('appointment'));
    }

    public function updateStatus(
        UpdateAppointmentStatusRequest $request,
        Appointment $appointment,
    ): RedirectResponse {
        $validated = $request->validated();

        $statusChanged = DB::transaction(function () use ($request, $appointment, $validated): bool {
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
