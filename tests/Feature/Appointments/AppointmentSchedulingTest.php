<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_book_within_weekday_based_therapist_availability(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, recurring: true);

        $this->actingAs($context['user'])
            ->post(route('customer.appointments.store'), $this->payload($context, $date, '10:00'))
            ->assertSessionHasNoErrors();

        $appointment = Appointment::firstOrFail();
        $this->assertSame($context['therapist']->id, $appointment->therapist_profile_id);
        $this->assertSame($date, $appointment->appointment_date->toDateString());
        $this->assertSame('10:00:00', $appointment->start_time);
        $this->assertSame('11:00:00', $appointment->end_time);
    }

    public function test_customer_can_book_within_date_specific_therapist_availability(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($context['therapist'], $date, '12:00', '15:00');

        $this->actingAs($context['user'])
            ->post(route('customer.appointments.store'), $this->payload($context, $date, '13:00'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_customer_cannot_book_outside_therapist_availability(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, '10:00', '12:00');

        $this->actingAs($context['user'])
            ->post(route('customer.appointments.store'), $this->payload($context, $date, '11:30'))
            ->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_customer_cannot_book_using_inactive_availability(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, status: 'inactive');

        $this->actingAs($context['user'])
            ->post(route('customer.appointments.store'), $this->payload($context, $date, '10:00'))
            ->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_all_overlapping_time_range_shapes_are_rejected(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, '09:00', '13:00');
        $this->createAppointment($context, $date, '10:00', '11:00', Appointment::STATUS_PENDING);

        $overlapCases = [
            ['start' => '10:30', 'duration' => 30, 'name' => 'Starts Inside'],
            ['start' => '09:30', 'duration' => 45, 'name' => 'Ends Inside'],
            ['start' => '09:30', 'duration' => 120, 'name' => 'Fully Contains'],
            ['start' => '10:00', 'duration' => 60, 'name' => 'Exact Match'],
        ];

        foreach ($overlapCases as $case) {
            $service = $this->createService($case['name'], $case['duration']);
            $payload = $this->payload($context, $date, $case['start']);
            $payload['service_id'] = $service->id;

            $this->actingAs($context['user'])
                ->post(route('customer.appointments.store'), $payload)
                ->assertSessionHasErrors('appointment_time');
        }

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_adjacent_appointments_are_allowed(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, '09:00', '13:00');
        $this->createAppointment($context, $date, '10:00', '11:00', Appointment::STATUS_CONFIRMED);

        $this->actingAs($context['user'])
            ->post(route('customer.appointments.store'), $this->payload($context, $date, '11:00'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_cancelled_and_no_show_appointments_do_not_block_new_bookings(): void
    {
        $context = $this->bookingContext();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, '09:00', '13:00');
        $this->createAppointment($context, $date, '10:00', '11:00', Appointment::STATUS_CANCELLED);
        $this->createAppointment($context, $date, '11:00', '12:00', Appointment::STATUS_NO_SHOW);

        foreach (['10:00', '11:00'] as $startTime) {
            $this->actingAs($context['user'])
                ->post(route('customer.appointments.store'), $this->payload($context, $date, $startTime))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('appointments', 4);
    }

    public function test_pending_confirmed_and_completed_appointments_block_conflicts(): void
    {
        $context = $this->bookingContext(duration: 30);
        $date = now()->addDay()->toDateString();
        $this->createAvailability($context['therapist'], $date, '09:00', '14:00');

        $blockingAppointments = [
            [Appointment::STATUS_PENDING, '10:00', '11:00', '10:15'],
            [Appointment::STATUS_CONFIRMED, '11:00', '12:00', '11:15'],
            [Appointment::STATUS_COMPLETED, '12:00', '13:00', '12:15'],
        ];

        foreach ($blockingAppointments as [$status, $start, $end, $attempt]) {
            $this->createAppointment($context, $date, $start, $end, $status);

            $this->actingAs($context['user'])
                ->post(route('customer.appointments.store'), $this->payload($context, $date, $attempt))
                ->assertSessionHasErrors('appointment_time');
        }

        $this->assertDatabaseCount('appointments', 3);
    }

    /**
     * @return array{user: User, customer: CustomerProfile, service: Service, therapist: TherapistProfile}
     */
    private function bookingContext(int $duration = 60): array
    {
        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['display_name' => 'Customer'],
        );
        $user = User::factory()->create(['role_id' => $customerRole->id]);
        $customer = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Schedule',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);

        return [
            'user' => $user,
            'customer' => $customer,
            'service' => $this->createService('Scheduling Service', $duration),
            'therapist' => TherapistProfile::create([
                'first_name' => 'Schedule',
                'last_name' => 'Therapist',
                'commission_rate' => 20,
                'status' => 'active',
            ]),
        ];
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

    /**
     * @param  array{user: User, customer: CustomerProfile, service: Service, therapist: TherapistProfile}  $context
     */
    private function createAppointment(
        array $context,
        string $date,
        string $start,
        string $end,
        string $status,
    ): Appointment {
        return Appointment::create([
            'customer_profile_id' => $context['customer']->id,
            'therapist_profile_id' => $context['therapist']->id,
            'service_id' => $context['service']->id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'service_name_snapshot' => $context['service']->name,
            'service_duration_minutes_snapshot' => $context['service']->duration_minutes,
            'service_price_snapshot' => $context['service']->price,
        ]);
    }

    private function createService(string $name, int $duration): Service
    {
        return Service::create([
            'name' => $name,
            'duration_minutes' => $duration,
            'price' => 600,
            'status' => 'active',
        ]);
    }

    /**
     * @param  array{user: User, customer: CustomerProfile, service: Service, therapist: TherapistProfile}  $context
     * @return array<string, int|string>
     */
    private function payload(array $context, string $date, string $time): array
    {
        return [
            'service_id' => $context['service']->id,
            'therapist_profile_id' => $context['therapist']->id,
            'appointment_date' => $date,
            'appointment_time' => $time,
        ];
    }
}
