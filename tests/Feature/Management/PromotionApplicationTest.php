<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerRfmScore;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_create_page_shows_eligible_promotion_recommendations(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion(['title' => 'Champion Transaction Offer']);

        $this->actingAs($manager)
            ->get(route('management.transactions.create', ['appointment_id' => $appointment->id]))
            ->assertOk()
            ->assertSee('Eligible Promotions')
            ->assertSee('No promotion')
            ->assertSee($promotion->title)
            ->assertSee('PHP 100.00 off')
            ->assertSee('Customer matches the Champion RFM segment.');
    }

    public function test_transaction_create_page_shows_an_empty_state_when_no_promotion_is_eligible(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion([
            'title' => 'Inactive Hidden Offer',
            'status' => Promotion::STATUS_INACTIVE,
        ]);

        $this->actingAs($manager)
            ->get(route('management.transactions.create', ['appointment_id' => $appointment->id]))
            ->assertOk()
            ->assertSee('No promotion')
            ->assertSee('No eligible promotions')
            ->assertDontSee($promotion->title);
    }

    public function test_transaction_without_a_promotion_preserves_manual_discount_behavior(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'discount_amount' => '50.00',
            ]))
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame('50.00', $transaction->discount_amount);
        $this->assertSame('950.00', $transaction->total_amount);
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_eligible_promotion_is_applied_server_side_and_usage_is_recorded_atomically(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion([
            'title' => 'PHP 250 Champion Credit',
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 250,
        ]);

        $response = $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => $promotion->id,
                'discount_amount' => '1.00',
            ]));

        $transaction = Transaction::sole();
        $usage = PromotionUsage::sole();

        $response->assertRedirect(route('management.transactions.show', $transaction));
        $this->assertSame('250.00', $transaction->discount_amount);
        $this->assertSame('750.00', $transaction->total_amount);
        $this->assertSame($promotion->id, $usage->promotion_id);
        $this->assertSame($transaction->id, $usage->transaction_id);
        $this->assertSame($appointment->customer_profile_id, $usage->customer_profile_id);
        $this->assertSame('250.00', $usage->discount_amount);
        $this->assertTrue($usage->used_at->equalTo($transaction->transaction_date));
    }

    public function test_tampered_manual_discount_cannot_override_percentage_promotion_calculation(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion([
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 15,
        ]);

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => $promotion->id,
                'discount_amount' => '999.99',
            ]))
            ->assertSessionHasNoErrors();

        $transaction = Transaction::sole();
        $this->assertSame('150.00', $transaction->discount_amount);
        $this->assertSame('850.00', $transaction->total_amount);
        $this->assertDatabaseCount('promotion_usages', 1);
    }

    public function test_tampered_ineligible_promotion_ids_are_rejected_at_transaction_time(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $inactive = $this->createPromotion(['status' => Promotion::STATUS_INACTIVE]);
        $wrongSegment = $this->createPromotion([
            'rfm_segment_label' => CustomerRfmScore::SEGMENT_AT_RISK,
        ]);

        foreach ([$inactive, $wrongSegment] as $promotion) {
            $this->actingAs($manager)
                ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                    'promotion_id' => $promotion->id,
                ]))
                ->assertSessionHasErrors('promotion_id');
        }

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('promotion_usages', 0);
    }

    public function test_unknown_promotion_id_is_rejected_by_request_validation(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => 999999,
            ]))
            ->assertSessionHasErrors('promotion_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_timestamp_is_used_for_promotion_window_revalidation(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion([
            'starts_at' => '2026-07-01 00:00:00',
            'ends_at' => '2026-07-31 23:59:59',
        ]);

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => $promotion->id,
                'transaction_date' => '2026-06-30 23:59:59',
            ]))
            ->assertSessionHasErrors('promotion_id');

        $this->post(route('management.transactions.store'), $this->transactionData($appointment, [
            'promotion_id' => $promotion->id,
            'transaction_date' => '2026-07-01 00:00:00',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseCount('promotion_usages', 1);
    }

    public function test_pending_transaction_can_record_an_eligible_promotion_usage(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion();

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => $promotion->id,
                'payment_status' => Transaction::STATUS_PENDING,
                'amount_tendered' => null,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(Transaction::STATUS_PENDING, Transaction::sole()->payment_status);
        $this->assertDatabaseCount('promotion_usages', 1);
    }

    public function test_transaction_receipt_shows_the_applied_promotion(): void
    {
        $manager = $this->createManager();
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $promotion = $this->createPromotion(['title' => 'Receipt Champion Offer']);

        $this->actingAs($manager)
            ->post(route('management.transactions.store'), $this->transactionData($appointment, [
                'promotion_id' => $promotion->id,
            ]));

        $transaction = Transaction::sole();

        $this->get(route('management.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Applied promotion')
            ->assertSee('Receipt Champion Offer')
            ->assertSee('PHP 100.00 discount');
    }

    private function createManager(): User
    {
        $role = Role::firstOrCreate(
            ['name' => 'management'],
            ['display_name' => 'Management'],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createAppointment(): Appointment
    {
        $customer = CustomerProfile::create([
            'first_name' => 'Application',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $therapist = TherapistProfile::create([
            'first_name' => 'Application',
            'last_name' => 'Therapist',
            'commission_rate' => 20,
            'status' => 'active',
        ]);
        $service = Service::create([
            'name' => 'Application Test Service',
            'duration_minutes' => 60,
            'price' => 1000,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => '2026-06-21',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => 1000,
        ]);
    }

    private function createScore(Appointment $appointment): CustomerRfmScore
    {
        return CustomerRfmScore::create([
            'customer_profile_id' => $appointment->customer_profile_id,
            'recency_score' => 5,
            'frequency_score' => 5,
            'monetary_score' => 5,
            'segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'calculated_at' => '2026-06-21',
            'source_notes' => 'Promotion application test score.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPromotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'title' => 'Champion Application Offer',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
            'rfm_segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'status' => Promotion::STATUS_ACTIVE,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function transactionData(Appointment $appointment, array $overrides = []): array
    {
        return array_merge([
            'appointment_id' => $appointment->id,
            'promotion_id' => null,
            'discount_amount' => '0.00',
            'payment_status' => Transaction::STATUS_PAID,
            'amount_tendered' => '1000.00',
            'transaction_date' => '2026-06-21 12:00:00',
        ], $overrides);
    }
}
