<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\UpdateTherapistCommissionStatusRequest;
use App\Models\TherapistCommission;
use App\Services\TherapistCommissionRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TherapistCommissionController extends Controller
{
    public function index(): View
    {
        $commissions = TherapistCommission::query()
            ->with([
                'therapistProfile',
                'transaction',
                'appointment.customerProfile',
                'appointment.service',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('management.commissions.index', compact('commissions'));
    }

    public function show(TherapistCommission $commission): View
    {
        $commission->load([
            'therapistProfile',
            'transaction.cashier',
            'appointment.customerProfile',
            'appointment.service',
        ]);

        return view('management.commissions.show', compact('commission'));
    }

    public function updateStatus(
        UpdateTherapistCommissionStatusRequest $request,
        TherapistCommission $commission,
        TherapistCommissionRecorder $recorder,
    ): RedirectResponse {
        $validated = $request->validated();

        $recorder->updateStatus(
            $commission,
            $validated['status'],
            $request->user(),
            $validated['notes'] ?? null,
        );

        return redirect()
            ->route('management.commissions.show', $commission)
            ->with('success', 'Commission status updated successfully.');
    }
}
