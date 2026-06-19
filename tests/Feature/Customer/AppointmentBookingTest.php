<?php

namespace Tests\Feature\Customer;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_access_booking_page_and_only_active_options_are_shown(): void
    {
        [$customer] = $this->createCustomer();
        $activeService = $this->createService('Active Massage');
        $inactiveService = $this->createService('Inactive Massage', 'inactive');
        $activeTherapist = $this->createTherapist('Active', 'Therapist');
        $inactiveTherapist = $this->createTherapist('Inactive', 'Therapist', 'inactive');

        $response = $this->actingAs($customer)->get(route('customer.appointments.create'));

        $response
            ->assertOk()
            ->assertSee($activeService->name)
            ->assertSee($activeTherapist->first_name)
            ->assertDontSee($inactiveService->name)
            ->assertDontSee($inactiveTherapist->first_name)
            ->assertSee('action="'.route('customer.appointments.store').'"', false);
    }

    public function test_guest_cannot_access_or_submit_the_booking_form(): void
    {
        $this->get(route('customer.appointments.create'))->assertRedirect('/login');
        $this->post(route('customer.appointments.store'), [])->assertRedirect('/login');
    }

    public function test_management_and_therapist_users_cannot_access_or_submit_bookings(): void
    {
        foreach (['management', 'therapist'] as $roleName) {
            $user = $this->createUserWithRole($roleName);

            $this->actingAs($user)
                ->get(route('customer.appointments.create'))
                ->assertForbidden();
            $this->post(route('customer.appointments.store'), [])
                ->assertForbidden();
        }
    }

    public function test_customer_can_create_a_valid_pending_appointment_with_service_snapshots(): void
    {
        [$customer, $customerProfile] = $this->createCustomer();
        $service = $this->createService('Ventosa Massage', duration: 75, price: 950);
        $therapist = $this->createTherapist('Maya', 'Santos');
        $appointmentDate = now()->addDay()->toDateString();
        TherapistAvailability::create([
            'therapist_profile_id' => $therapist->id,
            'availability_date' => $appointmentDate,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $response = $this->actingAs($customer)->post(route('customer.appointments.store'), [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapist->id,
            'appointment_date' => $appointmentDate,
            'appointment_time' => '10:30',
            'notes' => 'Please use medium pressure.',
        ]);

        $appointment = Appointment::firstOrFail();

        $response
            ->assertRedirect(route('customer.appointments.show', $appointment))
            ->assertSessionHas('success');
        $this->assertSame($customerProfile->id, $appointment->customer_profile_id);
        $this->assertSame($service->id, $appointment->service_id);
        $this->assertSame($therapist->id, $appointment->therapist_profile_id);
        $this->assertSame($appointmentDate, $appointment->appointment_date->toDateString());
        $this->assertSame('10:30:00', $appointment->start_time);
        $this->assertSame('11:45:00', $appointment->end_time);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('Ventosa Massage', $appointment->service_name_snapshot);
        $this->assertSame(75, $appointment->service_duration_minutes_snapshot);
        $this->assertSame('950.00', $appointment->service_price_snapshot);
        $this->assertSame('Please use medium pressure.', $appointment->notes);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_invalid_booking_data_is_rejected(): void
    {
        [$customer] = $this->createCustomer();

        $this->actingAs($customer)->post(route('customer.appointments.store'), [
            'service_id' => 999999,
            'therapist_profile_id' => 999999,
            'appointment_date' => now()->subDay()->toDateString(),
            'appointment_time' => 'not-a-time',
            'notes' => str_repeat('x', 2001),
        ])->assertSessionHasErrors([
            'service_id',
            'therapist_profile_id',
            'appointment_date',
            'appointment_time',
            'notes',
        ]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_inactive_services_and_therapists_cannot_be_booked(): void
    {
        [$customer] = $this->createCustomer();
        $activeService = $this->createService('Active Service');
        $inactiveService = $this->createService('Inactive Service', 'inactive');
        $activeTherapist = $this->createTherapist('Active', 'Therapist');
        $inactiveTherapist = $this->createTherapist('Inactive', 'Therapist', 'inactive');
        $validDate = now()->addDay()->toDateString();

        $this->actingAs($customer)->post(route('customer.appointments.store'), [
            'service_id' => $inactiveService->id,
            'therapist_profile_id' => $activeTherapist->id,
            'appointment_date' => $validDate,
            'appointment_time' => '09:00',
        ])->assertSessionHasErrors('service_id');

        $this->post(route('customer.appointments.store'), [
            'service_id' => $activeService->id,
            'therapist_profile_id' => $inactiveTherapist->id,
            'appointment_date' => $validDate,
            'appointment_time' => '09:00',
        ])->assertSessionHasErrors('therapist_profile_id');

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_customer_can_only_view_their_own_appointment_confirmation(): void
    {
        [$owner, $ownerProfile] = $this->createCustomer();
        [$otherCustomer] = $this->createCustomer();
        $service = $this->createService('Ownership Test Service');
        $therapist = $this->createTherapist('Ownership', 'Tester');
        $appointment = Appointment::create([
            'customer_profile_id' => $ownerProfile->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => 'pending',
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);

        $this->actingAs($owner)
            ->get(route('customer.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Ownership Test Service');

        $this->actingAs($otherCustomer)
            ->get(route('customer.appointments.show', $appointment))
            ->assertNotFound();
    }

    /**
     * @return array{User, CustomerProfile}
     */
    private function createCustomer(): array
    {
        $user = $this->createUserWithRole('customer');
        $profile = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => $user->email,
            'is_active' => true,
        ]);

        return [$user, $profile];
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createService(
        string $name,
        string $status = 'active',
        int $duration = 60,
        int|float $price = 600,
    ): Service {
        return Service::create([
            'name' => $name,
            'duration_minutes' => $duration,
            'price' => $price,
            'status' => $status,
        ]);
    }

    private function createTherapist(
        string $firstName,
        string $lastName,
        string $status = 'active',
    ): TherapistProfile {
        return TherapistProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => $status,
        ]);
    }
}
