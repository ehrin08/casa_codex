<?php

namespace Tests\Feature\Management;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\CustomerRfmScore;
use App\Models\Promotion;
use App\Models\Service;
use App\Services\PromotionEngine;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_engine_recommends_an_active_promotion_matching_the_latest_rfm_segment(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment, [
            'segment_label' => CustomerRfmScore::SEGMENT_AT_RISK,
            'calculated_at' => '2026-06-20',
        ]);
        $this->createScore($appointment, [
            'segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'calculated_at' => '2026-06-21',
        ]);
        $promotion = $this->createPromotion(['title' => 'Latest Champion Offer']);

        $recommendations = $this->engine()->recommendationsForAppointment(
            $appointment,
            CarbonImmutable::parse('2026-06-21 12:00:00'),
        );

        $this->assertCount(1, $recommendations);
        $this->assertTrue($recommendations[0]['eligible']);
        $this->assertTrue($recommendations[0]['criteria']['segment']);
        $this->assertSame($promotion->id, $recommendations[0]['promotion']->id);
        $this->assertSame('100.00', $recommendations[0]['discount_amount']);
    }

    public function test_engine_rejects_inactive_and_draft_promotions(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment);

        foreach ([Promotion::STATUS_INACTIVE, Promotion::STATUS_DRAFT] as $status) {
            $evaluation = $this->engine()->evaluate(
                $this->createPromotion(['status' => $status]),
                $appointment,
            );

            $this->assertFalse($evaluation['eligible']);
            $this->assertFalse($evaluation['criteria']['status']);
            $this->assertSame('Promotion is not active.', $evaluation['reason']);
        }
    }

    public function test_engine_rejects_promotions_before_the_start_and_after_the_end_date(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment);
        $asOf = CarbonImmutable::parse('2026-06-21 12:00:00');

        $beforeStart = $this->engine()->evaluate($this->createPromotion([
            'starts_at' => '2026-06-22 00:00:00',
        ]), $appointment, $asOf);
        $afterEnd = $this->engine()->evaluate($this->createPromotion([
            'ends_at' => '2026-06-20 23:59:59',
        ]), $appointment, $asOf);
        $onInclusiveBoundaries = $this->engine()->evaluate($this->createPromotion([
            'starts_at' => $asOf,
            'ends_at' => $asOf,
        ]), $appointment, $asOf);

        $this->assertFalse($beforeStart['eligible']);
        $this->assertFalse($beforeStart['criteria']['date_window']);
        $this->assertFalse($afterEnd['eligible']);
        $this->assertFalse($afterEnd['criteria']['date_window']);
        $this->assertTrue($onInclusiveBoundaries['eligible']);
    }

    public function test_engine_rejects_a_non_matching_rfm_segment(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment, ['segment_label' => CustomerRfmScore::SEGMENT_AT_RISK]);

        $evaluation = $this->engine()->evaluate($this->createPromotion([
            'rfm_segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
        ]), $appointment);

        $this->assertFalse($evaluation['eligible']);
        $this->assertFalse($evaluation['criteria']['segment']);
        $this->assertStringContainsString('segment does not match', $evaluation['reason']);
    }

    public function test_engine_rejects_each_unmet_rfm_score_threshold(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment, [
            'recency_score' => 4,
            'frequency_score' => 4,
            'monetary_score' => 4,
        ]);

        foreach ([
            'recency' => 'rule_min_recency_score',
            'frequency' => 'rule_min_frequency_score',
            'monetary' => 'rule_min_monetary_score',
        ] as $criterion => $field) {
            $evaluation = $this->engine()->evaluate(
                $this->createPromotion([$field => 5]),
                $appointment,
            );

            $this->assertFalse($evaluation['eligible']);
            $this->assertFalse($evaluation['criteria'][$criterion]);
        }
    }

    public function test_combined_segment_and_threshold_criteria_use_and_logic(): void
    {
        $appointment = $this->createAppointment();
        $score = $this->createScore($appointment, [
            'recency_score' => 5,
            'frequency_score' => 4,
            'monetary_score' => 4,
        ]);
        $promotion = $this->createPromotion([
            'rfm_segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'rule_min_recency_score' => 5,
            'rule_min_frequency_score' => 4,
            'rule_min_monetary_score' => 4,
        ]);

        $eligible = $this->engine()->evaluate($promotion, $appointment);
        $this->assertTrue($eligible['eligible']);
        $this->assertStringContainsString('meets all minimum RFM thresholds', $eligible['reason']);

        $score->update(['monetary_score' => 3]);
        $ineligible = $this->engine()->evaluate($promotion, $appointment);

        $this->assertFalse($ineligible['eligible']);
        $this->assertFalse($ineligible['criteria']['monetary']);
    }

    public function test_engine_calculates_percentage_and_fixed_discounts_in_cents(): void
    {
        $percentage = $this->createPromotion([
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 12.5,
        ]);
        $fixed = $this->createPromotion([
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 123.45,
        ]);

        $this->assertSame(12500, $this->engine()->calculateDiscount($percentage, 100000));
        $this->assertSame(12345, $this->engine()->calculateDiscount($fixed, 100000));
    }

    public function test_engine_rounds_percentage_discounts_and_caps_discounts_at_subtotal(): void
    {
        $percentage = $this->createPromotion([
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
        ]);
        $oversizedFixed = $this->createPromotion([
            'discount_type' => Promotion::DISCOUNT_TYPE_FIXED,
            'discount_value' => 1500,
        ]);

        $this->assertSame(10000, $this->engine()->calculateDiscount($percentage, 99999));
        $this->assertSame(100000, $this->engine()->calculateDiscount($oversizedFixed, 100000));
    }

    public function test_engine_supports_optional_service_targeting_in_rule_payload(): void
    {
        $appointment = $this->createAppointment();
        $this->createScore($appointment);

        $matching = $this->engine()->evaluate($this->createPromotion([
            'rule_payload' => ['service_ids' => [$appointment->service_id]],
        ]), $appointment);
        $notMatching = $this->engine()->evaluate($this->createPromotion([
            'rule_payload' => ['service_ids' => [$appointment->service_id + 100]],
        ]), $appointment);
        $unrestricted = $this->engine()->evaluate($this->createPromotion([
            'rule_payload' => ['service_ids' => []],
        ]), $appointment);

        $this->assertTrue($matching['eligible']);
        $this->assertTrue($matching['criteria']['service']);
        $this->assertFalse($notMatching['eligible']);
        $this->assertFalse($notMatching['criteria']['service']);
        $this->assertTrue($unrestricted['eligible']);
    }

    public function test_engine_requires_a_customer_and_latest_rfm_score(): void
    {
        $appointment = $this->createAppointment();
        $promotion = $this->createPromotion();

        $withoutScore = $this->engine()->evaluate($promotion, $appointment);
        $this->assertFalse($withoutScore['eligible']);
        $this->assertFalse($withoutScore['criteria']['rfm_score']);

        $appointment->update(['customer_profile_id' => null]);
        $withoutCustomer = $this->engine()->evaluate($promotion, $appointment->fresh());
        $this->assertFalse($withoutCustomer['eligible']);
        $this->assertFalse($withoutCustomer['criteria']['customer']);
    }

    private function engine(): PromotionEngine
    {
        return app(PromotionEngine::class);
    }

    private function createAppointment(): Appointment
    {
        $customer = CustomerProfile::create([
            'first_name' => 'Promotion',
            'last_name' => 'Customer',
            'is_active' => true,
        ]);
        $service = Service::create([
            'name' => 'Promotion Test Service',
            'duration_minutes' => 60,
            'price' => 1000,
            'status' => 'active',
        ]);

        return Appointment::create([
            'customer_profile_id' => $customer->id,
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createScore(Appointment $appointment, array $overrides = []): CustomerRfmScore
    {
        return CustomerRfmScore::create(array_merge([
            'customer_profile_id' => $appointment->customer_profile_id,
            'recency_score' => 5,
            'frequency_score' => 5,
            'monetary_score' => 5,
            'segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'calculated_at' => '2026-06-21',
            'source_notes' => 'Promotion engine test score.',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPromotion(array $overrides = []): Promotion
    {
        return Promotion::create(array_merge([
            'title' => 'Champion Promotion',
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENTAGE,
            'discount_value' => 10,
            'rfm_segment_label' => CustomerRfmScore::SEGMENT_CHAMPION,
            'status' => Promotion::STATUS_ACTIVE,
        ], $overrides));
    }
}
