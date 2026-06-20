<?php

namespace App\Http\Controllers\Therapist;

use App\Http\Controllers\Controller;
use App\Models\TherapistCommission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(TherapistCommission::STATUSES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $commissions = $this->ownedCommissions($request)
            ->with(['transaction', 'appointment.customerProfile'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereDate('transaction_date', '>=', $date)))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereHas('transaction', fn ($transactionQuery) => $transactionQuery->whereDate('transaction_date', '<=', $date)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('therapist.commissions.index', compact('commissions', 'filters'));
    }

    public function show(Request $request, int $commission): View
    {
        $commission = $this->ownedCommissions($request)
            ->with(['therapistProfile', 'transaction', 'appointment.customerProfile', 'appointment.service'])
            ->findOrFail($commission);

        return view('therapist.commissions.show', compact('commission'));
    }

    /**
     * @return Builder<TherapistCommission>
     */
    private function ownedCommissions(Request $request): Builder
    {
        $user = $request->user();
        $therapistProfileId = $user->therapistProfile?->id;

        return TherapistCommission::query()
            ->where(function (Builder $query) use ($user, $therapistProfileId): void {
                $query->where('therapist_user_id', $user->id);

                if ($therapistProfileId !== null) {
                    $query->orWhere('therapist_profile_id', $therapistProfileId);
                }
            });
    }
}
