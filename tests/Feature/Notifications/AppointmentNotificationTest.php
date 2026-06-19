<?php

namespace Tests\Feature\Notifications;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\SystemNotification;
use App\Models\TherapistAvailability;
use App\Models\TherapistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_notifies_all_management_users_and_assigned_therapist(): void
    {
        $managerOne = $this->createUser('management', 'Manager One');
        $managerTwo = $this->createUser('management', 'Manager Two');
        [$therapistUser, $therapistProfile] = $this->createTherapist();
        [$customerUser, $customerProfile] = $this->createCustomer();
        $service = $this->createService();
        $date = now()->addDay()->toDateString();
        TherapistAvailability::create([
            'therapist_profile_id' => $therapistProfile->id,
            'availability_date' => $date,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'active',
        ]);

        $this->actingAs($customerUser)->post(route('customer.appointments.store'), [
            'service_id' => $service->id,
            'therapist_profile_id' => $therapistProfile->id,
            'appointment_date' => $date,
            'appointment_time' => '10:00',
        ])->assertSessionHasNoErrors();

        $appointment = Appointment::firstOrFail();
        $notifications = SystemNotification::where('type', 'appointment_booked')->get();
        $this->assertCount(3, $notifications);
        $this->assertEqualsCanonicalizing(
            [$managerOne->id, $managerTwo->id, $therapistUser->id],
            $notifications->pluck('recipient_user_id')->all(),
        );
        $this->assertTrue($notifications->every(fn (SystemNotification $notification) => $notification->data['appointment_id'] === $appointment->id));
        $this->assertFalse($notifications->contains('recipient_user_id', $customerProfile->user_id));
    }

    public function test_status_change_notifies_customer_and_assigned_therapist_only_once(): void
    {
        $manager = $this->createUser('management', 'Status Manager');
        [$therapistUser, $therapistProfile] = $this->createTherapist();
        [$customerUser, $customerProfile] = $this->createCustomer();
        $appointment = $this->createAppointment($customerProfile, $therapistProfile);

        $this->actingAs($manager)->patch(route('management.appointments.update-status', $appointment), [
            'status' => Appointment::STATUS_CONFIRMED,
        ])->assertSessionHasNoErrors();

        $notifications = SystemNotification::where('type', 'appointment_status_changed')->get();
        $this->assertCount(2, $notifications);
        $this->assertEqualsCanonicalizing(
            [$customerUser->id, $therapistUser->id],
            $notifications->pluck('recipient_user_id')->all(),
        );
        $this->assertFalse($notifications->contains('recipient_user_id', $manager->id));

        $this->patch(route('management.appointments.update-status', $appointment), [
            'status' => Appointment::STATUS_CONFIRMED,
        ]);
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_users_view_only_their_own_notifications_and_can_only_mark_their_own_as_read(): void
    {
        $owner = $this->createUser('customer', 'Notification Owner');
        $other = $this->createUser('customer', 'Notification Other');
        $ownerNotification = $this->createNotification($owner, 'Owner notification');
        $otherNotification = $this->createNotification($other, 'Private other notification');

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($ownerNotification->title)
            ->assertDontSee($otherNotification->title);

        $this->patch(route('notifications.read', $ownerNotification))
            ->assertSessionHas('success');
        $this->assertTrue($ownerNotification->fresh()->is_read);
        $this->assertNotNull($ownerNotification->fresh()->read_at);

        $this->patch(route('notifications.read', $otherNotification))->assertNotFound();
        $this->assertFalse($otherNotification->fresh()->is_read);
    }

    public function test_guests_cannot_access_or_mark_notifications_as_read(): void
    {
        $user = $this->createUser('customer');
        $notification = $this->createNotification($user, 'Guest protected notification');

        $this->get(route('notifications.index'))->assertRedirect('/login');
        $this->patch(route('notifications.read', $notification))->assertRedirect('/login');
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
    private function createCustomer(): array
    {
        $user = $this->createUser('customer', 'Booking Customer');
        $profile = CustomerProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Booking',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);

        return [$user, $profile];
    }

    /** @return array{User, TherapistProfile} */
    private function createTherapist(): array
    {
        $user = $this->createUser('therapist', 'Assigned Therapist');
        $profile = TherapistProfile::create([
            'user_id' => $user->id,
            'first_name' => 'Assigned',
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => 'active',
        ]);

        return [$user, $profile];
    }

    private function createService(): Service
    {
        return Service::create([
            'name' => 'Notification Service',
            'duration_minutes' => 60,
            'price' => 700,
            'status' => 'active',
        ]);
    }

    private function createAppointment(CustomerProfile $customer, TherapistProfile $therapist): Appointment
    {
        $service = $this->createService();

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

    private function createNotification(User $user, string $title): SystemNotification
    {
        return SystemNotification::create([
            'recipient_user_id' => $user->id,
            'title' => $title,
            'message' => 'Notification ownership test.',
            'type' => 'system',
            'is_read' => false,
        ]);
    }
}
