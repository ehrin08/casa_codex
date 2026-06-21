<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\TherapistProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class TherapistAssignmentRecommender
{
    /**
     * @return Collection<int, array{
     *     therapist: TherapistProfile,
     *     is_current: bool,
     *     is_available: bool,
     *     has_conflict: bool,
     *     workload_count: int,
     *     is_valid_option: bool,
     *     score: int,
     *     label: string,
     *     reason: string
     * }>
     */
    public function recommend(Appointment $appointment): Collection
    {
        [$start, $end] = $this->appointmentWindow($appointment);
        $date = $start->toDateString();

        $therapists = TherapistProfile::query()
            ->where('status', 'active')
            ->with([
                'availabilities' => function ($query) use ($start): void {
                    $query
                        ->where('status', 'active')
                        ->where(function ($query) use ($start): void {
                            $query
                                ->whereDate('availability_date', $start->toDateString())
                                ->orWhere(function ($query) use ($start): void {
                                    $query
                                        ->whereNull('availability_date')
                                        ->where('day_of_week', $start->dayOfWeek);
                                });
                        });
                },
                'appointments' => function ($query) use ($date): void {
                    $query
                        ->whereDate('appointment_date', $date)
                        ->whereIn('status', Appointment::CONFLICT_BLOCKING_STATUSES);
                },
            ])
            ->get();

        $recommendations = $therapists
            ->map(function (TherapistProfile $therapist) use ($appointment, $start, $end): array {
                $isCurrent = $therapist->id === $appointment->therapist_profile_id;
                $isAvailable = $end->isSameDay($start)
                    && $therapist->availabilities->contains(
                        fn ($availability): bool => CarbonImmutable::parse(
                            $start->toDateString().' '.$availability->start_time,
                        )->lessThanOrEqualTo($start)
                            && CarbonImmutable::parse(
                                $start->toDateString().' '.$availability->end_time,
                            )->greaterThanOrEqualTo($end),
                    );
                $hasConflict = $therapist->appointments->contains(
                    fn (Appointment $scheduled): bool => $scheduled->id !== $appointment->id
                        && CarbonImmutable::parse(
                            $start->toDateString().' '.$scheduled->start_time,
                        )->lessThan($end)
                        && CarbonImmutable::parse(
                            $start->toDateString().' '.$scheduled->end_time,
                        )->greaterThan($start),
                );
                $workloadCount = $therapist->appointments->count();
                $isValidOption = $isAvailable && ! $hasConflict;

                return [
                    'therapist' => $therapist,
                    'is_current' => $isCurrent,
                    'is_available' => $isAvailable,
                    'has_conflict' => $hasConflict,
                    'workload_count' => $workloadCount,
                    'is_valid_option' => $isValidOption,
                    'score' => $this->score($isAvailable, $hasConflict, $workloadCount),
                ];
            })
            ->sort(function (array $left, array $right): int {
                return [
                    ! $left['is_valid_option'],
                    ! $left['is_available'],
                    $left['has_conflict'],
                    $left['workload_count'],
                    strtolower($left['therapist']->first_name.' '.$left['therapist']->last_name),
                    $left['therapist']->id,
                ] <=> [
                    ! $right['is_valid_option'],
                    ! $right['is_available'],
                    $right['has_conflict'],
                    $right['workload_count'],
                    strtolower($right['therapist']->first_name.' '.$right['therapist']->last_name),
                    $right['therapist']->id,
                ];
            })
            ->values();

        return $recommendations->map(function (array $recommendation, int $index) use ($appointment): array {
            $isBestMatch = $index === 0 && $recommendation['is_valid_option'];

            return [
                ...$recommendation,
                'label' => $this->label($recommendation, $isBestMatch),
                'reason' => $this->reason($recommendation, $isBestMatch, $appointment),
            ];
        });
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function appointmentWindow(Appointment $appointment): array
    {
        $date = $appointment->appointment_date->toDateString();
        $start = CarbonImmutable::parse($date.' '.$appointment->start_time);
        $end = CarbonImmutable::parse($date.' '.$appointment->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    private function score(bool $isAvailable, bool $hasConflict, int $workloadCount): int
    {
        $score = ($isAvailable ? 50 : 0) + ($hasConflict ? 0 : 40);

        return max(0, $score - min($workloadCount, 40));
    }

    /**
     * @param  array<string, mixed>  $recommendation
     */
    private function label(array $recommendation, bool $isBestMatch): string
    {
        if ($isBestMatch) {
            return 'Best match';
        }

        if (! $recommendation['is_available']) {
            return 'Unavailable';
        }

        if ($recommendation['has_conflict']) {
            return 'Has conflict';
        }

        if ($recommendation['is_current']) {
            return 'Current therapist';
        }

        return 'Available';
    }

    /**
     * @param  array<string, mixed>  $recommendation
     */
    private function reason(array $recommendation, bool $isBestMatch, Appointment $appointment): string
    {
        $duration = $appointment->service_duration_minutes_snapshot;
        $window = $duration ? "the full {$duration}-minute appointment window" : 'the full appointment window';

        if ($isBestMatch) {
            return "Available for {$window} with the lowest same-day workload.";
        }

        if (! $recommendation['is_available'] && $recommendation['has_conflict']) {
            return "Availability does not cover {$window}, and an appointment overlaps this time.";
        }

        if (! $recommendation['is_available']) {
            return "Availability does not cover {$window}.";
        }

        if ($recommendation['has_conflict']) {
            return "Available for {$window}, but an appointment overlaps this time.";
        }

        if ($recommendation['is_current']) {
            return "Currently assigned and available for {$window} with no overlapping appointments.";
        }

        return "Available for {$window} with no overlapping appointments.";
    }
}
