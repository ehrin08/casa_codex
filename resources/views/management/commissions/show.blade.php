@extends('layouts.app')

@section('title', 'Commission #'.$commission->id.' | Casa Paraiso')
@section('page_title', 'Commission Detail')
@section('page_description', 'Review the stored calculation snapshot and settlement status.')

@section('content')
    @php($therapist = $commission->therapistProfile)
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><a href="{{ route('management.commissions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to commissions</a>@if ($commission->transaction)<x-button :href="route('management.transactions.show', $commission->transaction)" variant="secondary">View transaction</x-button>@endif</div>

        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-cream-200 pb-6"><div><p class="spa-detail-label">Commission reference</p><h2 class="mt-2 text-2xl font-semibold text-cocoa-950">CP-COM-{{ str_pad((string) $commission->id, 6, '0', STR_PAD_LEFT) }}</h2></div><x-status-badge :status="$commission->status" class="px-3 py-1.5" /></div>

            <dl class="grid gap-x-8 gap-y-6 py-7 sm:grid-cols-2">
                <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : ($commission->therapistUser?->name ?? 'Therapist unavailable') }}</dd></div>
                <div><dt class="spa-detail-label">Transaction date</dt><dd class="spa-detail-value">{{ $commission->transaction?->transaction_date?->format('F j, Y g:i A') ?? 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Appointment</dt><dd class="spa-detail-value">{{ $commission->appointment ? '#'.$commission->appointment->id : 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $commission->appointment?->customerProfile ? trim($commission->appointment->customerProfile->first_name.' '.$commission->appointment->customerProfile->last_name) : 'Customer unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $commission->appointment?->service_name_snapshot ?: $commission->appointment?->service?->name ?: 'Service unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Paid at</dt><dd class="spa-detail-value">{{ $commission->paid_at?->format('F j, Y g:i A') ?? 'Not yet settled' }}</dd></div>
            </dl>

            <div class="rounded-2xl bg-cream-100 p-5 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-3"><div><p class="spa-detail-label">Commission base</p><p class="mt-2 text-xl font-semibold text-cocoa-950">PHP {{ number_format((float) $commission->commission_base_amount, 2) }}</p></div><div><p class="spa-detail-label">Rate snapshot</p><p class="mt-2 text-xl font-semibold text-cocoa-950">{{ number_format((float) $commission->commission_rate, 2) }}%</p></div><div><p class="spa-detail-label">Commission amount</p><p class="mt-2 text-xl font-semibold text-sage-700">PHP {{ number_format((float) $commission->commission_amount, 2) }}</p></div></div>
                <p class="mt-5 text-xs leading-5 text-cocoa-500">The rate is the therapist percentage captured when this commission was first calculated. Later profile-rate changes do not alter it.</p>
            </div>

            @if ($commission->status === \App\Models\TherapistCommission::STATUS_PENDING)
                <form method="POST" action="{{ route('management.commissions.mark-paid', $commission) }}" class="mt-7 flex justify-end" onsubmit="return confirm('Mark this commission as paid?')">@csrf @method('PATCH')<x-button type="submit">Mark commission paid</x-button></form>
            @endif
        </x-card>
    </div>
@endsection
