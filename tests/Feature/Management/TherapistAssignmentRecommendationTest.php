<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Services\TherapistAssignmentRecommender;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistAssignmentRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ranks_only_active_therapists_by_availability_conflicts_and_workload(): void
    {
        $date = '2026-07-20';
        $current = $this->createTherapist('Current');
        $best = $this->createTherapist('Best');
        $busy = $this->createTherapist('Busy');
        $unavailable = $this->createTherapist('Unavailable');
        $inactive = $this->createTherapist('Inactive', 'inactive');

        $this->createAvailability($current, $date, recurring: true);
        $this->createAvailability($best, $date);
        $this->createAvailability($busy, $date);
        $this->createAvailability($inactive, $date);

        $appointment = $this->createAppointment($current, $date, '10:00', '11:00');
        $this->createAppointment($busy, $date, '10:30', '11:30', Appointment::STATUS_CONFIRMED);
        $this->createAppointment($best, $date, '10:15', '10:45', Appointment::STATUS_CANCELLED);
        $this->createAppointment($best, $date, '10:30', '11:30', Appointment::STATUS_NO_SHOW);

        $recommendations = app(TherapistAssignmentRecommender::class)->recommend($appointment);

        $this->assertSame(
            [$best->id, $current->id, $busy->id, $unavailable->id],
            $recommendations->pluck('therapist.id')->all(),
        );
        $this->assertFalse($recommendations->contains(fn (array $item): bool => $item['therapist']->id === $inactive->id));

        $bestRecommendation = $recommendations->first();
        $this->assertSame('Best match', $bestRecommendation['label']);
        $this->assertTrue($bestRecommendation['is_available']);
        $this->assertFalse($bestRecommendation['has_conflict']);
        $this->assertSame(0, $bestRecommendation['workload_count']);

        $currentRecommendation = $recommendations->firstWhere('therapist.id', $current->id);
        $this->assertTrue($currentRecommendation['is_current']);
        $this->assertFalse($currentRecommendation['has_conflict']);
        $this->assertSame(1, $currentRecommendation['workload_count']);
        $this->assertSame('Current therapist', $currentRecommendation['label']);

        $busyRecommendation = $recommendations->firstWhere('therapist.id', $busy->id);
        $this->assertTrue($busyRecommendation['has_conflict']);
        $this->assertSame('Has conflict', $busyRecommendation['label']);

        $unavailableRecommendation = $recommendations->firstWhere('therapist.id', $unavailable->id);
        $this->assertFalse($unavailableRecommendation['is_available']);
        $this->assertSame('Unavailable', $unavailableRecommendation['label']);
    }

    public function test_availability_must_be_active_and_cover_the_full_window(): void
    {
        $date = '2026-07-20';
        $exact = $this->createTherapist('Exact');
        $weekly = $this->createTherapist('Weekly');
        $partial = $this->createTherapist('Partial');
        $inactiveAvailability = $this->createTherapist('Inactive Availability');

        $this->createAvailability($exact, $date, '10:00', '11:00');
        $this->createAvailability($weekly, $date, '09:00', '12:00', recurring: true);
        $this->createAvailability($partial, $date, '10:00', '10:59');
        $this->createAvailability($inactiveAvailability, $date, status: 'inactive');

        $appointment = $this->createAppointment($exact, $date, '10:00', '11:00');
        $recommendations = app(TherapistAssignmentRecommender::class)->recommend($appointment);

        $this->assertTrue($recommendations->firstWhere('therapist.id', $exact->id)['is_available']);
        $this->assertTrue($recommendations->firstWhere('therapist.id', $weekly->id)['is_available']);
        $this->assertFalse($recommendations->firstWhere('therapist.id', $partial->id)['is_available']);
        $this->assertFalse($recommendations->firstWhere('therapist.id', $inactiveAvailability->id)['is_available']);
    }

    public function test_adjacent_appointments_do_not_conflict_and_all_blocking_statuses_count_as_workload(): void
    {
        $date = '2026-07-20';
        $current = $this->createTherapist('Current');
        $adjacent = $this->createTherapist('Adjacent');
        $this->createAvailability($current, $date);
        $this->createAvailability($adjacent, $date);

        $appointment = $this->createAppointment($current, $date, '10:00', '11:00');
        $this->createAppointment($adjacent, $date, '09:00', '10:00', Appointment::STATUS_PENDING);
        $this->createAppointment($adjacent, $date, '11:00', '12:00', Appointment::STATUS_CONFIRMED);
        $this->createAppointment($adjacent, $date, '13:00', '14:00', Appointment::STATUS_COMPLETED);

        $recommendation = app(TherapistAssignmentRecommender::class)
            ->recommend($appointment)
            ->firstWhere('therapist.id', $adjacent->id);

        $this->assertFalse($recommendation['has_conflict']);
        $this->assertSame(3, $recommendation['workload_count']);
        $this->assertTrue($recommendation['is_valid_option']);
    }

    public function test_management_detail_page_displays_the_computed_recommendation_panel(): void
    {
        $managerRole = Role::firstOrCreate(['name' => 'management'], ['display_name' => 'Management']);
        $manager = User::factory()->create(['role_id' => $managerRole->id]);
        $therapist = $this->createTherapist('Panel');
        $date = '2026-07-20';
        $this->createAvailability($therapist, $date);
        $appointment = $this->createAppointment($therapist, $date, '10:00', '11:00');

        $this->actingAs($manager)
            ->get(route('management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Therapist recommendations')
            ->assertSee('Current therapist')
            ->assertSee('Panel Therapist')
            ->assertSee('Best match')
            ->assertSee('No conflict');
    }

    private function createTherapist(string $firstName, string $status = 'active'): TherapistProfile
    {
        return TherapistProfile::create([
            'first_name' => $firstName,
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => $status,
        ]);
    }

    private function createAvailability(
        TherapistProfile $therapist,
        string $date,
        string $start = '09:00',
        string $end = '17:00',
        string $status = 'active',
        bool $recurring = false,
    ): TherapistAvailability {
        return TherapistAvailability::create([
            'therapist_profile_id' => $therapist->id,
            'availability_date' => $recurring ? null : $date,
            'day_of_week' => $recurring ? Carbon::parse($date)->dayOfWeek : null,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    private function createAppointment(
        TherapistProfile $therapist,
        string $date,
        string $start,
        string $end,
        string $status = Appointment::STATUS_PENDING,
    ): Appointment {
        $customer = CustomerProfile::create([
            'first_name' => 'Recommendation',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $service = Service::firstOrCreate(
            ['name' => 'Recommendation Service'],
            ['duration_minutes' => 60, 'price' => 750, 'status' => 'active'],
        );

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => $service->price,
        ]);
    }
}
