<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CustomerReview;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = CarbonImmutable::today();

        $todayAppointments = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->count();

        $todayPaidRevenue = Transaction::query()
            ->whereDate('paid_at', $today)
            ->where('payment_status', Transaction::STATUS_PAID)
            ->sum('total_amount');

        $pendingTransactionsCount = Transaction::query()
            ->where('payment_status', Transaction::STATUS_PENDING)
            ->count();

        $unpaidCompletedAppointmentsCount = Appointment::query()
            ->where('status', Appointment::STATUS_COMPLETED)
            ->whereDoesntHave('transaction')
            ->count();

        $pendingPayments = $pendingTransactionsCount + $unpaidCompletedAppointmentsCount;

        $therapistsWorking = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->whereNotNull('therapist_profile_id')
            ->distinct()
            ->count('therapist_profile_id');

        $pendingRequests = Appointment::query()
            ->where('status', Appointment::STATUS_PENDING)
            ->count();

        $attentionNeeded = [];

        if ($pendingRequests > 0) {
            $attentionNeeded[] = [
                'type' => 'pending_request',
                'message' => "{$pendingRequests} appointment request".($pendingRequests > 1 ? 's' : '').' awaiting review.',
                'route' => 'management.appointments.index',
                'route_params' => ['status' => Appointment::STATUS_PENDING],
                'label' => 'Review requests',
            ];
        }

        if ($pendingPayments > 0) {
            $attentionNeeded[] = [
                'type' => 'pending_payment',
                'message' => "{$pendingPayments} payment".($pendingPayments > 1 ? 's require' : ' requires').' processing.',
                'route' => 'management.transactions.index',
                'route_params' => [],
                'label' => 'Record payments',
            ];
        }

        if (class_exists(CustomerReview::class)) {
            $negativeReviews = CustomerReview::query()
                ->where('sentiment_label', CustomerReview::SENTIMENT_NEGATIVE)
                ->where('reviewed_at', '>=', $today->subDays(7))
                ->count();

            if ($negativeReviews > 0) {
                $attentionNeeded[] = [
                    'type' => 'negative_review',
                    'message' => "{$negativeReviews} negative review".($negativeReviews > 1 ? 's' : '').' in the last 7 days.',
                    'route' => 'management.reviews.index',
                    'route_params' => [],
                    'label' => 'View feedback',
                ];
            }
        }

        $mostBookedService = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->with('service')
            ->select('service_id')
            ->selectRaw('count(*) as total')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->first();

        $insights = [
            'most_booked_service' => $mostBookedService && $mostBookedService->service
                ? $mostBookedService->service->name
                : null,
            'most_booked_count' => $mostBookedService ? $mostBookedService->total : 0,
        ];

        return view('management.index', compact(
            'todayAppointments',
            'todayPaidRevenue',
            'pendingPayments',
            'therapistsWorking',
            'attentionNeeded',
            'insights'
        ));
    }
}
