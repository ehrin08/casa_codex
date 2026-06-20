<?php

namespace Tests\Feature\Therapist;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistCommissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_therapist_can_view_only_their_own_commissions(): void
    {
        [$owner, $ownerAppointment] = $this->createAppointment('Owner Therapist');
        [, $otherAppointment] = $this->createAppointment('Other Therapist');
        $ownedCommission = $this->createTransaction($ownerAppointment)->therapistCommission;
        $otherCommission = $this->createTransaction($otherAppointment)->therapistCommission;

        $this->actingAs($owner)
            ->get(route('therapist.commissions.index'))
            ->assertOk()
            ->assertSee('Commission Test Service Owner Therapist')
            ->assertDontSee('Commission Test Service Other Therapist');

        $this->get(route('therapist.commissions.show', $ownedCommission))
            ->assertOk()
            ->assertSee('Rate snapshot')
            ->assertDontSee('Mark commission paid');

        $this->get(route('therapist.commissions.show', $otherCommission))->assertNotFound();
    }

    public function test_therapist_cannot_mark_a_commission_paid(): void
    {
        [$therapist, $appointment] = $this->createAppointment('Protected Therapist');
        $commission = $this->createTransaction($appointment)->therapistCommission;

        $this->actingAs($therapist)
            ->patch(route('management.commissions.mark-paid', $commission))
            ->assertForbidden();

        $this->assertSame('pending', $commission->refresh()->status);
        $this->assertNull($commission->paid_at);
    }

    public function test_customers_cannot_access_commission_pages(): void
    {
        [, $appointment] = $this->createAppointment();
        $commission = $this->createTransaction($appointment)->therapistCommission;
        $customer = $this->createUser('customer');

        $this->actingAs($customer)->get(route('management.commissions.index'))->assertForbidden();
        $this->get(route('management.commissions.show', $commission))->assertForbidden();
        $this->patch(route('management.commissions.mark-paid', $commission))->assertForbidden();
        $this->get(route('therapist.commissions.index'))->assertForbidden();
        $this->get(route('therapist.commissions.show', $commission))->assertForbidden();
    }

    public function test_guests_are_redirected_from_commission_pages(): void
    {
        [, $appointment] = $this->createAppointment();
        $commission = $this->createTransaction($appointment)->therapistCommission;

        $this->get(route('management.commissions.index'))->assertRedirect('/login');
        $this->get(route('management.commissions.show', $commission))->assertRedirect('/login');
        $this->patch(route('management.commissions.mark-paid', $commission))->assertRedirect('/login');
        $this->get(route('therapist.commissions.index'))->assertRedirect('/login');
        $this->get(route('therapist.commissions.show', $commission))->assertRedirect('/login');
    }

    /**
     * @return array{User, Appointment}
     */
    private function createAppointment(string $therapistName = 'Visibility Therapist'): array
    {
        $therapistUser = $this->createUser('therapist', $therapistName);
        [$firstName, $lastName] = array_pad(explode(' ', $therapistName, 2), 2, 'Therapist');
        $therapist = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => 'active',
        ]);
        $customer = CustomerProfile::create([
            'first_name' => 'Visibility',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name' => 'Commission Test Service '.$therapistName,
            'duration_minutes' => 60,
            'price' => 500,
            'status' => 'active',
        ]);
        $appointment = Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => today(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 500,
        ]);

        return [$therapistUser, $appointment];
    }

    private function createTransaction(Appointment $appointment): Transaction
    {
        return Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'cashier_user_id' => $this->createUser('management')->id,
            'subtotal' => 500,
            'discount_amount' => 0,
            'total_amount' => 500,
            'amount_tendered' => 500,
            'change_due' => 0,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => Transaction::STATUS_PAID,
            'transaction_date' => now(),
        ]);
    }

    private function createUser(string $roleName, ?string $name = null): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'name' => $name ?? ucfirst($roleName).' User',
        ]);
    }
}
