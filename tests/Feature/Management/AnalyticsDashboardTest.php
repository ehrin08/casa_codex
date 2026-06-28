<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerReview;
use App\Models\CustomerRfmScore;
use App\Models\Promotion;
use App\Models\PromotionUsage;
use App\Models\Role;
use App\Models\Service;
use App\Models\TherapistProfile;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_is_management_only_and_linked_from_management_navigation(): void
    {
        $this->get(route('management.analytics.index'))->assertRedirect('/login');

        foreach (['customer', 'therapist'] as $role) {
            $this->actingAs($this->createUser($role))
                ->get(route('management.analytics.index'))
                ->assertForbidden();
        }

        $manager = $this->createUser('management');
        $this->actingAs($manager)
            ->get(route('management.analytics.index'))
            ->assertOk()
            ->assertSee('Analytics Dashboard')
            ->assertSee('href="'.route('management.analytics.index').'"', false);

        $this->get(route('management.index'))
            ->assertOk()
            ->assertSee('href="'.route('management.analytics.index').'"', false);
    }

    public function test_revenue_counts_only_paid_transactions_and_separates_pending_and_void_totals(): void
    {
        $manager = $this->createUser('management');
        $service = $this->createService('Revenue Hilot');
        $therapist = $this->createTherapist('Revenue Therapist');

        $paid = $this->createAppointment($service, $therapist, '2026-06-10', '09:00');
        $pending = $this->createAppointment($service, $therapist, '2026-06-10', '10:00');
        $void = $this->createAppointment($service, $therapist, '2026-06-10', '11:00');

        $this->createTransaction($paid, '2026-06-10 12:00:00', Transaction::STATUS_PAID, 1000, 100, 900);
        $this->createTransaction($pending, '2026-06-10 12:30:00', Transaction::STATUS_PENDING, 500, 0, 500);
        $this->createTransaction($void, '2026-06-10 13:00:00', Transaction::STATUS_VOID, 700, 50, 650);

        $this->actingAs($manager)
            ->get($this->analyticsUrl())
            ->assertOk()
            ->assertSee('Pending')
            ->assertSee('Void')
            ->assertViewHas('analytics', function (array $analytics): bool {
                return $analytics['revenue']['gross_subtotal'] === 1000.0
                    && $analytics['revenue']['discount_total'] === 100.0
                    && $analytics['revenue']['net_revenue'] === 900.0
                    && $analytics['revenue']['paid_count'] === 1
                    && $analytics['revenue']['pending_count'] === 1
                    && $analytics['revenue']['pending_total'] === 500.0
                    && $analytics['revenue']['void_count'] === 1
                    && $analytics['revenue']['void_total'] === 650.0;
            });
    }

    public function test_service_popularity_and_peak_booking_periods_are_ranked_and_grouped(): void
    {
        $manager = $this->createUser('management');
        $therapist = $this->createTherapist('Trend Therapist');
        $popular = $this->createService('Popular Hilot');
        $premium = $this->createService('Premium Facial');

        $first = $this->createAppointment($popular, $therapist, '2026-06-11', '09:00');
        $second = $this->createAppointment($popular, $therapist, '2026-06-11', '09:00');
        $third = $this->createAppointment($premium, $therapist, '2026-06-12', '10:00');
        $this->createTransaction($first, '2026-06-11 11:00:00', Transaction::STATUS_PAID, 900, 0, 900);
        $this->createTransaction($third, '2026-06-12 11:00:00', Transaction::STATUS_PAID, 1500, 0, 1500);

        $this->actingAs($manager)
            ->get($this->analyticsUrl())
            ->assertOk()
            ->assertSeeInOrder(['Popular Hilot', 'Premium Facial'])
            ->assertSee('09:00')
            ->assertSee('10:00')
            ->assertViewHas('analytics', function (array $analytics) use ($second): bool {
                $popular = $analytics['services']->first();
                $thursday = $analytics['bookingPeriods']['days']->firstWhere('label', $second->appointment_date->format('l'));
                $nine = $analytics['bookingPeriods']['hours']->firstWhere('label', '09:00');

                return $popular['service'] === 'Popular Hilot'
                    && $popular['appointment_count'] === 2
                    && $popular['completed_count'] === 2
                    && $thursday['count'] === 2
                    && $nine['count'] === 2
                    && $analytics['bookingPeriods']['total'] === 3;
            });
    }

    public function test_rfm_distribution_and_promotion_usage_summaries_render(): void
    {
        $manager = $this->createUser('management');
        $service = $this->createService('Segment Massage');
        $therapist = $this->createTherapist('Segment Therapist');
        $appointment = $this->createAppointment($service, $therapist, '2026-06-15', '13:00');
        $transaction = $this->createTransaction(
            $appointment,
            '2026-06-15 14:00:00',
            Transaction::STATUS_PAID,
            1000,
            100,
            900,
        );

        CustomerRfmScore::create([
            'customer_profile_id' => $appointment->customer_profile_id,
            'recency_score' => 5,
            'frequency_score' => 5,
            'monetary_score' => 5,
            'segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'calculated_at' => '2026-06-15',
        ]);

        $promotion = Promotion::create([
            'title' => 'Champion Wellness Credit',
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 100,
            'status' => Promotion::STATUS_ACTIVE,
        ]);
        PromotionUsage::create([
            'promotion_id' => $promotion->id,
            'transaction_id' => $transaction->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'discount_amount' => 100,
            'used_at' => '2026-06-15 14:00:00',
        ]);

        $this->actingAs($manager)
            ->get($this->analyticsUrl())
            ->assertOk()
            ->assertSee('Champion')
            ->assertSee('Champion Wellness Credit')
            ->assertViewHas('analytics', function (array $analytics): bool {
                $champion = $analytics['rfm']['segments']->firstWhere('label', CustomerRfmScore::SEGMENT_CHAMPION);

                return $analytics['rfm']['total'] === 1
                    && $champion['count'] === 1
                    && $champion['percentage'] === 100.0
                    && $analytics['promotions']['usage_count'] === 1
                    && $analytics['promotions']['discount_total'] === 100.0
                    && $analytics['promotions']['paid_revenue'] === 900.0;
            });
    }

    public function test_review_and_sentiment_snapshot_renders_when_ncp_106_data_exists(): void
    {
        $manager = $this->createUser('management');
        $service = $this->createService('Review Facial');
        $therapist = $this->createTherapist('Review Therapist');
        $positiveAppointment = $this->createAppointment($service, $therapist, '2026-06-18', '09:00');
        $negativeAppointment = $this->createAppointment($service, $therapist, '2026-06-19', '10:00');

        $this->createReview($positiveAppointment, 5, CustomerReview::SENTIMENT_POSITIVE, '2026-06-18 12:00:00');
        $this->createReview($negativeAppointment, 1, CustomerReview::SENTIMENT_NEGATIVE, '2026-06-19 12:00:00');

        $this->actingAs($manager)
            ->get($this->analyticsUrl())
            ->assertOk()
            ->assertSee('Review &amp; Sentiment Snapshot', false)
            ->assertViewHas('analytics', fn (array $analytics): bool => $analytics['reviews'] === [
                'available' => true,
                'total' => 2,
                'average_rating' => 3.0,
                'positive' => 1,
                'neutral' => 0,
                'negative' => 1,
                'recent_negative' => 0,
            ]);
    }

    public function test_date_and_relationship_filters_limit_results_and_preserve_values(): void
    {
        $manager = $this->createUser('management');
        $selectedService = $this->createService('Selected Service');
        $otherService = $this->createService('Other Service');
        $selectedTherapist = $this->createTherapist('Selected Therapist');
        $otherTherapist = $this->createTherapist('Other Therapist');

        $selected = $this->createAppointment($selectedService, $selectedTherapist, '2026-06-10', '09:00');
        $other = $this->createAppointment($otherService, $otherTherapist, '2026-06-20', '10:00');
        $this->createTransaction($selected, '2026-06-10 12:00:00', Transaction::STATUS_PAID, 800, 0, 800);
        $this->createTransaction($other, '2026-06-20 12:00:00', Transaction::STATUS_PAID, 2000, 0, 2000);

        $url = route('management.analytics.index', [
            'range' => 'custom',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-15',
            'service_id' => $selectedService->id,
            'therapist_profile_id' => $selectedTherapist->id,
        ]);

        $this->actingAs($manager)
            ->get($url)
            ->assertOk()
            ->assertSee('Selected Service')
            ->assertDontSee('Other Service</td>', false)
            ->assertSee('value="2026-06-01"', false)
            ->assertSee('value="2026-06-15"', false)
            ->assertViewHas('filters', fn (array $filters): bool => $filters === [
                'range' => 'custom',
                'date_from' => '2026-06-01',
                'date_to' => '2026-06-15',
                'service_id' => $selectedService->id,
                'therapist_profile_id' => $selectedTherapist->id,
            ])
            ->assertViewHas('analytics', fn (array $analytics): bool => $analytics['revenue']['net_revenue'] === 800.0
                && $analytics['bookingPeriods']['total'] === 1);
    }

    public function test_empty_filters_render_clean_empty_states_including_empty_review_snapshot(): void
    {
        $this->actingAs($this->createUser('management'))
            ->get(route('management.analytics.index', [
                'range' => 'custom',
                'date_from' => '2030-01-01',
                'date_to' => '2030-01-31',
            ]))
            ->assertOk()
            ->assertSee('No analytics data found')
            ->assertSee('No paid revenue')
            ->assertSee('No service activity')
            ->assertSee('No booking patterns')
            ->assertSee('No customer segment data')
            ->assertSee('No promotion usage')
            ->assertSee('No reviews in this period');
    }

    public function test_custom_range_validation_rejects_missing_or_reversed_dates_without_crashing(): void
    {
        $manager = $this->createUser('management');

        $this->actingAs($manager)
            ->get(route('management.analytics.index', ['range' => 'custom']))
            ->assertSessionHasErrors(['date_from', 'date_to']);

        $this->get(route('management.analytics.index', [
            'range' => 'custom',
            'date_from' => '2026-06-20',
            'date_to' => '2026-06-01',
        ]))->assertSessionHasErrors('date_to');
    }

    private function createUser(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function createService(string $name): Service
    {
        return Service::create([
            'name' => $name,
            'duration_minutes' => 60,
            'price' => 1000,
            'status' => 'active',
        ]);
    }

    private function createTherapist(string $name): TherapistProfile
    {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');

        return TherapistProfile::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'commission_rate' => 20,
            'status' => 'active',
        ]);
    }

    private function createAppointment(
        Service $service,
        TherapistProfile $therapist,
        string $date,
        string $startTime,
    ): Appointment {
        $customer = CustomerProfile::create([
            'first_name' => 'Analytics',
            'last_name' => 'Customer '.CustomerProfile::count(),
            'is_active' => true,
        ]);
        $endTime = Carbon::parse($date.' '.$startTime)->addHour()->format('H:i');

        return Appointment::create([
            'customer_profile_id' => $customer->id,
            'therapist_profile_id' => $therapist->id,
            'service_id' => $service->id,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => Appointment::STATUS_COMPLETED,
            'service_name_snapshot' => $service->name,
            'service_duration_minutes_snapshot' => 60,
            'service_price_snapshot' => $service->price,
        ]);
    }

    private function createTransaction(
        Appointment $appointment,
        string $date,
        string $status,
        float $subtotal,
        float $discount,
        float $total,
    ): Transaction {
        return Transaction::withoutEvents(fn (): Transaction => Transaction::create([
            'appointment_id' => $appointment->id,
            'customer_profile_id' => $appointment->customer_profile_id,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'payment_method' => Transaction::PAYMENT_METHOD_CASH,
            'payment_status' => $status,
            'transaction_date' => $date,
        ]));
    }

    private function createReview(Appointment $appointment, int $rating, string $sentiment, string $reviewedAt): CustomerReview
    {
        return CustomerReview::create([
            'customer_profile_id' => $appointment->customer_profile_id,
            'appointment_id' => $appointment->id,
            'service_id' => $appointment->service_id,
            'rating' => $rating,
            'sentiment_label' => $sentiment,
            'reviewed_at' => $reviewedAt,
        ]);
    }

    private function analyticsUrl(): string
    {
        return route('management.analytics.index', [
            'range' => 'custom',
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]);
    }
}
