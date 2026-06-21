<?php

namespace App\Services;

use App\Models\TherapistCommission;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ManagementFinancialReportBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(CarbonImmutable $dateFrom, CarbonImmutable $dateTo): array
    {
        $transactions = Transaction::query()
            ->with([
                'appointment.customerProfile',
                'appointment.therapistProfile',
                'appointment.service',
                'customerProfile',
            ])
            ->whereBetween('transaction_date', [$dateFrom->startOfDay(), $dateTo->endOfDay()])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $commissions = TherapistCommission::query()
            ->with([
                'therapistProfile',
                'therapistUser',
                'transaction',
                'appointment.customerProfile',
            ])
            ->whereHas('transaction', function ($query) use ($dateFrom, $dateTo): void {
                $query->whereBetween('transaction_date', [$dateFrom->startOfDay(), $dateTo->endOfDay()]);
            })
            ->latest('id')
            ->get();

        $paidTransactions = $transactions->where('payment_status', Transaction::STATUS_PAID);

        return [
            'transactions' => $transactions,
            'commissions' => $commissions,
            'sales_summary' => $this->salesSummary($transactions, $paidTransactions),
            'commission_summary' => $this->commissionSummary($commissions),
            'service_performance' => $this->servicePerformance($paidTransactions),
            'therapist_summary' => $this->therapistSummary($commissions),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @param  Collection<int, Transaction>  $paidTransactions
     * @return array<string, float|int>
     */
    private function salesSummary(Collection $transactions, Collection $paidTransactions): array
    {
        return [
            'gross_sales' => $this->sumMoney($paidTransactions, 'subtotal'),
            'discounts' => $this->sumMoney($paidTransactions, 'discount_amount'),
            'net_sales' => $this->sumMoney($paidTransactions, 'total_amount'),
            'paid_count' => $transactions->where('payment_status', Transaction::STATUS_PAID)->count(),
            'pending_count' => $transactions->where('payment_status', Transaction::STATUS_PENDING)->count(),
            'void_count' => $transactions->where('payment_status', Transaction::STATUS_VOID)->count(),
        ];
    }

    /**
     * @param  Collection<int, TherapistCommission>  $commissions
     * @return array<string, float|int>
     */
    private function commissionSummary(Collection $commissions): array
    {
        $pending = $commissions->where('status', TherapistCommission::STATUS_PENDING);
        $paid = $commissions->where('status', TherapistCommission::STATUS_PAID);
        $void = $commissions->where('status', TherapistCommission::STATUS_VOID);

        return [
            'total_generated' => $this->sumMoney($commissions, 'commission_amount'),
            'pending_total' => $this->sumMoney($pending, 'commission_amount'),
            'paid_total' => $this->sumMoney($paid, 'commission_amount'),
            'void_total' => $this->sumMoney($void, 'commission_amount'),
            'pending_count' => $pending->count(),
            'paid_count' => $paid->count(),
            'void_count' => $void->count(),
        ];
    }

    /**
     * @param  Collection<int, Transaction>  $paidTransactions
     * @return Collection<int, array<string, float|int|string>>
     */
    private function servicePerformance(Collection $paidTransactions): Collection
    {
        return $paidTransactions
            ->groupBy(fn (Transaction $transaction): string => $this->serviceName($transaction))
            ->map(function (Collection $transactions, string $service): array {
                $count = $transactions->count();
                $netSales = $this->sumMoney($transactions, 'total_amount');

                return [
                    'service' => $service,
                    'paid_sales_count' => $count,
                    'gross_sales' => $this->sumMoney($transactions, 'subtotal'),
                    'discounts' => $this->sumMoney($transactions, 'discount_amount'),
                    'net_sales' => $netSales,
                    'average_sale' => $count > 0 ? round($netSales / $count, 2) : 0.0,
                ];
            })
            ->sortByDesc('net_sales')
            ->values();
    }

    /**
     * @param  Collection<int, TherapistCommission>  $commissions
     * @return Collection<int, array<string, float|int|string>>
     */
    private function therapistSummary(Collection $commissions): Collection
    {
        return $commissions
            ->groupBy(function (TherapistCommission $commission): string {
                if ($commission->therapist_profile_id) {
                    return 'profile:'.$commission->therapist_profile_id;
                }

                if ($commission->therapist_user_id) {
                    return 'user:'.$commission->therapist_user_id;
                }

                return 'unavailable';
            })
            ->map(function (Collection $therapistCommissions): array {
                $first = $therapistCommissions->first();

                return [
                    'therapist' => $this->therapistName($first),
                    'commission_count' => $therapistCommissions->count(),
                    'pending_total' => $this->sumMoney(
                        $therapistCommissions->where('status', TherapistCommission::STATUS_PENDING),
                        'commission_amount',
                    ),
                    'paid_total' => $this->sumMoney(
                        $therapistCommissions->where('status', TherapistCommission::STATUS_PAID),
                        'commission_amount',
                    ),
                    'void_total' => $this->sumMoney(
                        $therapistCommissions->where('status', TherapistCommission::STATUS_VOID),
                        'commission_amount',
                    ),
                    'total_generated' => $this->sumMoney($therapistCommissions, 'commission_amount'),
                ];
            })
            ->sortBy('therapist', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    private function serviceName(Transaction $transaction): string
    {
        return $transaction->appointment?->service_name_snapshot
            ?: $transaction->appointment?->service?->name
            ?: 'Service unavailable';
    }

    private function therapistName(TherapistCommission $commission): string
    {
        if ($commission->therapistProfile) {
            return trim($commission->therapistProfile->first_name.' '.$commission->therapistProfile->last_name);
        }

        return $commission->therapistUser?->name ?: 'Therapist unavailable.';
    }

    /**
     * @param  Collection<int, mixed>  $records
     */
    private function sumMoney(Collection $records, string $attribute): float
    {
        $cents = $records->sum(
            fn ($record): int => (int) round(((float) $record->{$attribute}) * 100),
        );

        return $cents / 100;
    }
}
