<?php

namespace App\Services;

use App\Models\CustomerProfile;
use App\Models\CustomerRfmScore;
use App\Models\Transaction;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CustomerRfmScorer
{
    /**
     * @return array{processed: int, created: int, updated: int, segments: array<string, int>}
     */
    public function recalculate(?CarbonInterface $calculatedAt = null): array
    {
        $calculatedAt = Carbon::instance($calculatedAt ?? now());
        $transactionValues = Transaction::query()
            ->where('payment_status', Transaction::STATUS_PAID)
            ->whereNotNull('customer_profile_id')
            ->select('customer_profile_id')
            ->selectRaw('COUNT(*) as paid_transaction_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_paid_spend')
            ->selectRaw('MAX(COALESCE(paid_at, transaction_date)) as latest_paid_at')
            ->groupBy('customer_profile_id')
            ->get()
            ->keyBy('customer_profile_id');

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'segments' => array_fill_keys(CustomerRfmScore::SEGMENTS, 0),
        ];

        DB::transaction(function () use ($calculatedAt, $transactionValues, &$summary): void {
            CustomerProfile::query()
                ->orderBy('id')
                ->chunkById(200, function ($customers) use ($calculatedAt, $transactionValues, &$summary): void {
                    foreach ($customers as $customer) {
                        $raw = $transactionValues->get($customer->id);
                        $frequency = (int) ($raw?->paid_transaction_count ?? 0);
                        $monetary = (float) ($raw?->total_paid_spend ?? 0);
                        $latestPaidAt = $raw?->latest_paid_at
                            ? Carbon::parse($raw->latest_paid_at)
                            : null;
                        $recencyDays = $latestPaidAt
                            ? (int) $latestPaidAt->copy()->startOfDay()->diffInDays($calculatedAt->copy()->startOfDay(), true)
                            : null;

                        $recencyScore = $this->recencyScore($recencyDays);
                        $frequencyScore = $this->frequencyScore($frequency);
                        $monetaryScore = $this->monetaryScore($monetary);
                        $segment = $this->segment($recencyScore, $frequencyScore, $monetaryScore);

                        $score = CustomerRfmScore::updateOrCreate(
                            ['customer_profile_id' => $customer->id],
                            [
                                'recency_score' => $recencyScore,
                                'frequency_score' => $frequencyScore,
                                'monetary_score' => $monetaryScore,
                                'segment_label' => $segment,
                                'calculated_at' => $calculatedAt->toDateString(),
                                'source_notes' => $this->sourceNotes($frequency, $monetary, $latestPaidAt, $recencyDays),
                            ],
                        );

                        $summary['processed']++;
                        $summary[$score->wasRecentlyCreated ? 'created' : 'updated']++;
                        $summary['segments'][$segment]++;
                    }
                });
        });

        return $summary;
    }

    public function recencyScore(?int $days): int
    {
        return match (true) {
            $days === null, $days > 180 => 1,
            $days <= 30 => 5,
            $days <= 60 => 4,
            $days <= 90 => 3,
            default => 2,
        };
    }

    public function frequencyScore(int $count): int
    {
        return match (true) {
            $count >= 10 => 5,
            $count >= 7 => 4,
            $count >= 4 => 3,
            $count >= 2 => 2,
            default => 1,
        };
    }

    public function monetaryScore(float $amount): int
    {
        return match (true) {
            $amount >= 20000 => 5,
            $amount >= 10000 => 4,
            $amount >= 5000 => 3,
            $amount >= 1000 => 2,
            default => 1,
        };
    }

    public function segment(int $recency, int $frequency, int $monetary): string
    {
        return match (true) {
            $recency >= 4 && $frequency >= 4 && $monetary >= 4 => CustomerRfmScore::SEGMENT_CHAMPION,
            $frequency >= 4 && $monetary >= 3 => CustomerRfmScore::SEGMENT_LOYAL_CUSTOMER,
            $recency >= 4 && $frequency >= 2 => CustomerRfmScore::SEGMENT_POTENTIAL_LOYALIST,
            $recency >= 3 && $frequency >= 2 => CustomerRfmScore::SEGMENT_NEEDS_ATTENTION,
            $recency <= 2 && $frequency >= 3 => CustomerRfmScore::SEGMENT_AT_RISK,
            default => CustomerRfmScore::SEGMENT_NEW_LOW_ACTIVITY,
        };
    }

    private function sourceNotes(
        int $frequency,
        float $monetary,
        ?CarbonInterface $latestPaidAt,
        ?int $recencyDays,
    ): string {
        $latest = $latestPaidAt?->format('Y-m-d H:i:s') ?? 'none';
        $recency = $recencyDays === null ? 'no paid transaction' : $recencyDays.' days';

        return sprintf(
            'Paid transactions: %d; total paid spend: PHP %s; latest paid transaction: %s; recency: %s.',
            $frequency,
            number_format($monetary, 2, '.', ','),
            $latest,
            $recency,
        );
    }
}
