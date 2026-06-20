@extends('layouts.app')

@section('title', 'Commission #'.$commission->id.' | Casa Paraiso')
@section('page_title', 'Commission #'.$commission->id)
@section('page_description', 'Review the calculation snapshot and manage this therapist commission status.')

@section('content')
    @php
        $appointment = $commission->appointment;
        $transaction = $commission->transaction;
        $therapist = $commission->therapistProfile;
        $customer = $appointment?->customerProfile;
    @endphp
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><a href="{{ route('management.commissions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to commissions</a>@if ($transaction)<x-button :href="route('management.transactions.show', $transaction)" variant="secondary">View cash receipt</x-button>@endif</div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(300px,2fr)] lg:items-start">
        <div class="space-y-6">
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-cream-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Earning record</p><h2 class="mt-1 text-lg font-semibold text-cocoa-950">Commission details</h2></div><x-status-badge :status="$commission->status" class="px-3 py-1.5" /></div>
                <dl class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Appointment</dt><dd class="spa-detail-value">{{ $appointment ? '#'.$appointment->id.' on '.$appointment->appointment_date->format('M j, Y') : 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Transaction</dt><dd class="spa-detail-value">{{ $transaction ? 'CP-'.str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT) : 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Created</dt><dd class="spa-detail-value">{{ $commission->created_at->format('F j, Y g:i A') }}</dd></div>
                    @if ($commission->paid_at)<div><dt class="spa-detail-label">Paid at</dt><dd class="spa-detail-value">{{ $commission->paid_at->format('F j, Y g:i A') }}</dd></div>@endif
                    @if ($commission->notes)<div class="sm:col-span-2"><dt class="spa-detail-label">Status notes</dt><dd class="mt-1.5 whitespace-pre-line leading-6 text-cocoa-800">{{ $commission->notes }}</dd></div>@endif
                </dl>
            </x-card>

            <x-card>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Calculation snapshot</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl bg-cream-100 p-4"><p class="spa-detail-label">Subtotal basis</p><p class="mt-2 text-xl font-semibold text-cocoa-950">PHP {{ number_format((float) $transaction?->subtotal, 2) }}</p></div>
                    <div class="rounded-2xl bg-cream-100 p-4"><p class="spa-detail-label">Rate snapshot</p><p class="mt-2 text-xl font-semibold text-cocoa-950">{{ number_format((float) $commission->commission_rate, 2) }}%</p></div>
                    <div class="rounded-2xl bg-sage-100 p-4"><p class="spa-detail-label text-sage-700">Commission</p><p class="mt-2 text-xl font-semibold text-sage-800">PHP {{ number_format((float) $commission->commission_amount, 2) }}</p></div>
                </div>
                <p class="mt-4 text-sm leading-6 text-cocoa-500">Commission is based on the service subtotal before discount: subtotal multiplied by the therapist rate snapshot.</p>
            </x-card>
        </div>

        <aside class="lg:sticky lg:top-28">
            @if ($commission->status === \App\Models\TherapistCommission::STATUS_PENDING)
                <form method="POST" action="{{ route('management.commissions.update-status', $commission) }}" class="spa-panel p-6">
                    @csrf
                    @method('PATCH')
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Commission action</p>
                    <h2 class="mt-1 text-lg font-semibold text-cocoa-950">Update status</h2>
                    <div class="mt-6 space-y-5">
                        <x-form.select name="status" label="New status" required><option value="paid" @selected(old('status') === 'paid')>Paid</option><option value="void" @selected(old('status') === 'void')>Void</option></x-form.select>
                        <x-form.textarea name="notes" label="Status notes (optional)" rows="4" maxlength="2000" />
                        <x-button type="submit" class="w-full">Save commission status</x-button>
                    </div>
                </form>
            @else
                <x-alert :type="$commission->status === 'paid' ? 'success' : 'warning'" title="Terminal commission status">This commission is {{ $commission->status }} and cannot be returned to pending.</x-alert>
            @endif
        </aside>
    </div>
@endsection
