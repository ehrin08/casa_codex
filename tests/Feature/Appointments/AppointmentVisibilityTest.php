<?php

namespace Tests\Feature\Appointments;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_view_all_appointments_and_filter_them(): void
    {
        $manager = $this->createUser('management');
        [, $customerOne] = $this->createCustomer('First Customer');
        [, $customerTwo] = $this->createCustomer('Second Customer');
        [, $therapistOne] = $this->createTherapist('First Therapist');
        [, $therapistTwo] = $this->createTherapist('Second Therapist');
        $targetDate = now()->addDay()->toDateString();
        $target = $this->createAppointment($customerOne, $therapistOne, 'Filtered Service', $targetDate, Appointment::STATUS_CONFIRMED);
        $other = $this->createAppointment($customerTwo, $therapistTwo, 'Other Service', now()->addDays(2)->toDateString());

        $this->actingAs($manager)
            ->get(route('management.appointments.index'))
            ->assertOk()
            ->assertSee($target->service_name_snapshot)
            ->assertSee($other->service_name_snapshot);

        $this->get(route('management.appointments.index', [
            'appointment_date' => $targetDate,
            'status' => Appointment::STATUS_CONFIRMED,
            'therapist_profile_id' => $therapistOne->id,
            'customer_profile_id' => $customerOne->id,
        ]))
            ->assertOk()
            ->assertViewHas('appointments', fn ($appointments) => $appointments
                ->getCollection()
                ->modelKeys() === [$target->id])
            ->assertSee($target->service_name_snapshot);
    }

    public function test_therapist_schedule_only_shows_their_assigned_appointments(): void
    {
        [$therapistUser, $therapistProfile] = $this->createTherapist('Schedule Owner');
        [, $otherTherapist] = $this->createTherapist('Other Therapist');
        [, $customer] = $this->createCustomer('Schedule Customer');
        $todayAppointment = $this->createAppointment($customer, $therapistProfile, 'Owner Today Service', today()->toDateString());
        $upcomingAppointment = $this->createAppointment($customer, $therapistProfile, 'Owner Upcoming Service', now()->addDay()->toDateString());
        $otherAppointment = $this->createAppointment($customer, $otherTherapist, 'Private Other Service', now()->addDay()->toDateString());

        $this->actingAs($therapistUser)
            ->get(route('therapist.schedule.index'))
            ->assertOk()
            ->assertSee($todayAppointment->service_name_snapshot)
            ->assertSee($upcomingAppointment->service_name_snapshot)
            ->assertDontSee($otherAppointment->service_name_snapshot);

        $this->get(route('therapist.appointments.show', $upcomingAppointment))->assertOk();
        $this->get(route('therapist.appointments.show', $otherAppointment))->assertNotFound();
    }

    public function test_customer_list_only_shows_their_own_appointments(): void
    {
        [$customerUser, $customerProfile] = $this->createCustomer('Appointment Owner');
        [, $otherCustomer] = $this->createCustomer('Other Customer');
        [, $therapist] = $this->createTherapist('Customer Therapist');
        $upcoming = $this->createAppointment($customerProfile, $therapist, 'Owner Upcoming Appointment', now()->addDay()->toDateString());
        $past = $this->createAppointment($customerProfile, $therapist, 'Owner Past Appointment', now()->subDay()->toDateString(), Appointment::STATUS_COMPLETED);
        $other = $this->createAppointment($otherCustomer, $therapist, 'Private Customer Appointment', now()->addDay()->toDateString());

        $this->actingAs($customerUser)
            ->get(route('customer.appointments.index'))
            ->assertOk()
            ->assertSee($upcoming->service_name_snapshot)
            ->assertSee($past->service_name_snapshot)
            ->assertDontSee($other->service_name_snapshot);

        $this->get(route('customer.appointments.show', $upcoming))->assertOk();
        $this->get(route('customer.appointments.show', $other))->assertNotFound();
    }

    public function test_role_specific_appointment_views_remain_role_protected(): void
    {
        $manager = $this->createUser('management');
        [$therapist] = $this->createTherapist('Protected Therapist');
        [$customer] = $this->createCustomer('Protected Customer');

        $this->actingAs($manager)->get(route('therapist.schedule.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('customer.appointments.index'))->assertForbidden();
        $this->actingAs($therapist)->get(route('customer.appointments.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('therapist.schedule.index'))->assertForbidden();
    }

    private function createUser(string $roleName, ?string $name = null): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name ?? ucfirst($roleName).' User',
        ]);
    }

    /** @return array{User, CustomerProfile} */
    private function createCustomer(string $name): array
    {
        $user = $this->createUser('customer', $name);
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $profile = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'is_active' => true,
        ]);

        return [$user, $profile];
    }

    /** @return array{User, TherapistProfile} */
    private function createTherapist(string $name): array
    {
        $user = $this->createUser('therapist', $name);
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);
        $profile = TherapistProfile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => 'active',
        ]);

        return [$user, $profile];
    }

    private function createAppointment(
        CustomerProfile $customer,
        TherapistProfile $therapist,
        string $serviceName,
        string $date,
        string $status = Appointment::STATUS_PENDING,
    ): Appointment {
        $service = Service::create([
            'name' => $serviceName,
            'duration_minutes' => 60,
            'price' => 700,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => $status,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $service->price,
            'notes' => 'Appointment visibility test notes.',
        ]);
    }
}
