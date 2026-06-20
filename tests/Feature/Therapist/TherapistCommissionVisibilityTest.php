<?php

namespace Tests\Feature\Therapist;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistCommission;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistCommissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_therapist_list_shows_only_their_pending_paid_and_void_commissions(): void
    {
        $owner = $this->createUserWithRole('therapist');
        $other = $this->createUserWithRole('therapist');

        $this->createCommissionFor($owner, 'Owner Pending Service', TherapistCommission::STATUS_PENDING);
        $this->createCommissionFor($owner, 'Owner Paid Service', TherapistCommission::STATUS_PAID);
        $this->createCommissionFor($owner, 'Owner Void Service', TherapistCommission::STATUS_VOID);
        $this->createCommissionFor($other, 'Private Other Service', TherapistCommission::STATUS_PENDING);

        $this->actingAs($owner)
            ->get(route('therapist.commissions.index'))
            ->assertOk()
            ->assertSee('Owner Pending Service')
            ->assertSee('Owner Paid Service')
            ->assertSee('Owner Void Service')
            ->assertDontSee('Private Other Service');
    }

    public function test_therapist_can_view_own_commission_but_not_another_therapists_record(): void
    {
        $owner = $this->createUserWithRole('therapist');
        $other = $this->createUserWithRole('therapist');
        $ownCommission = $this->createCommissionFor($owner, 'Owned Commission Service');
        $otherCommission = $this->createCommissionFor($other, 'Other Commission Service');

        $this->actingAs($owner)
            ->get(route('therapist.commissions.show', $ownCommission))
            ->assertOk()
            ->assertSee('Owned Commission Service')
            ->assertSee('PHP 170.00');

        $this->get(route('therapist.commissions.show', $otherCommission))->assertNotFound();
    }

    public function test_guest_and_non_therapist_roles_cannot_access_therapist_commissions(): void
    {
        $therapist = $this->createUserWithRole('therapist');
        $commission = $this->createCommissionFor($therapist, 'Protected Commission Service');

        $this->get(route('therapist.commissions.index'))->assertRedirect('/login');
        $this->get(route('therapist.commissions.show', $commission))->assertRedirect('/login');

        foreach (['management', 'customer'] as $roleName) {
            $user = $this->createUserWithRole($roleName);
            $this->actingAs($user)->get(route('therapist.commissions.index'))->assertForbidden();
            $this->get(route('therapist.commissions.show', $commission))->assertForbidden();
        }
    }

    public function test_therapist_without_linked_profile_cannot_view_commissions(): void
    {
        $therapist = $this->createUserWithRole('therapist');

        $this->actingAs($therapist)
            ->get(route('therapist.commissions.index'))
            ->assertForbidden();
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createCommissionFor(
        User $therapistUser,
        string $serviceName,
        string $status = TherapistCommission::STATUS_PENDING,
    ): TherapistCommission {
        $therapist = TherapistProfile::firstOrCreate(
            ['user_id' => $therapistUser->id],
            [
                'first_name' => $therapistUser->name,
                'commission_rate' => 20,
                'status' => 'active',
            ],
        );
        $customer = CustomerProfile::create([
            'first_name' => 'Commission',
            'last_name' => 'Guest',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name' => $serviceName,
            'duration_minutes' => 60,
            'price' => 850,
            'status' => 'active',
        ]);
        $appointment = Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 850,
        ]);
        $manager = $this->createUserWithRole('management');
        $transaction = Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $customer->id,
            'cashier_user_id' => $manager->id,
            'subtotal' => 850,
            'discount_amount' => 0,
            'total_amount' => 850,
            'amount_tendered' => 850,
            'change_due' => 0,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => Transaction::STATUS_PAID,
            'transaction_date' => now(),
        ]);

        return TherapistCommission::create([
            'therapist_profile_id' => $therapist->id,
            'transaction_id' => $transaction->id,
            'appointment_id' => $appointment->id,
            'commission_rate' => 20,
            'commission_amount' => 170,
            'status' => $status,
            'paid_at' => $status === TherapistCommission::STATUS_PAID ? now() : null,
        ]);
    }
}
