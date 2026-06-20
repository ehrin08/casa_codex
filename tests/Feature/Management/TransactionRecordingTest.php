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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_can_access_transaction_pages_and_select_an_eligible_appointment(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->get(route('management.transactions.index'))
            ->assertOk()
            ->assertSee('Cash Transactions');

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('href="'.route('management.transactions.index').'"', false);

        $this->get(route('management.transactions.create'))
            ->assertOk()
            ->assertSee('Choose a completed appointment')
            ->assertSee('Transaction Test Service');

        $this->get(route('management.transactions.create', ['appointment_id' => $appointment->id]))
            ->assertOk()
            ->assertSee('Appointment #'.$appointment->id)
            ->assertSee('PHP 850.00');

        $this->get(route('management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('href="'.route('management.transactions.create', ['appointment_id' => $appointment->id]).'"', false);
    }

    public function test_guests_are_redirected_from_all_transaction_pages(): void
    {
        $appointment = $this->createAppointment();
        $transaction = $this->createTransaction($appointment);

        $this->get(route('management.transactions.index'))->assertRedirect('/login');
        $this->get(route('management.transactions.create'))->assertRedirect('/login');
        $this->get(route('management.transactions.show', $transaction))->assertRedirect('/login');
        $this->post(route('management.transactions.store'), $this->validTransactionData($appointment))
            ->assertRedirect('/login');
    }

    public function test_therapists_and_customers_cannot_access_transaction_workflow(): void
    {
        $appointment = $this->createAppointment();
        $transaction = $this->createTransaction($appointment);

        foreach (['therapist', 'customer'] as $roleName) {
            $user = $this->createUserWithRole($roleName);

            $this->actingAs($user)->get(route('management.transactions.index'))->assertForbidden();
            $this->get(route('management.transactions.create'))->assertForbidden();
            $this->get(route('management.transactions.show', $transaction))->assertForbidden();
            $this->post(route('management.transactions.store'), $this->validTransactionData($appointment))
                ->assertForbidden();
        }
    }

    public function test_management_can_record_paid_cash_transaction_with_computed_total_and_change(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $response = $this->actingAs($manager)
            ->post(route('management.transactions.store'), [
                'appointment_id' => $appointment->id,
                'discount_amount' => '50.00',
                'payment_status' => Transaction::STATUS_PAID,
                'amount_tendered' => '1000.00',
                'transaction_date' => '2026-06-20 14:30:00',
                'notes' => 'Paid over the counter.',
            ]);

        $transaction = Transaction::sole();

        $response
            ->assertRedirect(route('management.transactions.show', $transaction))
            ->assertSessionHas('success');

        $this->assertSame($appointment->id, $transaction->appointment_id);
        $this->assertSame($appointment->customer_profile_id, $transaction->customer_profile_id);
        $this->assertSame($manager->id, $transaction->cashier_user_id);
        $this->assertSame('850.00', $transaction->subtotal);
        $this->assertSame('50.00', $transaction->discount_amount);
        $this->assertSame('800.00', $transaction->total_amount);
        $this->assertSame('1000.00', $transaction->amount_tendered);
        $this->assertSame('200.00', $transaction->change_due);
        $this->assertSame(Transaction::PAYMENT_METHOD_CASH, $transaction->payment_method);
        $this->assertSame(Transaction::STATUS_PAID, $transaction->payment_status);
        $commission = TherapistCommission::sole();
        $this->assertSame($appointment->therapist_profile_id, $commission->therapist_profile_id);
        $this->assertSame($transaction->id, $commission->transaction_id);
        $this->assertSame('20.00', $commission->commission_rate);
        $this->assertSame('170.00', $commission->commission_amount);
        $this->assertSame(TherapistCommission::STATUS_PENDING, $commission->status);
    }

    public function test_transaction_uses_related_service_price_when_snapshot_is_missing(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment(
            status: Appointment::STATUS_COMPLETED,
            snapshotPrice: null,
            servicePrice: 725.50,
        );

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), [
                'appointment_id' => $appointment->id,
                'discount_amount' => '25.00',
                'payment_status' => Transaction::STATUS_PENDING,
                'transaction_date' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();

        $this->assertSame('725.50', $transaction->subtotal);
        $this->assertSame('700.50', $transaction->total_amount);
        $this->assertNull($transaction->amount_tendered);
        $this->assertNull($transaction->change_due);
        $this->assertDatabaseCount('therapist_commissions', 0);
    }

    public function test_non_completed_appointments_cannot_have_transactions(): void
    {
        $manager = $this->createUserWithRole('management');

        foreach ([
            Appointment::STATUS_PENDING,
            Appointment::STATUS_CONFIRMED,
            Appointment::STATUS_CANCELLED,
            Appointment::STATUS_NO_SHOW,
        ] as $status) {
            $appointment = $this->createAppointment(status: $status);

            $this->actingAs($manager)
                ->post(route('management.transactions.store'), $this->validTransactionData($appointment))
                ->assertSessionHasErrors('appointment_id');
        }

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_duplicate_transaction_for_an_appointment_is_rejected(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $this->createTransaction($appointment);

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->validTransactionData($appointment))
            ->assertSessionHasErrors('appointment_id');

        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_discount_must_be_between_zero_and_subtotal(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), array_merge(
                $this->validTransactionData($appointment),
                ['discount_amount' => '-0.01'],
            ))
            ->assertSessionHasErrors('discount_amount');

        $this->post(route('management.transactions.store'), array_merge(
            $this->validTransactionData($appointment),
            ['discount_amount' => '850.01'],
        ))->assertSessionHasErrors('discount_amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_payment_status_and_cash_tender_are_validated(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), array_merge(
                $this->validTransactionData($appointment),
                ['payment_status' => 'refunded'],
            ))
            ->assertSessionHasErrors('payment_status');

        $this->post(route('management.transactions.store'), array_merge(
            $this->validTransactionData($appointment),
            ['amount_tendered' => null],
        ))->assertSessionHasErrors('amount_tendered');

        $this->post(route('management.transactions.store'), array_merge(
            $this->validTransactionData($appointment),
            ['amount_tendered' => '849.99'],
        ))->assertSessionHasErrors('amount_tendered');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_detail_renders_receipt_information(): void
    {
        $manager = $this->createUserWithRole('management');
        $appointment = $this->createAppointment();
        $transaction = $this->createTransaction($appointment, $manager);

        $this->actingAs($manager)
            ->get(route('management.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('CASA PARAISO')
            ->assertSee('Cash receipt')
            ->assertSee('Transaction Customer')
            ->assertSee('Transaction Therapist')
            ->assertSee('Transaction Test Service')
            ->assertSee($manager->name)
            ->assertSee('PHP 850.00')
            ->assertSee('PHP 50.00')
            ->assertSee('PHP 800.00');

        $this->get(route('management.appointments.show', $appointment))
            ->assertOk()
            ->assertSee('href="'.route('management.transactions.show', $transaction).'"', false);
    }

    private function createUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createAppointment(
        string $status = Appointment::STATUS_COMPLETED,
        ?float $snapshotPrice = 850.00,
        float $servicePrice = 900.00,
    ): Appointment {
        $customer = CustomerProfile::create([
            'first_name' => 'Transaction',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Transaction',
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => 'active',
        ]);
        $service = Service::create([
            'name' => 'Transaction Test Service',
            'duration_minutes' => 60,
            'price' => $servicePrice,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => $status,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => $service->duration_minutes,
            'service_price_snapshot' => $snapshotPrice,
        ]);
    }

    private function createTransaction(Appointment $appointment, ?User $cashier = null): Transaction
    {
        $cashier ??= $this->createUserWithRole('management');

        return Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'cashier_user_id' => $cashier->id,
            'subtotal' => 850,
            'discount_amount' => 50,
            'total_amount' => 800,
            'amount_tendered' => 1000,
            'change_due' => 200,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => Transaction::STATUS_PAID,
            'transaction_date' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validTransactionData(Appointment $appointment): array
    {
        return [
            'appointment_id' => $appointment->id,
            'discount_amount' => '0.00',
            'payment_status' => Transaction::STATUS_PAID,
            'amount_tendered' => '850.00',
            'transaction_date' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
