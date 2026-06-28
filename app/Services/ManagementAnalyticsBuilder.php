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

        $revenue = $this->revenue($filters, $dateFrom, $dateTo);
        $transactionCount = $revenue['transaction_count'];
        unset($revenue['transaction_count']);

        $services = $this->servicePopularity($filters, $dateFrom, $dateTo);
        $bookingPeriods = $this->bookingPeriods($filters, $dateFrom, $dateTo);
        $rfm = $this->rfm($filters, $this->matchingCustomerIds($filters, $dateFrom, $dateTo), $dateFrom, $dateTo);
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
            'hasData' => $transactionCount > 0
                || $bookingPeriods['total'] > 0
                || $rfm['total'] > 0
                || $promotions['usage_count'] > 0
                || ($reviews['available'] && $reviews['total'] > 0),
        ];
    }

    /** @param array<string, int|string|null> $filters */
    private function appointmentQuery(array $filters): Builder
    {
        return Appointment::query()
            ->when($filters['service_id'] ?? null, fn (Builder $query, int $serviceId) => $query->where('appointments.service_id', $serviceId))
            ->when(
                $filters['therapist_profile_id'] ?? null,
                fn (Builder $query, int $therapistId) => $query->where('appointments.therapist_profile_id', $therapistId),
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

    /** @param array<string, int|string|null> $filters */
    private function revenue(array $filters, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $baseQuery = $this->transactionQuery($filters)
            ->whereBetween('transactions.transaction_date', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);

        $statusRows = (clone $baseQuery)
            ->select('transactions.payment_status')
            ->selectRaw('COUNT(*) as aggregate')
            ->selectRaw('COALESCE(SUM(transactions.subtotal), 0) as subtotal_total')
            ->selectRaw('COALESCE(SUM(transactions.discount_amount), 0) as discount_total')
            ->selectRaw('COALESCE(SUM(transactions.total_amount), 0) as total_amount')
            ->groupBy('transactions.payment_status')
            ->get()
            ->keyBy('payment_status');

        $paid = $statusRows->get(Transaction::STATUS_PAID);
        $pending = $statusRows->get(Transaction::STATUS_PENDING);
        $void = $statusRows->get(Transaction::STATUS_VOID);

        $daily = (clone $baseQuery)
            ->where('transactions.payment_status', Transaction::STATUS_PAID)
            ->selectRaw('DATE(transactions.transaction_date) as date')
            ->selectRaw('COUNT(*) as aggregate')
            ->selectRaw('COALESCE(SUM(transactions.total_amount), 0) as net_revenue')
            ->groupByRaw('DATE(transactions.transaction_date)')
            ->orderBy('date')
            ->get()
            ->map(fn (Transaction $transaction): array => [
                'date' => $transaction->date,
                'count' => (int) $transaction->aggregate,
                'net_revenue' => (float) $transaction->net_revenue,
            ]);

        return [
            'gross_subtotal' => (float) ($paid?->subtotal_total ?? 0),
            'discount_total' => (float) ($paid?->discount_total ?? 0),
            'net_revenue' => (float) ($paid?->total_amount ?? 0),
            'paid_count' => (int) ($paid?->aggregate ?? 0),
            'pending_count' => (int) ($pending?->aggregate ?? 0),
            'pending_total' => (float) ($pending?->total_amount ?? 0),
            'void_count' => (int) ($void?->aggregate ?? 0),
            'void_total' => (float) ($void?->total_amount ?? 0),
            'daily' => $daily,
            'transaction_count' => (int) $statusRows->sum('aggregate'),
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return Collection<int, array<string, float|int|string>>
     */
    private function servicePopularity(array $filters, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): Collection
    {
        $rows = [];
        $serviceLabelSql = "COALESCE(NULLIF(appointments.service_name_snapshot, ''), services.name, 'Service unavailable')";

        $appointmentRows = $this->appointmentQuery($filters)
            ->leftJoin('services', 'services.id', '=', 'appointments.service_id')
            ->whereBetween('appointments.appointment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->select('appointments.service_id')
            ->selectRaw($serviceLabelSql.' as service_label')
            ->selectRaw('COUNT(*) as appointment_count')
            ->selectRaw(
                'SUM(CASE WHEN appointments.status = ? THEN 1 ELSE 0 END) as completed_count',
                [Appointment::STATUS_COMPLETED],
            )
            ->groupBy('appointments.service_id', 'appointments.service_name_snapshot', 'services.name')
            ->get();

        foreach ($appointmentRows as $row) {
            $key = $this->serviceRowKey($row->service_id === null ? null : (int) $row->service_id, $row->service_label);
            $rows[$key] ??= $this->emptyServiceRowFromLabel($row->service_label);
            $rows[$key]['appointment_count'] += (int) $row->appointment_count;
            $rows[$key]['completed_count'] += (int) $row->completed_count;
        }

        $transactionRows = $this->transactionQuery($filters)
            ->join('appointments', 'appointments.id', '=', 'transactions.appointment_id')
            ->leftJoin('services', 'services.id', '=', 'appointments.service_id')
            ->where('transactions.payment_status', Transaction::STATUS_PAID)
            ->whereBetween('transactions.transaction_date', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->select('appointments.service_id')
            ->selectRaw($serviceLabelSql.' as service_label')
            ->selectRaw('COUNT(*) as paid_transaction_count')
            ->selectRaw('COALESCE(SUM(transactions.total_amount), 0) as revenue')
            ->groupBy('appointments.service_id', 'appointments.service_name_snapshot', 'services.name')
            ->get();

        foreach ($transactionRows as $row) {
            $key = $this->serviceRowKey($row->service_id === null ? null : (int) $row->service_id, $row->service_label);
            $rows[$key] ??= $this->emptyServiceRowFromLabel($row->service_label);
            $rows[$key]['paid_transaction_count'] += (int) $row->paid_transaction_count;
            $rows[$key]['revenue'] += (float) $row->revenue;
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

    /** @param array<string, int|string|null> $filters */
    private function bookingPeriods(array $filters, CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $baseQuery = $this->appointmentQuery($filters)
            ->whereBetween('appointments.appointment_date', [$dateFrom->toDateString(), $dateTo->toDateString()]);

        $statusCounts = (clone $baseQuery)
            ->select('appointments.status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('appointments.status')
            ->pluck('aggregate', 'status');

        $dateRows = (clone $baseQuery)
            ->selectRaw('appointments.appointment_date as date')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('appointments.appointment_date')
            ->get();

        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $dayCounts = array_fill_keys($dayNames, 0);

        foreach ($dateRows as $row) {
            $dayCounts[CarbonImmutable::parse($row->date)->format('l')] += (int) $row->aggregate;
        }

        $days = collect($dayNames)
            ->map(fn (string $day): array => [
                'label' => $day,
                'count' => $dayCounts[$day],
            ]);

        $hours = (clone $baseQuery)
            ->select('appointments.start_time')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('appointments.start_time')
            ->orderBy('appointments.start_time')
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'label' => substr((string) $appointment->start_time, 0, 5),
                'count' => (int) $appointment->aggregate,
            ]);

        $busiestDates = $dateRows
            ->map(fn (Appointment $appointment): array => [
                'date' => $appointment->date,
                'count' => (int) $appointment->aggregate,
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        $therapistWorkload = (clone $baseQuery)
            ->leftJoin('therapist_profiles', 'therapist_profiles.id', '=', 'appointments.therapist_profile_id')
            ->whereNotNull('appointments.therapist_profile_id')
            ->select('appointments.therapist_profile_id', 'therapist_profiles.first_name', 'therapist_profiles.last_name')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('appointments.therapist_profile_id', 'therapist_profiles.first_name', 'therapist_profiles.last_name')
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'therapist' => trim(($appointment->first_name ?? '').' '.($appointment->last_name ?? '')) ?: 'Therapist unavailable',
                'count' => (int) $appointment->aggregate,
            ])
            ->sortByDesc('count')
            ->values();

        $total = (int) $statusCounts->sum();
        $completed = (int) $statusCounts->get(Appointment::STATUS_COMPLETED, 0);

        return [
            'days' => $days,
            'hours' => $hours,
            'busiest_dates' => $busiestDates,
            'total' => $total,
            'completed' => $completed,
            'cancelled' => (int) $statusCounts->get(Appointment::STATUS_CANCELLED, 0),
            'no_show' => (int) $statusCounts->get(Appointment::STATUS_NO_SHOW, 0),
            'completion_rate' => $total > 0
                ? round(($completed / $total) * 100, 1)
                : 0.0,
            'therapist_workload' => $therapistWorkload,
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @param  Collection<int, int>  $customerIds
     * @return array{total: int, segments: Collection<int, array<string, float|int|string>>}
     */
    private function rfm(
        array $filters,
        Collection $customerIds,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
    ): array {
        $query = CustomerRfmScore::query()
            ->whereBetween('calculated_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->orderByDesc('calculated_at')
            ->orderByDesc('id');

        if ($this->hasAppointmentRelationshipFilter($filters)) {
            $query->whereIn('customer_profile_id', $customerIds);
        }

        $latestScores = $query
            ->get(['customer_profile_id', 'segment_label', 'calculated_at', 'id'])
            ->unique('customer_profile_id');
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
        $baseQuery = PromotionUsage::query()
            ->whereBetween('promotion_usages.used_at', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);

        $this->applyUsageRelationshipFilters($baseQuery, $filters);

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as usage_count')
            ->selectRaw('COALESCE(SUM(promotion_usages.discount_amount), 0) as discount_total')
            ->first();

        $paidTransactionIds = (clone $baseQuery)
            ->whereHas('transaction', fn (Builder $transaction) => $transaction
                ->where('payment_status', Transaction::STATUS_PAID))
            ->whereNotNull('promotion_usages.transaction_id')
            ->distinct()
            ->pluck('promotion_usages.transaction_id');

        $paidRevenue = $paidTransactionIds->isEmpty()
            ? 0.0
            : (float) Transaction::query()
                ->whereIn('id', $paidTransactionIds)
                ->sum('total_amount');

        $top = (clone $baseQuery)
            ->leftJoin('promotions', 'promotions.id', '=', 'promotion_usages.promotion_id')
            ->leftJoin('transactions', 'transactions.id', '=', 'promotion_usages.transaction_id')
            ->select('promotion_usages.promotion_id', 'promotions.title')
            ->selectRaw('COUNT(*) as usage_count')
            ->selectRaw('COALESCE(SUM(promotion_usages.discount_amount), 0) as discount_total')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN transactions.payment_status = ? THEN transactions.total_amount ELSE 0 END), 0) as paid_revenue',
                [Transaction::STATUS_PAID],
            )
            ->groupBy('promotion_usages.promotion_id', 'promotions.title')
            ->orderByDesc('usage_count')
            ->limit(5)
            ->get()
            ->map(fn (PromotionUsage $usage): array => [
                'promotion' => $usage->title ?? 'Promotion unavailable',
                'usage_count' => (int) $usage->usage_count,
                'discount_total' => (float) $usage->discount_total,
                'paid_revenue' => (float) $usage->paid_revenue,
            ]);

        return [
            'usage_count' => (int) ($summary?->usage_count ?? 0),
            'discount_total' => (float) ($summary?->discount_total ?? 0),
            'paid_revenue' => $paidRevenue,
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

        $recentFrom = $dateTo->subDays(6)->max($dateFrom)->startOfDay();
        $summary = (clone $query)
            ->selectRaw('COUNT(*) as aggregate')
            ->selectRaw('AVG(rating) as average_rating')
            ->first();
        $counts = (clone $query)
            ->select('sentiment_label')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('sentiment_label')
            ->pluck('aggregate', 'sentiment_label');
        $recentNegative = (clone $query)
            ->where('sentiment_label', CustomerReview::SENTIMENT_NEGATIVE)
            ->whereBetween('reviewed_at', [$recentFrom, $dateTo->endOfDay()])
            ->count();

        return [
            'available' => true,
            'total' => (int) ($summary?->aggregate ?? 0),
            'average_rating' => round((float) ($summary?->average_rating ?? 0), 1),
            'positive' => (int) $counts->get(CustomerReview::SENTIMENT_POSITIVE, 0),
            'neutral' => (int) $counts->get(CustomerReview::SENTIMENT_NEUTRAL, 0),
            'negative' => (int) $counts->get(CustomerReview::SENTIMENT_NEGATIVE, 0),
            'recent_negative' => $recentNegative,
        ];
    }

    /**
     * @param  array<string, int|string|null>  $filters
     * @return Collection<int, int>
     */
    private function matchingCustomerIds(
        array $filters,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
    ): Collection {
        if (! $this->hasAppointmentRelationshipFilter($filters)) {
            return collect();
        }

        return $this->appointmentQuery($filters)
            ->whereBetween('appointments.appointment_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->whereNotNull('appointments.customer_profile_id')
            ->distinct()
            ->pluck('appointments.customer_profile_id')
            ->map(fn (int|string $customerId): int => (int) $customerId)
            ->values();
    }

    /** @param array<string, int|string|null> $filters */
    private function hasAppointmentRelationshipFilter(array $filters): bool
    {
        return (bool) (($filters['service_id'] ?? null) || ($filters['therapist_profile_id'] ?? null));
    }

    private function serviceRowKey(?int $serviceId, string $service): string
    {
        if ($serviceId !== null) {
            return 'service:'.$serviceId;
        }

        return 'snapshot:'.mb_strtolower($service);
    }

    /**
     * @return array{service: string, appointment_count: int, completed_count: int, paid_transaction_count: int, revenue: float}
     */
    private function emptyServiceRowFromLabel(string $service): array
    {
        return [
            'service' => $service,
            'appointment_count' => 0,
            'completed_count' => 0,
            'paid_transaction_count' => 0,
            'revenue' => 0.0,
        ];
    }
}
