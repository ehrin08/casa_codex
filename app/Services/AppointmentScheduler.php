<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentScheduler
{
    public function __construct(
        private readonly AppointmentNotificationService $notificationService,
    ) {}

    public function schedule(
        CustomerProfile $customerProfile,
        int $serviceId,
        int $therapistProfileId,
        string $appointmentDate,
        string $appointmentTime,
        ?string $notes = null,
    ): Appointment {
        return DB::transaction(function () use (
            $customerProfile,
            $serviceId,
            $therapistProfileId,
            $appointmentDate,
            $appointmentTime,
            $notes,
        ): Appointment {
            $service = Service::query()
                ->whereKey($serviceId)
                ->where('status', 'active')
                ->first();

            if (! $service) {
                throw ValidationException::withMessages([
                    'service_id' => 'The selected service is no longer active.',
                ]);
            }

            // Serializing bookings per therapist prevents concurrent requests from claiming the same slot.
            $therapist = TherapistProfile::query()
                ->whereKey($therapistProfileId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $therapist) {
                throw ValidationException::withMessages([
                    'therapist_profile_id' => 'The selected therapist is no longer active.',
                ]);
            }

            $start = CarbonImmutable::createFromFormat(
                '!Y-m-d H:i',
                $appointmentDate.' '.$appointmentTime,
            );
            $end = $start->addMinutes($service->duration_minutes);

            if (! $start->isFuture()) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'The selected appointment time must be in the future.',
                ]);
            }

            if (! $this->isWithinAvailability($therapist, $start, $end)) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'The selected therapist is not available for the full appointment time.',
                ]);
            }

            if ($this->hasConflict($therapist, $start, $end)) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'The selected therapist already has an appointment that overlaps this time.',
                ]);
            }

            $appointment = Appointment::create([
                'customer_profile_id' => $customerProfile->id,
                'therapist_profile_id' => $therapist->id,
                'service_id' => $service->id,
                'appointment_date' => $start->toDateString(),
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'status' => Appointment::STATUS_PENDING,
                'service_name_snapshot' => $service->name,
                'service_duration_minutes_snapshot' => $service->duration_minutes,
                'service_price_snapshot' => $service->price,
                'notes' => $notes,
            ]);

            $this->notificationService->appointmentBooked($appointment);

            return $appointment;
        });
    }

    private function isWithinAvailability(
        TherapistProfile $therapist,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): bool {
        if (! $end->isSameDay($start)) {
            return false;
        }

        return TherapistAvailability::query()
            ->where('therapist_profile_id', $therapist->id)
            ->where('status', 'active')
            ->where(function ($query) use ($start): void {
                $query
                    ->whereDate('availability_date', $start->toDateString())
                    ->orWhere(function ($query) use ($start): void {
                        $query
                            ->whereNull('availability_date')
                            ->where('day_of_week', $start->dayOfWeek);
                    });
            })
            ->where('start_time', '<=', $start->format('H:i:s'))
            ->where('end_time', '>=', $end->format('H:i:s'))
            ->exists();
    }

    private function hasConflict(
        TherapistProfile $therapist,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): bool {
        return Appointment::query()
            ->where('therapist_profile_id', $therapist->id)
            ->whereDate('appointment_date', $start->toDateString())
            ->whereIn('status', Appointment::CONFLICT_BLOCKING_STATUSES)
            ->where('start_time', '<', $end->format('H:i:s'))
            ->where('end_time', '>', $start->format('H:i:s'))
            ->exists();
    }
}
