<?php

namespace Tests\Feature\Management;

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

class WalkInAppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_user_can_view_walk_in_form_dashboard_link_and_appointments_modal(): void
    {
        $manager = $this->createUser('management');
        $activeCustomer = $this->createCustomer('Active', 'Guest');
        $inactiveCustomer = $this->createCustomer('Inactive', 'Guest', false);
        $activeService = $this->createService('Active Massage');
        $inactiveService = $this->createService('Inactive Massage', 'inactive');
        $activeTherapist = $this->createTherapist('Active', 'Therapist');
        $inactiveTherapist = $this->createTherapist('Inactive', 'Therapist', 'inactive');

        $this->actingAs($manager)
            ->get(route('management.walk-ins.create'))
            ->assertOk()
            ->assertSee('Walk-in Booking')
            ->assertSee($activeCustomer->first_name)
            ->assertDontSee($inactiveCustomer->first_name)
            ->assertSee($activeService->name)
            ->assertDontSee($inactiveService->name)
            ->assertSee($activeTherapist->first_name)
            ->assertDontSee($inactiveTherapist->first_name)
            ->assertSee('Customer Type')
            ->assertSee('Existing Customer')
            ->assertSee('Walk-in Guest')
            ->assertSee('Use this for guests who do not need an online account.')
            ->assertSee('Guest Name')
            ->assertSee('Guest Contact Number')
            ->assertSee('Walk-in Notes')
            ->assertSee('Book Walk-in Appointment')
            ->assertSee('data-appointment-booking-form', false);

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('Book Walk-in')
            ->assertSee(route('management.walk-ins.create'));

        $this->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee('Book walk-in')
            ->assertSee(route('management.walk-ins.create'))
            ->assertSee('data-modal-open="walk-in-booking-modal"', false)
            ->assertSee('id="walk-in-booking-modal"', false)
            ->assertSee('data-appointment-booking-form', false);

        $this->from(route('management.appointments.index'))
            ->post(route('management.walk-ins.store'), ['_modal' => 'walk-in-create'])
            ->assertRedirect(route('management.appointments.index'))
            ->assertSessionHasErrors(['customer_type', 'service_id', 'therapist_profile_id', 'appointment_date', 'appointment_time']);

        $this->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee('data-modal-open-on-load', false);
    }

    public function test_guest_customer_and_therapist_cannot_access_walk_in_booking(): void
    {
        $this->get(route('management.walk-ins.create'))->assertRedirect('/login');
        $this->getJson(route('management.walk-ins.slots'))->assertUnauthorized();
        $this->post(route('management.walk-ins.store'), [
            'customer_type' => 'guest',
            'guest_name' => 'Unauthorized Guest',
        ])->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $role) {
            $this->actingAs($this->createUser($role))
                ->get(route('management.walk-ins.create'))
                ->assertForbidden();
            $this->getJson(route('management.walk-ins.slots'))->assertForbidden();
            $this->post(route('management.walk-ins.store'), [
                'customer_type' => 'guest',
                'guest_name' => 'Unauthorized Guest',
            ])->assertForbidden();
        }
    }

    public function test_slot_endpoint_returns_only_same_day_future_conflict_free_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-06-22 10:15:00'));
        $manager = $this->createUser('management');
        $service = $this->createService('Same-day Massage', duration: 60);
        $therapist = $this->createTherapist();
        $date = today()->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '14:00');
        $this->createAppointment($therapist, $service, $date, '11:00', '12:00');

        $response = $this->actingAs($manager)->getJson(route('management.walk-ins.slots', [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
        ]));

        $response
            ->assertOk()
            ->assertExactJson(['slots' => ['12:00', '12:30', '13:00']]);
    }

    public function test_staff_can_create_walk_in_visible_to_management_and_therapist(): void
    {
        $this->travelTo(Carbon::parse('2026-06-22 10:15:00'));
        $manager = $this->createUser('management', 'Front Desk Staff');
        $therapistUser = $this->createUser('therapist', 'Assigned Therapist');
        $customer = $this->createCustomer('Maria', 'Santos');
        $service = $this->createService('Walk-in Hilot', duration: 60);
        $therapist = $this->createTherapist('Ana', 'Reyes', user: $therapistUser);
        $date = today()->toDateString();
        $this->createAvailability($therapist, $date, '12:00', '17:00');

        $response = $this->actingAs($manager)->post(route('management.walk-ins.store'), [
            'customer_type' => 'existing',
            'customer_profile_id' => $customer->id,
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
            'appointment_time' => '13:00',
            'notes' => 'Guest prefers light pressure.',
        ]);

        $appointment = Appointment::firstOrFail();

        $response
            ->assertRedirect(route('management.appointments.show', $appointment))
            ->assertSessionHas('success', 'Walk-in appointment was created successfully.');
        $this->assertSame($customer->id, $appointment->customer_profile_id);
        $this->assertTrue($appointment->is_walk_in);
        $this->assertNull($appointment->guest_name);
        $this->assertNull($appointment->guest_contact);
        $this->assertSame(Appointment::STATUS_PENDING, $appointment->status);
        $this->assertSame(
            'Walk-in booking created by Front Desk Staff.'.PHP_EOL.'Guest prefers light pressure.',
            $appointment->notes,
        );

        $this->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee('Walk-in Hilot')
            ->assertSee('Maria Santos');

        $this->actingAs($therapistUser)
            ->get(route('therapist.schedule.index'))
            ->assertOk()
            ->assertSee('Walk-in Hilot')
            ->assertSee('Maria Santos')
            ->assertSee('Walk-in booking created by Front Desk Staff.');
    }

    public function test_staff_can_create_guest_walk_in_without_account_or_customer_profile_visible_to_management_and_therapist(): void
    {
        $this->travelTo(Carbon::parse('2026-06-22 10:15:00'));
        $manager = $this->createUser('management', 'Front Desk Staff');
        $therapistUser = $this->createUser('therapist', 'Guest Therapist User');
        $service = $this->createService('Guest Hilot', duration: 60);
        $therapist = $this->createTherapist('Guest', 'Therapist', user: $therapistUser);
        $date = today()->toDateString();
        $this->createAvailability($therapist, $date, '12:00', '17:00');
        $userCount = User::count();

        $response = $this->actingAs($manager)->post(route('management.walk-ins.store'), [
            'customer_type' => 'guest',
            'guest_name' => 'Lina Cruz',
            'guest_contact' => '0917-555-0199',
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
            'appointment_time' => '13:00',
            'notes' => 'Guest prefers a quiet room.',
        ]);

        $appointment = Appointment::firstOrFail();

        $response
            ->assertRedirect(route('management.appointments.show', $appointment))
            ->assertSessionHas('success', 'Walk-in appointment was created successfully.');
        $this->assertNull($appointment->customer_profile_id);
        $this->assertSame('Lina Cruz', $appointment->guest_name);
        $this->assertSame('0917-555-0199', $appointment->guest_contact);
        $this->assertTrue($appointment->is_walk_in);
        $this->assertSame('Walk-in Guest', $appointment->customer_display_label);
        $this->assertSame('Lina Cruz', $appointment->customer_display_name);
        $this->assertSame('0917-555-0199', $appointment->customer_display_contact);
        $this->assertDatabaseCount('customer_profiles', 0);
        $this->assertSame($userCount, User::count());
        $this->assertSame(
            'Walk-in booking created by Front Desk Staff.'.PHP_EOL.'Guest prefers a quiet room.',
            $appointment->notes,
        );

        $this->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee('Guest Hilot')
            ->assertSee('Walk-in Guest')
            ->assertSee('Lina Cruz')
            ->assertSee('0917-555-0199');

        $this->get(route('management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Walk-in Guest')
            ->assertSee('Lina Cruz')
            ->assertSee('0917-555-0199')
            ->assertSee('Appointment notes')
            ->assertSee('Guest prefers a quiet room.');

        $this->actingAs($therapistUser)
            ->get(route('therapist.schedule.index'))
            ->assertOk()
            ->assertSee('Guest Hilot')
            ->assertSee('Walk-in Guest')
            ->assertSee('Lina Cruz')
            ->assertSee('Walk-in booking created by Front Desk Staff.');

        $this->get(route('therapist.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Walk-in Guest')
            ->assertSee('Lina Cruz')
            ->assertSee('0917-555-0199');
    }

    public function test_guest_name_is_required_and_guest_contact_is_optional(): void
    {
        $manager = $this->createUser('management');
        $service = $this->createService(duration: 60);
        $therapist = $this->createTherapist();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '13:00');

        $base = [
            'customer_type' => 'guest',
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
            'appointment_time' => '09:00',
        ];

        $this->actingAs($manager)
            ->post(route('management.walk-ins.store'), $base)
            ->assertSessionHasErrors('guest_name');

        $this->post(route('management.walk-ins.store'), $base + ['guest_name' => 'No Contact Guest'])
            ->assertSessionHasNoErrors();

        $appointment = Appointment::firstOrFail();
        $this->assertNull($appointment->customer_profile_id);
        $this->assertSame('No Contact Guest', $appointment->guest_name);
        $this->assertNull($appointment->guest_contact);
        $this->assertTrue($appointment->is_walk_in);
        $this->assertDatabaseCount('customer_profiles', 0);
    }

    public function test_inactive_customer_service_and_therapist_are_rejected(): void
    {
        $manager = $this->createUser('management');
        $activeCustomer = $this->createCustomer();
        $inactiveCustomer = $this->createCustomer('Inactive', 'Customer', false);
        $activeService = $this->createService();
        $inactiveService = $this->createService('Inactive Service', 'inactive');
        $activeTherapist = $this->createTherapist();
        $inactiveTherapist = $this->createTherapist('Inactive', 'Therapist', 'inactive');
        $date = now()->addDay()->toDateString();

        $base = [
            'customer_type' => 'existing',
            'customer_profile_id' => $activeCustomer->id,
            'service_id' => $activeService->id,
            'therapist_profile_id' => $activeTherapist->id,
            'appointment_date' => $date,
            'appointment_time' => '09:00',
        ];

        $this->actingAs($manager)
            ->post(route('management.walk-ins.store'), array_replace($base, [
                'customer_profile_id' => $inactiveCustomer->id,
            ]))
            ->assertSessionHasErrors('customer_profile_id');
        $this->post(route('management.walk-ins.store'), array_replace($base, [
            'service_id' => $inactiveService->id,
        ]))->assertSessionHasErrors('service_id');
        $this->post(route('management.walk-ins.store'), array_replace($base, [
            'therapist_profile_id' => $inactiveTherapist->id,
        ]))->assertSessionHasErrors('therapist_profile_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_tampered_and_overlapping_slots_are_rejected(): void
    {
        $manager = $this->createUser('management');
        $service = $this->createService(duration: 60);
        $therapist = $this->createTherapist();
        $date = now()->addDay()->toDateString();
        $this->createAvailability($therapist, $date, '09:00', '13:00');

        $base = [
            'customer_type' => 'guest',
            'guest_name' => 'Slot Guest',
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $date,
        ];

        $this->actingAs($manager)
            ->post(route('management.walk-ins.store'), $base + ['appointment_time' => '09:15'])
            ->assertSessionHasErrors('appointment_time');

        $this->createAppointment($therapist, $service, $date, '10:00', '11:00');

        $this->post(route('management.walk-ins.store'), $base + ['appointment_time' => '10:00'])
            ->assertSessionHasErrors('appointment_time');

        $this->assertDatabaseCount('appointments', 1);
    }

    private function createUser(string $roleName, ?string $name = null): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name ?? fake()->name(),
        ]);
    }

    private function createCustomer(
        string $firstName = 'Walk-in',
        string $lastName = 'Guest',
        bool $active = true,
    ): CustomerProfile {
        return CustomerProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => $active,
        ]);
    }

    private function createService(
        string $name = 'Massage',
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
        string $firstName = 'Test',
        string $lastName = 'Therapist',
        string $status = 'active',
        ?User $user = null,
    ): TherapistProfile {
        return TherapistProfile::create([
            'user_id' => $user?->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => $status,
        ]);
    }

    private function createAvailability(
        TherapistProfile $therapist,
        string $date,
        string $start,
        string $end,
    ): TherapistAvailability {
        return TherapistAvailability::create([
            'therapist_profile_id' => $therapist->id,
            'availability_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'active',
        ]);
    }

    private function createAppointment(
        TherapistProfile $therapist,
        Service $service,
        string $date,
        string $start,
        string $end,
    ): Appointment {
        return Appointment::create([
            'customer_profile_id' => $this->createCustomer(fake()->firstName())->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => Appointment::STATUS_CONFIRMED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);
    }
}
