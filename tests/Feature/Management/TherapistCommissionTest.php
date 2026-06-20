<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistCommission;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TherapistCommissionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_cash_transaction_creates_an_accurate_rate_snapshot_once(): void
    {
        [$therapistUser, $therapist, $appointment] = $this->createAppointment('Snapshot Therapist', 17.50, 999.99);
        $transaction = $this->createTransaction($appointment, Transaction::STATUS_PAID, 999.99);

        $commission = TherapistCommission::sole();

        $this->assertSame($transaction->id, $commission->transaction_id);
        $this->assertSame($appointment->id, $commission->appointment_id);
        $this->assertSame($therapist->id, $commission->therapist_profile_id);
        $this->assertSame($therapistUser->id, $commission->therapist_user_id);
        $this->assertSame('17.50', $commission->commission_rate);
        $this->assertSame('999.99', $commission->commission_base_amount);
        $this->assertSame('175.00', $commission->commission_amount);
        $this->assertSame(TherapistCommission::STATUS_PENDING, $commission->status);

        $therapist->update(['commission_rate' => 40]);
        app(TherapistCommissionCalculator::class)->sync($transaction);

        $commission->refresh();
        $this->assertSame('17.50', $commission->commission_rate);
        $this->assertSame('175.00', $commission->commission_amount);
        $this->assertDatabaseCount('therapist_commissions', 1);
    }

    public function test_pending_transaction_waits_for_payment_and_voided_unpaid_commission_is_not_payable(): void
    {
        [, , $appointment] = $this->createAppointment();
        $transaction = $this->createTransaction($appointment, Transaction::STATUS_PENDING);

        $this->assertDatabaseCount('therapist_commissions', 0);

        $transaction->update(['payment_status' => Transaction::STATUS_PAID]);
        $commission = TherapistCommission::sole();
        $this->assertSame(TherapistCommission::STATUS_PENDING, $commission->status);

        $transaction->update(['payment_status' => Transaction::STATUS_VOID]);
        $this->assertSame(TherapistCommission::STATUS_VOID, $commission->refresh()->status);

        $transaction->update(['payment_status' => Transaction::STATUS_PAID, 'subtotal' => 1200]);
        $commission->refresh();
        $this->assertSame(TherapistCommission::STATUS_PENDING, $commission->status);
        $this->assertSame('20.00', $commission->commission_rate);
        $this->assertSame('1200.00', $commission->commission_base_amount);
        $this->assertSame('240.00', $commission->commission_amount);
        $this->assertDatabaseCount('therapist_commissions', 1);
    }

    public function test_initially_void_transaction_does_not_create_a_payable_commission(): void
    {
        [, , $appointment] = $this->createAppointment();
        $this->createTransaction($appointment, Transaction::STATUS_VOID);

        $this->assertDatabaseCount('therapist_commissions', 0);
    }

    public function test_management_can_filter_view_and_mark_only_pending_commissions_paid(): void
    {
        $manager = $this->createUser('management', 'Commission Manager');
        [, , $firstAppointment] = $this->createAppointment('First Commission Therapist');
        [, , $secondAppointment] = $this->createAppointment('Second Commission Therapist');
        $firstCommission = $this->createTransaction($firstAppointment)->therapistCommission;
        $secondCommission = $this->createTransaction($secondAppointment)->therapistCommission;

        $this->actingAs($manager)
            ->get(route('management.commissions.index'))
            ->assertOk()
            ->assertSee('First Commission Therapist')
            ->assertSee('Second Commission Therapist');

        $this->get(route('management.commissions.index', [
            'therapist_profile_id' => $firstAppointment->therapist_profile_id,
            'status' => TherapistCommission::STATUS_PENDING,
        ]))
            ->assertOk()
            ->assertSee('First Commission Therapist')
            ->assertDontSee('href="'.route('management.commissions.show', $secondCommission).'"', false);

        $this->get(route('management.commissions.show', $firstCommission))
            ->assertOk()
            ->assertSee('Rate snapshot')
            ->assertSee('Mark commission paid');

        $this->patch(route('management.commissions.mark-paid', $firstCommission))
            ->assertRedirect(route('management.commissions.show', $firstCommission))
            ->assertSessionHas('success');

        $firstCommission->refresh();
        $this->assertSame(TherapistCommission::STATUS_PAID, $firstCommission->status);
        $this->assertNotNull($firstCommission->paid_at);

        $this->patch(route('management.commissions.mark-paid', $firstCommission))
            ->assertSessionHasErrors('status');

        $secondCommission->transaction->update(['payment_status' => Transaction::STATUS_VOID]);
        $this->patch(route('management.commissions.mark-paid', $secondCommission))
            ->assertSessionHasErrors('status');

        $this->assertSame(TherapistCommission::STATUS_VOID, $secondCommission->refresh()->status);
    }

    public function test_paid_commission_is_not_recalculated_by_later_transaction_changes(): void
    {
        $manager = $this->createUser('management');
        [, $therapist, $appointment] = $this->createAppointment(rate: 25, subtotal: 800);
        $transaction = $this->createTransaction($appointment, subtotal: 800);
        $commission = $transaction->therapistCommission;

        $this->actingAs($manager)
            ->patch(route('management.commissions.mark-paid', $commission))
            ->assertSessionHasNoErrors();

        $therapist->update(['commission_rate' => 50]);
        $transaction->update(['subtotal' => 1000]);

        $commission->refresh();
        $this->assertSame(TherapistCommission::STATUS_PAID, $commission->status);
        $this->assertSame('25.00', $commission->commission_rate);
        $this->assertSame('800.00', $commission->commission_base_amount);
        $this->assertSame('200.00', $commission->commission_amount);
    }

    /**
     * @return array{User, TherapistProfile, Appointment}
     */
    private function createAppointment(
        string $therapistName = 'Commission Therapist',
        float $rate = 20,
        float $subtotal = 1000,
    ): array {
        $therapistUser = $this->createUser('therapist', $therapistName);
        [$firstName, $lastName] = array_pad(explode(' ', $therapistName, 2), 2, 'Therapist');
        $therapist = TherapistProfile::create([
            'user_id' => $therapistUser->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => $rate,
            'status' => 'active',
        ]);
        $customer = CustomerProfile::create([
            'first_name' => 'Commission',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name' => 'Commission Test Service',
            'duration_minutes' => 60,
            'price' => $subtotal,
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
            'service_price_snapshot' => $subtotal,
        ]);

        return [$therapistUser, $therapist, $appointment];
    }

    private function createTransaction(
        Appointment $appointment,
        string $status = Transaction::STATUS_PAID,
        float $subtotal = 1000,
    ): Transaction {
        return Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'cashier_user_id' => $this->createUser('management')->id,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'total_amount' => $subtotal,
            'amount_tendered' => $status === Transaction::STATUS_PAID ? $subtotal : null,
            'change_due' => $status === Transaction::STATUS_PAID ? 0 : null,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => $status,
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
