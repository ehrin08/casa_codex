<?php

namespace Tests\Feature\Customer;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use App\Services\AppointmentSlotFinder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSlotTest extends TestCase
{
    use RefreshDatabase;

    public function test_slots_use_exact_and_recurring_availability_and_service_duration(): void
    {
        $service = $this->createService(duration: 60);
        $therapist = $this->createTherapist();
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '11:00');
        $this->createAvailability($therapist, $date, '13:00', '14:00', recurring: true);

        $slots = app(AppointmentSlotFinder::class)->availableSlots($service->id, $therapist->id, $date);

        $this->assertSame(['09:00', '09:30', '10:00', '13:00'], $slots);
    }

    public function test_blocking_conflicts_are_removed_while_adjacent_cancelled_and_no_show_times_remain(): void
    {
        $service = $this->createService(duration: 60);
        $therapist = $this->createTherapist();
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '13:00');
        $this->createAppointment($therapist, $service, $date, '10:00', '11:00', Appointment::STATUS_CONFIRMED);
        $this->createAppointment($therapist, $service, $date, '11:00', '12:00', Appointment::STATUS_CANCELLED);
        $this->createAppointment($therapist, $service, $date, '12:00', '13:00', Appointment::STATUS_NO_SHOW);

        $slots = app(AppointmentSlotFinder::class)->availableSlots($service->id, $therapist->id, $date);

        $this->assertSame(['09:00', '11:00', '11:30', '12:00'], $slots);
    }

    public function test_inactive_services_therapists_and_availability_do_not_produce_slots(): void
    {
        $activeService = $this->createService();
        $inactiveService = $this->createService('Inactive Service', 'inactive');
        $activeTherapist = $this->createTherapist();
        $inactiveTherapist = $this->createTherapist('Inactive', 'Therapist', 'inactive');
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($activeTherapist, $date, status: 'inactive');
        $this->createAvailability($inactiveTherapist, $date);

        $finder = app(AppointmentSlotFinder::class);

        $this->assertSame([], $finder->availableSlots($inactiveService->id, $activeTherapist->id, $date));
        $this->assertSame([], $finder->availableSlots($activeService->id, $inactiveTherapist->id, $date));
        $this->assertSame([], $finder->availableSlots($activeService->id, $activeTherapist->id, $date));
    }

    public function test_customer_slot_endpoint_and_booking_page_expose_selectable_slots(): void
    {
        $customer = $this->createCustomer();
        $service = $this->createService();
        $therapist = $this->createTherapist();
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '10:00');

        $this->actingAs($customer)
            ->get(route('customer.appointments.create'))
            ->assertOk()
            ->assertSee('data-slot-picker', false)
            ->assertSee(route('customer.appointments.slots'), false)
            ->assertSee('No available times for this date.')
            ->assertDontSee('type="time"', false);

        $this->getJson(route('customer.appointments.slots', [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
        ]))->assertOk()->assertExactJson(['slots' => ['09:00']]);
    }

    public function test_slot_endpoint_is_restricted_to_customers(): void
    {
        $this->getJson(route('customer.appointments.slots'))->assertUnauthorized();

        $role = Role::firstOrCreate(['name' => 'management'], ['display_name' => 'Management']);
        $manager = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($manager)
            ->getJson(route('customer.appointments.slots'))
            ->assertForbidden();
    }

    public function test_past_same_day_slots_are_hidden_and_rejected_if_manually_submitted(): void
    {
        $this->travelTo(Carbon::parse('2026-07-20 10:15:00'));

        try {
            $customer = $this->createCustomer();
            $service = $this->createService();
            $therapist = $this->createTherapist();
            $date = now()->toDateString();
            $this->createAvailability($therapist, $date, '09:00', '12:00');

            $slots = app(AppointmentSlotFinder::class)->availableSlots($service->id, $therapist->id, $date);

            $this->assertSame(['10:30', '11:00'], $slots);

            $this->actingAs($customer)->post(route('customer.appointments.store'), [
                'service_id' => $service->id,
                'therapist_profile_id' => $therapist->id,
                'appointment_date' => $date,
                'appointment_time' => '10:00',
            ])->assertSessionHasErrors('appointment_time');
        } finally {
            $this->travelBack();
        }
    }

    public function test_a_slot_that_becomes_stale_is_rejected_by_the_scheduler(): void
    {
        $customer = $this->createCustomer();
        $service = $this->createService();
        $therapist = $this->createTherapist();
        $date = now()->addDays(2)->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '11:00');

        $this->actingAs($customer)->getJson(route('customer.appointments.slots', [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
        ]))->assertJsonFragment(['09:00']);

        $this->createAppointment($therapist, $service, $date, '09:00', '10:00', Appointment::STATUS_PENDING);

        $this->post(route('customer.appointments.store'), [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
            'appointment_time' => '09:00',
        ])->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    private function createCustomer(): User
    {
        $role = Role::firstOrCreate(['name' => 'customer'], ['display_name' => 'Customer']);
        $user = User::factory()->create(['role_id' => $role->id]);
        CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Slot',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);

        return $user;
    }

    private function createService(
        string $name = 'Slot Service',
        string $status = 'active',
        int $duration = 60,
    ): Service {
        return Service::create([
            'name' => $name,
            'duration_minutes' => $duration,
            'price' => 700,
            'status' => $status,
        ]);
    }

    private function createTherapist(
        string $firstName = 'Slot',
        string $lastName = 'Therapist',
        string $status = 'active',
    ): TherapistProfile {
        return TherapistProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
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
        Service $service,
        string $date,
        string $start,
        string $end,
        string $status,
    ): Appointment {
        return Appointment::create([
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);
    }
}
