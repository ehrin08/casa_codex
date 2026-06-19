<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_view_appointment_list_and_detail(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee('Status Test Service')
            ->assertSee('Status Customer');

        $this->get(route('management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('Appointment #'.$appointment->id)
            ->assertSee('Update status');
    }

    public function test_management_can_update_status_and_history_records_actor_and_notes(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->patch(route('management.appointments.update-status', $appointment), [
                'status' => Appointment::STATUS_CONFIRMED,
                'status_notes' => 'Confirmed by phone.',
            ])
            ->assertRedirect(route('management.appointments.show', $appointment))
            ->assertSessionHas('success');

        $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->fresh()->status);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $appointment->id,
            'changed_by_user_id' => $manager->id,
            'from_status' => Appointment::STATUS_PENDING,
            'to_status' => Appointment::STATUS_CONFIRMED,
            'note' => 'Confirmed by phone.',
        ]);
    }

    public function test_unchanged_status_does_not_duplicate_history(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)->patch(route('management.appointments.update-status', $appointment), [
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
        $this->patch(route('management.appointments.update-status', $appointment), [
            'status' => Appointment::STATUS_CONFIRMED,
            'status_notes' => 'This should not create another history record.',
        ])->assertSessionHas('success');

        $this->assertDatabaseCount('appointment_status_histories', 1);
    }

    public function test_invalid_status_update_is_rejected(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->patch(route('management.appointments.update-status', $appointment), [
                'status' => 'rescheduled',
                'status_notes' => str_repeat('x', 2001),
            ])
            ->assertSessionHasErrors(['status', 'status_notes']);

        $this->assertSame(Appointment::STATUS_PENDING, $appointment->fresh()->status);
        $this->assertDatabaseCount('appointment_status_histories', 0);
    }

    public function test_customer_and_therapist_cannot_update_appointment_status(): void
    {
        $appointment = $this->createAppointment();

        foreach (['customer', 'therapist'] as $roleName) {
            $user = $this->createUserWithRole($roleName);

            $this->actingAs($user)
                ->patch(route('management.appointments.update-status', $appointment), [
                    'status' => Appointment::STATUS_CONFIRMED,
                ])
                ->assertForbidden();
        }

        $this->assertSame(Appointment::STATUS_PENDING, $appointment->fresh()->status);
    }

    public function test_guest_cannot_access_management_appointments_or_update_status(): void
    {
        $appointment = $this->createAppointment();

        $this->get(route('management.appointments.index'))->assertRedirect('/login');
        $this->get(route('management.appointments.show', $appointment))->assertRedirect('/login');
        $this->patch(route('management.appointments.update-status', $appointment), [
            'status' => Appointment::STATUS_CONFIRMED,
        ])->assertRedirect('/login');
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createAppointment(): Appointment
    {
        $customer = CustomerProfile::create([
            'first_name' => 'Status',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Status',
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => 'active',
        ]);
        $service = Service::create([
            'name' => 'Status Test Service',
            'duration_minutes' => 60,
            'price' => 750,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_PENDING,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
        ]);
    }
}
