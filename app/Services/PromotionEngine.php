<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CustomerRfmScore;
use App\Models\Promotion;
use Carbon\CarbonInterface;

class PromotionEngine
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function recommendationsForAppointment(
        Appointment $appointment,
        ?CarbonInterface $asOf = null,
    ): array {
        return Promotion::query()
            ->where('status', Promotion::STATUS_ACTIVE)
            ->orderBy('title')
            ->get()
            ->map(fn (Promotion $promotion): array => $this->evaluate($promotion, $appointment, $asOf))
            ->filter(fn (array $evaluation): bool => $evaluation['eligible'])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(
        Promotion $promotion,
        Appointment $appointment,
        ?CarbonInterface $asOf = null,
    ): array {
        $evaluationDate = $asOf ?? now();
        $rfmScore = $this->latestRfmScore($appointment);
        $subtotalCents = $this->subtotalCents($appointment);
        $discountCents = $this->calculateDiscount($promotion, $subtotalCents);

        $criteria = [
            'status' => $promotion->status === Promotion::STATUS_ACTIVE,
            'date_window' => $this->isWithinDateWindow($promotion, $evaluationDate),
            'customer' => $appointment->customer_profile_id !== null,
            'rfm_score' => $rfmScore !== null,
            'segment' => $promotion->rfm_segment_label === null
                || $rfmScore?->segment_label === $promotion->rfm_segment_label,
            'recency' => $promotion->rule_min_recency_score === null
                || ($rfmScore !== null && $rfmScore->recency_score >= $promotion->rule_min_recency_score),
            'frequency' => $promotion->rule_min_frequency_score === null
                || ($rfmScore !== null && $rfmScore->frequency_score >= $promotion->rule_min_frequency_score),
            'monetary' => $promotion->rule_min_monetary_score === null
                || ($rfmScore !== null && $rfmScore->monetary_score >= $promotion->rule_min_monetary_score),
            'service' => $this->matchesServiceTarget($promotion, $appointment),
            'discount' => $discountCents > 0 && $discountCents <= $subtotalCents,
        ];

        $eligible = ! in_array(false, $criteria, true);

        return [
            'promotion' => $promotion,
            'eligible' => $eligible,
            'discount_amount' => $this->fromCents($discountCents),
            'discount_cents' => $discountCents,
            'reason' => $eligible
                ? $this->eligibleReason($promotion)
                : $this->ineligibleReason($criteria),
            'criteria' => $criteria,
            'rfm_score' => $rfmScore,
        ];
    }

    public function calculateDiscount(Promotion $promotion, int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        $discountCents = match ($promotion->discount_type) {
            Promotion::DISCOUNT_TYPE_PERCENTAGE => (int) round(
                $subtotalCents * $this->percentageBasisPoints($promotion->discount_value) / 10000,
            ),
            Promotion::DISCOUNT_TYPE_FIXED => $this->toCents($promotion->discount_value),
            default => 0,
        };

        return min($subtotalCents, max(0, $discountCents));
    }

    private function latestRfmScore(Appointment $appointment): ?CustomerRfmScore
    {
        if ($appointment->customer_profile_id === null) {
            return null;
        }

        return CustomerRfmScore::query()
            ->where('customer_profile_id', $appointment->customer_profile_id)
            ->orderByDesc('calculated_at')
            ->orderByDesc('id')
            ->first();
    }

    private function isWithinDateWindow(Promotion $promotion, CarbonInterface $asOf): bool
    {
        if ($promotion->starts_at !== null && $asOf->lt($promotion->starts_at)) {
            return false;
        }

        return $promotion->ends_at === null || ! $asOf->gt($promotion->ends_at);
    }

    private function matchesServiceTarget(Promotion $promotion, Appointment $appointment): bool
    {
        $payload = $promotion->rule_payload;

        if ($payload === null) {
            return true;
        }

        if (! is_array($payload)) {
            return false;
        }

        if (! array_key_exists('service_ids', $payload)) {
            return true;
        }

        $serviceIds = $payload['service_ids'];

        if ($serviceIds === []) {
            return true;
        }

        if (! is_array($serviceIds) || $appointment->service_id === null) {
            return false;
        }

        return in_array(
            $appointment->service_id,
            array_map('intval', array_filter($serviceIds, 'is_numeric')),
            true,
        );
    }

    private function eligibleReason(Promotion $promotion): string
    {
        $hasThreshold = $promotion->rule_min_recency_score !== null
            || $promotion->rule_min_frequency_score !== null
            || $promotion->rule_min_monetary_score !== null;

        if ($promotion->rfm_segment_label !== null && $hasThreshold) {
            return "Customer matches the {$promotion->rfm_segment_label} segment and meets all minimum RFM thresholds.";
        }

        if ($promotion->rfm_segment_label !== null) {
            return "Customer matches the {$promotion->rfm_segment_label} RFM segment.";
        }

        return 'Customer meets all configured minimum RFM thresholds.';
    }

    /**
     * @param  array<string, bool>  $criteria
     */
    private function ineligibleReason(array $criteria): string
    {
        $reasons = [
            'status' => 'Promotion is not active.',
            'date_window' => 'Promotion is outside its active date window.',
            'customer' => 'Appointment has no customer profile.',
            'rfm_score' => 'Customer does not have an RFM score.',
            'segment' => 'Customer RFM segment does not match.',
            'recency' => 'Customer does not meet the minimum recency score.',
            'frequency' => 'Customer does not meet the minimum frequency score.',
            'monetary' => 'Customer does not meet the minimum monetary score.',
            'service' => 'Appointment service is not targeted by this promotion.',
            'discount' => 'Promotion does not produce a valid discount for this subtotal.',
        ];

        foreach ($criteria as $criterion => $passed) {
            if (! $passed) {
                return $reasons[$criterion];
            }
        }

        return 'Promotion is not eligible.';
    }

    private function subtotalCents(Appointment $appointment): int
    {
        $appointment->loadMissing('service');
        $subtotal = $appointment->service_price_snapshot ?? $appointment->service?->price;

        return $subtotal === null ? 0 : $this->toCents($subtotal);
    }

    private function percentageBasisPoints(mixed $percentage): int
    {
        return (int) round(((float) $percentage) * 100);
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
