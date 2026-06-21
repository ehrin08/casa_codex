<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use Carbon\CarbonImmutable;

class AppointmentSlotFinder
{
    private const SLOT_INTERVAL_MINUTES = 30;

    /**
     * @return array<int, string>
     */
    public function availableSlots(
        int $serviceId,
        int $therapistProfileId,
        string $appointmentDate,
    ): array {
        $service = Service::query()
            ->whereKey($serviceId)
            ->where('status', 'active')
            ->first();
        $therapist = TherapistProfile::query()
            ->whereKey($therapistProfileId)
            ->where('status', 'active')
            ->first();

        if (! $service || ! $therapist) {
            return [];
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $appointmentDate);
        $availabilities = TherapistAvailability::query()
            ->where('therapist_profile_id', $therapist->id)
            ->where('status', 'active')
            ->where(function ($query) use ($date): void {
                $query
                    ->whereDate('availability_date', $date->toDateString())
                    ->orWhere(function ($query) use ($date): void {
                        $query
                            ->whereNull('availability_date')
                            ->where('day_of_week', $date->dayOfWeek);
                    });
            })
            ->get();
        $appointments = Appointment::query()
            ->where('therapist_profile_id', $therapist->id)
            ->whereDate('appointment_date', $date->toDateString())
            ->whereIn('status', Appointment::CONFLICT_BLOCKING_STATUSES)
            ->get(['start_time', 'end_time']);

        return $availabilities
            ->flatMap(function (TherapistAvailability $availability) use ($date, $service, $appointments): array {
                $cursor = CarbonImmutable::parse($date->toDateString().' '.$availability->start_time);
                $availabilityEnd = CarbonImmutable::parse($date->toDateString().' '.$availability->end_time);
                $slots = [];

                while ($cursor->addMinutes($service->duration_minutes)->lessThanOrEqualTo($availabilityEnd)) {
                    $slotEnd = $cursor->addMinutes($service->duration_minutes);
                    $hasConflict = $appointments->contains(function (Appointment $appointment) use ($date, $cursor, $slotEnd): bool {
                        $appointmentStart = CarbonImmutable::parse($date->toDateString().' '.$appointment->start_time);
                        $appointmentEnd = CarbonImmutable::parse($date->toDateString().' '.$appointment->end_time);

                        return $appointmentStart->lessThan($slotEnd)
                            && $appointmentEnd->greaterThan($cursor);
                    });

                    if (! $hasConflict && $cursor->isFuture()) {
                        $slots[] = $cursor->format('H:i');
                    }

                    $cursor = $cursor->addMinutes(self::SLOT_INTERVAL_MINUTES);
                }

                return $slots;
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
