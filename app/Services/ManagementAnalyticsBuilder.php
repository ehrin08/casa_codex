<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\CustomerReview;
use App\Models\CustomerRfmScore;
use App\Models\PromotionUsage;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ManagementAnalyticsBuilder
{
    /**
     * @param  array<string, int|string|null>  $filters
     * @return array<string, mixed>
     */
    public function build(array $filters = []): array
    {
        $today = CarbonImmutable::today();
        $filters = array_merge([
            'range' => 'month',
            'date_from' => $today->startOfMonth()->toDateString(),
            'date_to' => $today->endOfMonth()->toDateString(),
            'service_id' => null,
            'therapist_profile_id' => null,
        ], $filters);
        $dateFrom = CarbonImmutable::parse((string) $filters['date_from']);
        $dateTo = CarbonImmutable::parse((string) $filters['date_to']);

        $appointments = $this->appointmentQuery($filters)
            ->with(['service', 'therapistProfile'])
            ->whereBetween('appointment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get();

        $transactions = $this->transactionQuery($filters)
            ->with('appointment.service')
            ->whereBetween('transaction_date', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->get();

        $revenue = $this->revenue($transactions);
        $services = $this->servicePopularity($appointments, $transactions);
        $bookingPeriods = $this->bookingPeriods($appointments);
        $rfm = $this->rfm($filters, $appointments, $dateFrom, $dateTo);
        $promotions = $this->promotions($filters, $dateFrom, $dateTo);
        $reviews = $this->reviews($filters, $dateFrom, $dateTo);

        return [
            'filters' => $filters,
            'revenue' => $revenue,
            'services' => $services,
            'bookingPeriods' => $bookingPeriods,
            'rfm' => $rfm,
            'promotions' => $promotions,
            'reviews' => $reviews,
            'hasData' => $transactions->isNotEmpty()
                || $appointments->isNotEmpty()
                || $rfm['total'] > 0
                || $promotions['usage_count'] > 0
                || ($reviews['available'] && $reviews['total'] > 0),
        ];
    }

    /** @param array<string, int|string|null> $filters */
    private function appointmentQuery(array $filters): Builder
    {
        return Appointment::query()
            ->when($filters['service_id'] ?? null, fn (Builder $query, int $serviceId) => $query->where('service_id', $serviceId))
            ->when(
                $filters['therapist_profile_id'] ?? null,
                fn (Builder $query, int $therapistId) => $query->where('therapist_profile_id', $therapistId),
            );
    }

    /** @param array<string, int|string|null> $filters */
    private function transactionQuery(array $filters): Builder
    {
        return Transaction::query()
            ->when($filters['service_id'] ?? null, function (Builder $query, int $serviceId): void {
                $query->whereHas('appointment', fn (Builder $appointment) => $appointment->where('service_id', $serviceId));
            })
            ->when($filters['therapist_profile_id'] ?? null, function (Builder $query, int $therapistId): void {
                $query->whereHas(
                    'appointment',
                    fn (Builder $appointment) => $appointment->where('therapist_profile_id', $therapistId),
                );
            });
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array<string, mixed>
     */
    private function revenue(Collection $transactions): array
    {
        $paid = $transactions->where('payment_status', Transaction::STATUS_PAID);
        $pending = $transactions->where('payment_status', Transaction::STATUS_PENDING);
        $void = $transactions->where('payment_status', Transaction::STATUS_VOID);

        $daily = $paid
            ->groupBy(fn (Transaction $transaction): string => $transaction->transaction_date->toDateString())
            ->map(fn (Collection $day, string $date): array => [
                'date' => $date,
                'count' => $day->count(),
                'net_revenue' => $this->sumMoney($day, 'total_amount'),
            ])
            ->sortBy('date')
            ->values();

        return [
            'gross_subtotal' => $this->sumMoney($paid, 'subtotal'),
            'discount_total' => $this->sumMoney($paid, 'discount_amount'),
            'net_revenue' => $this->sumMoney($paid, 'total_amount'),
            'paid_count' => $paid->count(),
            'pending_count' => $pending->count(),
            'pending_total' => $this->sumMoney($pending, 'total_amount'),
            'void_count' => $void->count(),
            'void_total' => $this->sumMoney($void, 'total_amount'),
            'daily' => $daily,
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, Transaction>  $transactions
     * @return Collection<int, array<string, float|int|string>>
     */
    private function servicePopularity(Collection $appointments, Collection $transactions): Collection
    {
        $rows = [];

        foreach ($appointments as $appointment) {
            $key = $this->serviceKey($appointment);
            $rows[$key] ??= $this->emptyServiceRow($appointment);
            $rows[$key]['appointment_count']++;

            if ($appointment->status === Appointment::STATUS_COMPLETED) {
                $rows[$key]['completed_count']++;
            }
        }

        foreach ($transactions->where('payment_status', Transaction::STATUS_PAID) as $transaction) {
            $appointment = $transaction->appointment;
            $key = $this->serviceKey($appointment);
            $rows[$key] ??= $this->emptyServiceRow($appointment);
            $rows[$key]['paid_transaction_count']++;
            $rows[$key]['revenue'] += (float) $transaction->total_amount;
        }

        return collect($rows)
            ->map(function (array $row): array {
                $row['revenue'] = round($row['revenue'], 2);
                $row['average_transaction'] = $row['paid_transaction_count'] > 0
                    ? round($row['revenue'] / $row['paid_transaction_count'], 2)
                    : 0.0;

                return $row;
            })
            ->sort(function (array $left, array $right): int {
                return [$right['appointment_count'], $right['revenue'], $left['service']]
                    <=> [$left['appointment_count'], $left['revenue'], $right['service']];
            })
            ->values();
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return array<string, mixed>
     */
    private function bookingPeriods(Collection $appointments): array
    {
        $days = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])
            ->map(fn (string $day): array => [
                'label' => $day,
                'count' => $appointments->filter(
                    fn (Appointment $appointment): bool => $appointment->appointment_date->format('l') === $day,
                )->count(),
            ]);

        $hours = $appointments
            ->groupBy(fn (Appointment $appointment): string => substr((string) $appointment->start_time, 0, 5))
            ->map(fn (Collection $period, string $hour): array => ['label' => $hour, 'count' => $period->count()])
            ->sortBy('label')
            ->values();

        $busiestDates = $appointments
            ->groupBy(fn (Appointment $appointment): string => $appointment->appointment_date->toDateString())
            ->map(fn (Collection $date, string $label): array => ['date' => $label, 'count' => $date->count()])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $statusCounts = $appointments->countBy('status');
        $completed = (int) $statusCounts->get(Appointment::STATUS_COMPLETED, 0);
        $therapistWorkload = $appointments
            ->filter(fn (Appointment $appointment): bool => $appointment->therapist_profile_id !== null)
            ->groupBy('therapist_profile_id')
            ->map(function (Collection $bookings): array {
                $therapist = $bookings->first()->therapistProfile;

                return [
                    'therapist' => $therapist
                        ? trim($therapist->first_name.' '.$therapist->last_name)
                        : 'Therapist unavailable',
                    'count' => $bookings->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        return [
            'days' => $days,
            'hours' => $hours,
            'busiest_dates' => $busiestDates,
            'total' => $appointments->count(),
            'completed' => $completed,
            'cancelled' => (int) $statusCounts->get(Appointment::STATUS_CANCELLED, 0),
            'no_show' => (int) $statusCounts->get(Appointment::STATUS_NO_SHOW, 0),
            'completion_rate' => $appointments->isNotEmpty()
                ? round(($completed / $appointments->count()) * 100, 1)
                : 0.0,
            'therapist_workload' => $therapistWorkload,
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @param  Collection<int, Appointment>  $appointments
     * @return array{total: int, segments: Collection<int, array<string, float|int|string>>}
     */
    private function rfm(
        array $filters,
        Collection $appointments,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
    ): array {
        $query = CustomerRfmScore::query()
            ->whereBetween('calculated_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');

        if (($filters['service_id'] ?? null) || ($filters['therapist_profile_id'] ?? null)) {
            $query->whereIn('customer_profile_id', $appointments->pluck('customer_profile_id')->filter()->unique());
        }

        $latestScores = $query->get()->unique('customer_profile_id');
        $counts = $latestScores->countBy('segment_label');
        $total = $latestScores->count();

        $segments = collect(CustomerRfmScore::SEGMENTS)->map(fn (string $segment): array => [
            'label' => $segment,
            'count' => (int) $counts->get($segment, 0),
            'percentage' => $total > 0 ? round(((int) $counts->get($segment, 0) / $total) * 100, 1) : 0.0,
        ]);

        return ['total' => $total, 'segments' => $segments];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return array<string, mixed>
     */
    private function promotions(array $filters, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $query = PromotionUsage::query()
            ->with(['promotion', 'transaction'])
            ->whereBetween('used_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);

        $this->applyUsageRelationshipFilters($query, $filters);
        $usages = $query->get();

        $top = $usages
            ->groupBy('promotion_id')
            ->map(function (Collection $promotionUsages): array {
                $promotion = $promotionUsages->first()->promotion;
                $paidTransactions = $promotionUsages
                    ->pluck('transaction')
                    ->filter(fn (?Transaction $transaction): bool => $transaction?->payment_status === Transaction::STATUS_PAID)
                    ->unique('id');

                return [
                    'promotion' => $promotion?->title ?? 'Promotion unavailable',
                    'usage_count' => $promotionUsages->count(),
                    'discount_total' => $this->sumMoney($promotionUsages, 'discount_amount'),
                    'paid_revenue' => $this->sumMoney($paidTransactions, 'total_amount'),
                ];
            })
            ->sortByDesc('usage_count')
            ->values()
            ->take(5);

        $paidTransactions = $usages
            ->pluck('transaction')
            ->filter(fn (?Transaction $transaction): bool => $transaction?->payment_status === Transaction::STATUS_PAID)
            ->unique('id');

        return [
            'usage_count' => $usages->count(),
            'discount_total' => $this->sumMoney($usages, 'discount_amount'),
            'paid_revenue' => $this->sumMoney($paidTransactions, 'total_amount'),
            'top' => $top,
        ];
    }

    /**
     * @param  Builder<PromotionUsage>  $query
     * @param  array<string, int|string|null>  $filters
     */
    private function applyUsageRelationshipFilters(Builder $query, array $filters): void
    {
        if ($filters['service_id'] ?? null) {
            $query->whereHas('transaction.appointment', fn (Builder $appointment) => $appointment
                ->where('service_id', $filters['service_id']));
        }

        if ($filters['therapist_profile_id'] ?? null) {
            $query->whereHas('transaction.appointment', fn (Builder $appointment) => $appointment
                ->where('therapist_profile_id', $filters['therapist_profile_id']));
        }
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return array<string, float|int|bool>
     */
    private function reviews(array $filters, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $empty = [
            'available' => false,
            'total' => 0,
            'average_rating' => 0.0,
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
            'recent_negative' => 0,
        ];

        if (! class_exists(CustomerReview::class)
            || ! Schema::hasTable('customer_reviews')
            || ! Route::has('management.reviews.index')) {
            return $empty;
        }

        $query = CustomerReview::query()
            ->whereBetween('reviewed_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->when($filters['service_id'] ?? null, fn (Builder $reviews, int $serviceId) => $reviews
                ->where('service_id', $serviceId))
            ->when($filters['therapist_profile_id'] ?? null, function (Builder $reviews, int $therapistId): void {
                $reviews->whereHas('appointment', fn (Builder $appointment) => $appointment
                    ->where('therapist_profile_id', $therapistId));
            });

        $reviews = $query->get(['id', 'rating', 'sentiment_label', 'reviewed_at']);
        $counts = $reviews->countBy('sentiment_label');
        $recentFrom = $dateTo->subDays(6)->max($dateFrom)->startOfDay();

        return [
            'available' => true,
            'total' => $reviews->count(),
            'average_rating' => round((float) $reviews->avg('rating'), 1),
            'positive' => (int) $counts->get(CustomerReview::SENTIMENT_POSITIVE, 0),
            'neutral' => (int) $counts->get(CustomerReview::SENTIMENT_NEUTRAL, 0),
            'negative' => (int) $counts->get(CustomerReview::SENTIMENT_NEGATIVE, 0),
            'recent_negative' => $reviews->where('sentiment_label', CustomerReview::SENTIMENT_NEGATIVE)
                ->filter(fn (CustomerReview $review): bool => $review->reviewed_at->betweenIncluded($recentFrom, $dateTo->endOfDay()))
                ->count(),
        ];
    }

    private function serviceKey(?Appointment $appointment): string
    {
        if ($appointment?->service_id) {
            return 'service:'.$appointment->service_id;
        }

        return 'snapshot:'.mb_strtolower($this->serviceName($appointment));
    }

    /** @return array{service: string, appointment_count: int, completed_count: int, paid_transaction_count: int, revenue: float} */
    private function emptyServiceRow(?Appointment $appointment): array
    {
        return [
            'service' => $this->serviceName($appointment),
            'appointment_count' => 0,
            'completed_count' => 0,
            'paid_transaction_count' => 0,
            'revenue' => 0.0,
        ];
    }

    private function serviceName(?Appointment $appointment): string
    {
        return $appointment?->service_name_snapshot
            ?: $appointment?->service?->name
            ?: 'Service unavailable';
    }

    /** @param Collection<int, mixed> $records */
    private function sumMoney(Collection $records, string $attribute): float
    {
        $cents = $records->sum(fn ($record): int => (int) round(((float) $record->{$attribute}) * 100));

        return $cents / 100;
    }
}
