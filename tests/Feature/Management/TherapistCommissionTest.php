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
use App\Services\TherapistCommissionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TherapistCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_transaction_creates_pending_commission_from_subtotal_and_rate_snapshot(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment(rate: 20, subtotal: 850);

        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID, '50.00');

        $transaction = Transaction::sole();
        $commission = TherapistCommission::sole();

        $this->assertSame($appointment->therapist_profile_id, $commission->therapist_profile_id);
        $this->assertSame($transaction->id, $commission->transaction_id);
        $this->assertSame($appointment->id, $commission->appointment_id);
        $this->assertSame('20.00', $commission->commission_rate);
        $this->assertSame('170.00', $commission->commission_amount);
        $this->assertSame(TherapistCommission::STATUS_PENDING, $commission->status);
        $this->assertSame('800.00', $transaction->total_amount);
        $this->assertNull($commission->paid_at);
    }

    public function test_fractional_commission_is_rounded_to_nearest_cent(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment(rate: 12.5, subtotal: 999.99);

        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID);

        $commission = TherapistCommission::sole();

        $this->assertSame('12.50', $commission->commission_rate);
        $this->assertSame('125.00', $commission->commission_amount);
    }

    public function test_pending_and_void_transactions_do_not_create_commissions(): void
    {
        $manager = $this->createUserWithRole('management');

        foreach ([Transaction::STATUS_PENDING, Transaction::STATUS_VOID] as $status) {
            $appointment = $this->createAppointment();
            $this->recordTransaction($manager, $appointment, $status);
        }

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('therapist_commissions', 0);
    }

    public function test_commission_recorder_returns_existing_record_instead_of_duplicating(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID);

        $transaction = Transaction::sole();
        $original = TherapistCommission::sole();
        $recorder = app(TherapistCommissionRecorder::class);

        $firstRetry = $recorder->recordFor($transaction);
        $secondRetry = $recorder->recordFor($transaction);

        $this->assertSame($original->id, $firstRetry?->id);
        $this->assertSame($original->id, $secondRetry?->id);
        $this->assertDatabaseCount('therapist_commissions', 1);
    }

    public function test_management_can_view_commission_list_and_detail(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID);
        $commission = TherapistCommission::sole();

        $this->get(route('management.commissions.index'))
            ->assertOk()
            ->assertSee('Commission Therapist')
            ->assertSee('Commission Test Service')
            ->assertSee('PHP 170.00');

        $this->get(route('management.commissions.show', $commission))
            ->assertOk()
            ->assertSee('Calculation snapshot')
            ->assertSee('20.00%')
            ->assertSee('PHP 170.00')
            ->assertSee('Save commission status');
    }

    public function test_management_can_mark_pending_commission_paid_and_terminal_status_cannot_change(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID);
        $commission = TherapistCommission::sole();

        $this->patch(route('management.commissions.update-status', $commission), [
            'status' => TherapistCommission::STATUS_PAID,
            'notes' => 'Included in therapist payout.',
        ])->assertRedirect(route('management.commissions.show', $commission));

        $commission->refresh();
        $this->assertSame(TherapistCommission::STATUS_PAID, $commission->status);
        $this->assertNotNull($commission->paid_at);
        $this->assertSame('Included in therapist payout.', $commission->notes);

        $this->patch(route('management.commissions.update-status', $commission), [
            'status' => TherapistCommission::STATUS_VOID,
        ])->assertSessionHasErrors('status');

        $this->assertSame(TherapistCommission::STATUS_PAID, $commission->fresh()->status);
    }

    public function test_management_can_void_pending_commission_without_paid_timestamp(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $this->recordTransaction($manager, $appointment, Transaction::STATUS_PAID);
        $commission = TherapistCommission::sole();

        $this->patch(route('management.commissions.update-status', $commission), [
            'status' => TherapistCommission::STATUS_VOID,
            'notes' => 'Voided after management review.',
        ])->assertSessionHasNoErrors();

        $commission->refresh();
        $this->assertSame(TherapistCommission::STATUS_VOID, $commission->status);
        $this->assertNull($commission->paid_at);
    }

    public function test_management_commission_pages_are_role_protected(): void
    {
        $appointment = $this->createAppointment();
        $commission = $this->createCommission($appointment);

        $this->get(route('management.commissions.index'))->assertRedirect('/login');
        $this->get(route('management.commissions.show', $commission))->assertRedirect('/login');
        $this->patch(route('management.commissions.update-status', $commission), [
            'status' => TherapistCommission::STATUS_PAID,
        ])->assertRedirect('/login');

        foreach (['therapist', 'customer'] as $roleName) {
            $user = $this->createUserWithRole($roleName);
            $this->actingAs($user)->get(route('management.commissions.index'))->assertForbidden();
            $this->get(route('management.commissions.show', $commission))->assertForbidden();
            $this->patch(route('management.commissions.update-status', $commission), [
                'status' => TherapistCommission::STATUS_PAID,
            ])->assertForbidden();
        }
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createAppointment(float $rate = 20, float $subtotal = 850): Appointment
    {
        $customer = CustomerProfile::create([
            'first_name' => 'Commission',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Commission',
            'last_name' => 'Therapist',
            'commission_rate' => $rate,
            'status' => 'active',
        ]);
        $service = Service::create([
            'name' => 'Commission Test Service',
            'duration_minutes' => 60,
            'price' => $subtotal,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $subtotal,
        ]);
    }

    private function recordTransaction(
        User $manager,
        Appointment $appointment,
        string $status,
        string $discount = '0.00',
    ): void {
        $data = [
            'appointment_id' => $appointment->id,
            'discount_amount' => $discount,
            'payment_status' => $status,
            'transaction_date' => now()->format('Y-m-d H:i:s'),
        ];

        if ($status === Transaction::STATUS_PAID) {
            $data['amount_tendered'] = '1000.00';
        }

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $data)
            ->assertSessionHasNoErrors();
    }

    private function createCommission(Appointment $appointment): TherapistCommission
    {
        $manager = $this->createUserWithRole('management');
        $transaction = Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
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
            'therapist_profile_id' => $appointment->therapist_profile_id,
            'transaction_id' => $transaction->id,
            'appointment_id' => $appointment->id,
            'commission_rate' => 20,
            'commission_amount' => 170,
            'status' => TherapistCommission::STATUS_PENDING,
        ]);
    }
}
