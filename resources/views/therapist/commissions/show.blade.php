@extends('layouts.app')

@section('title', 'Commission #'.$commission->id.' | Casa Paraiso')
@section('page_title', 'Commission Detail')
@section('page_description', 'Review the service and calculation behind this commission record.')

@section('content')
    @php
        $appointment = $commission->appointment;
        $transaction = $commission->transaction;
        $customer = $appointment?->customerProfile;
    @endphp
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><a href="{{ route('therapist.commissions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to my commissions</a></div>
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-cream-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Your earning record</p><h2 class="mt-1 text-lg font-semibold text-cocoa-950">Commission #{{ $commission->id }}</h2></div><x-status-badge :status="$commission->status" class="px-3 py-1.5" /></div>

            <dl class="mt-7 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Appointment date</dt><dd class="spa-detail-value">{{ $appointment?->appointment_date?->format('F j, Y') ?? 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Recorded</dt><dd class="spa-detail-value">{{ $commission->created_at->format('F j, Y') }}</dd></div>
                @if ($commission->paid_at)<div><dt class="spa-detail-label">Paid at</dt><dd class="spa-detail-value">{{ $commission->paid_at->format('F j, Y g:i A') }}</dd></div>@endif
                @if ($commission->notes)<div class="sm:col-span-2"><dt class="spa-detail-label">Status notes</dt><dd class="mt-1.5 whitespace-pre-line leading-6 text-cocoa-800">{{ $commission->notes }}</dd></div>@endif
            </dl>

            <div class="mt-8 rounded-2xl bg-cocoa-800 p-5 text-cream-50 sm:p-6">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-200">Subtotal basis</p><p class="mt-2 text-lg font-semibold">PHP {{ number_format((float) $transaction?->subtotal, 2) }}</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-200">Rate snapshot</p><p class="mt-2 text-lg font-semibold">{{ number_format((float) $commission->commission_rate, 2) }}%</p></div>
                    <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-gold-300">Commission</p><p class="mt-2 text-2xl font-semibold">PHP {{ number_format((float) $commission->commission_amount, 2) }}</p></div>
                </div>
            </div>
        </x-card>
    </div>
@endsection
