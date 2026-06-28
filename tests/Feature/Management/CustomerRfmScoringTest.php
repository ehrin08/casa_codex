<?php

namespace Tests\Feature\Management;

use App\Models\CustomerProfile;
use App\Models\CustomerRfmScore;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CustomerRfmScorer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRfmScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_rfm_page_and_recalculation_are_management_only_and_linked_from_dashboard(): void
    {
        $this->get(route('management.rfm.index'))->assertRedirect('/login');
        $this->post(route('management.rfm.recalculate'))->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $roleName) {
            $user = $this->createUser($roleName);

            $this->actingAs($user)
                ->get(route('management.rfm.index'))
                ->assertForbidden();
            $this->post(route('management.rfm.recalculate'))->assertForbidden();
        }

        $manager = $this->createUser('management');

        $this->actingAs($manager)
            ->get(route('management.rfm.index'))
            ->assertOk()
            ->assertSee('Customer RFM Segments')
            ->assertSee('Recalculate RFM Scores');

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('RFM Scores')
            ->assertSee('href="'.route('management.rfm.index').'"', false)
            ->assertSee('Review customer value segments and retention indicators.');
    }

    public function test_recalculation_uses_only_paid_transactions_and_defaults_customers_without_paid_history(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');
        $manager = $this->createUser('management');
        $champion = $this->createCustomer('Paid', 'Champion');
        $noPaidHistory = $this->createCustomer('No', 'History', false);

        for ($index = 0; $index < 7; $index++) {
            $this->createTransaction(
                $champion,
                Transaction::STATUS_PAID,
                1500,
                now()->subDays(10),
                $index === 0 ? null : now()->subDays(10),
            );
        }

        $this->createTransaction($champion, Transaction::STATUS_PENDING, 50000, now());
        $this->createTransaction($champion, Transaction::STATUS_VOID, 50000, now());
        $this->createTransaction($noPaidHistory, Transaction::STATUS_PENDING, 25000, now());

        $this->actingAs($manager)
            ->post(route('management.rfm.recalculate'))
            ->assertRedirect(route('management.rfm.index'))
            ->assertSessionHas('success', 'RFM scores recalculated for 2 customers (2 created, 0 updated).');

        $championScore = CustomerRfmScore::whereBelongsTo($champion)->firstOrFail();
        $this->assertSame(5, $championScore->recency_score);
        $this->assertSame(4, $championScore->frequency_score);
        $this->assertSame(4, $championScore->monetary_score);
        $this->assertSame(CustomerRfmScore::SEGMENT_CHAMPION, $championScore->segment_label);
        $this->assertSame('2026-06-21', $championScore->calculated_at->toDateString());
        $this->assertStringContainsString('Paid transactions: 7', $championScore->source_notes);
        $this->assertStringContainsString('total paid spend: PHP 10,500.00', $championScore->source_notes);
        $this->assertStringContainsString('latest paid transaction: 2026-06-11 12:00:00', $championScore->source_notes);
        $this->assertStringContainsString('recency: 10 days', $championScore->source_notes);

        $defaultScore = CustomerRfmScore::whereBelongsTo($noPaidHistory)->firstOrFail();
        $this->assertSame([1, 1, 1], [
            $defaultScore->recency_score,
            $defaultScore->frequency_score,
            $defaultScore->monetary_score,
        ]);
        $this->assertSame(CustomerRfmScore::SEGMENT_NEW_LOW_ACTIVITY, $defaultScore->segment_label);
        $this->assertStringContainsString('latest paid transaction: none', $defaultScore->source_notes);
        $this->assertStringContainsString('recency: no paid transaction', $defaultScore->source_notes);
    }

    public function test_recalculation_updates_the_existing_customer_score(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');
        $manager = $this->createUser('management');
        $customer = $this->createCustomer('Repeat', 'Customer');
        $this->createTransaction($customer, Transaction::STATUS_PAID, 500, now()->subDays(200));

        $this->actingAs($manager)->post(route('management.rfm.recalculate'));
        $firstScoreId = CustomerRfmScore::whereBelongsTo($customer)->firstOrFail()->id;

        for ($index = 0; $index < 9; $index++) {
            $this->createTransaction($customer, Transaction::STATUS_PAID, 2500, now()->subDays(5), now()->subDays(5));
        }

        $this->post(route('management.rfm.recalculate'))
            ->assertSessionHas('success', 'RFM scores recalculated for 1 customers (0 created, 1 updated).');

        $score = CustomerRfmScore::whereBelongsTo($customer)->firstOrFail();
        $this->assertSame($firstScoreId, $score->id);
        $this->assertDatabaseCount('customer_rfm_scores', 1);
        $this->assertSame([5, 5, 5], [$score->recency_score, $score->frequency_score, $score->monetary_score]);
        $this->assertSame(CustomerRfmScore::SEGMENT_CHAMPION, $score->segment_label);
    }

    public function test_raw_values_produce_ordered_recency_frequency_and_monetary_scores(): void
    {
        Carbon::setTestNow('2026-06-21 12:00:00');
        $recent = $this->createCustomer('Recent', 'Customer');
        $older = $this->createCustomer('Older', 'Customer');
        $frequent = $this->createCustomer('Frequent', 'Customer');
        $lowSpend = $this->createCustomer('Low Spend', 'Customer');
        $highSpend = $this->createCustomer('High Spend', 'Customer');

        $this->createTransaction($recent, Transaction::STATUS_PAID, 500, now()->subDays(5), now()->subDays(5));
        $this->createTransaction($older, Transaction::STATUS_PAID, 500, now()->subDays(200), now()->subDays(200));
        for ($index = 0; $index < 4; $index++) {
            $this->createTransaction($frequent, Transaction::STATUS_PAID, 500, now()->subDays(5), now()->subDays(5));
        }
        $this->createTransaction($lowSpend, Transaction::STATUS_PAID, 999, now()->subDays(5), now()->subDays(5));
        $this->createTransaction($highSpend, Transaction::STATUS_PAID, 20000, now()->subDays(5), now()->subDays(5));

        app(CustomerRfmScorer::class)->recalculate();

        $this->assertGreaterThan($older->rfmScores()->firstOrFail()->recency_score, $recent->rfmScores()->firstOrFail()->recency_score);
        $this->assertGreaterThan($recent->rfmScores()->firstOrFail()->frequency_score, $frequent->rfmScores()->firstOrFail()->frequency_score);
        $this->assertGreaterThan($lowSpend->rfmScores()->firstOrFail()->monetary_score, $highSpend->rfmScores()->firstOrFail()->monetary_score);
    }

    public function test_segment_rules_assign_every_supported_label(): void
    {
        $scorer = app(CustomerRfmScorer::class);

        $this->assertSame(CustomerRfmScore::SEGMENT_CHAMPION, $scorer->segment(5, 5, 5));
        $this->assertSame(CustomerRfmScore::SEGMENT_LOYAL_CUSTOMER, $scorer->segment(3, 4, 3));
        $this->assertSame(CustomerRfmScore::SEGMENT_POTENTIAL_LOYALIST, $scorer->segment(4, 2, 1));
        $this->assertSame(CustomerRfmScore::SEGMENT_NEEDS_ATTENTION, $scorer->segment(3, 2, 1));
        $this->assertSame(CustomerRfmScore::SEGMENT_AT_RISK, $scorer->segment(2, 3, 1));
        $this->assertSame(CustomerRfmScore::SEGMENT_NEW_LOW_ACTIVITY, $scorer->segment(5, 1, 1));
    }

    public function test_segment_filter_only_displays_matching_customers(): void
    {
        $manager = $this->createUser('management');
        $champion = $this->createCustomer('Visible', 'Champion');
        $lowActivity = $this->createCustomer('Hidden', 'Low Activity');
        $this->createScore($champion, CustomerRfmScore::SEGMENT_CHAMPION, 5, 5, 5);
        $this->createScore($lowActivity, CustomerRfmScore::SEGMENT_NEW_LOW_ACTIVITY, 1, 1, 1);

        $this->actingAs($manager)
            ->get(route('management.rfm.index', ['segment' => CustomerRfmScore::SEGMENT_CHAMPION]))
            ->assertOk()
            ->assertSee('Visible Champion')
            ->assertDontSee('Hidden Low Activity')
            ->assertSee('value="Champion" selected', false);
    }

    private function createUser(string $roleName): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName],
            ['display_name' => ucfirst($roleName)],
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createCustomer(string $firstName, string $lastName, bool $active = true): CustomerProfile
    {
        return CustomerProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower(str_replace(' ', '.', $firstName.'.'.$lastName)).'@example.test',
            'is_active' => $active,
        ]);
    }

    private function createTransaction(
        CustomerProfile $customer,
        string $status,
        float $amount,
        Carbon $transactionDate,
        ?Carbon $paidAt = null,
    ): Transaction {
        return Transaction::create([
            'customer_profile_id' => $customer->id,
            'subtotal' => $amount,
            'discount_amount' => 0,
            'total_amount' => $amount,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => $status,
            'paid_at' => $paidAt,
            'transaction_date' => $transactionDate,
        ]);
    }

    private function createScore(
        CustomerProfile $customer,
        string $segment,
        int $recency,
        int $frequency,
        int $monetary,
    ): CustomerRfmScore {
        return CustomerRfmScore::create([
            'customer_profile_id' => $customer->id,
            'recency_score' => $recency,
            'frequency_score' => $frequency,
            'monetary_score' => $monetary,
            'segment_label' => $segment,
            'calculated_at' => today(),
            'source_notes' => 'Test source values.',
        ]);
    }
}
