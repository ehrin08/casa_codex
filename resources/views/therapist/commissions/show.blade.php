@extends('layouts.app')

@section('title', 'My Commission #'.$commission->id.' | Casa Paraiso')
@section('page_title', 'My Commission Detail')
@section('page_description', 'A read-only view of your commission calculation and status.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><a href="{{ route('therapist.commissions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to my commissions</a></div>
        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-cream-200 pb-6"><div><p class="spa-detail-label">Commission reference</p><h2 class="mt-2 text-2xl font-semibold text-cocoa-950">CP-COM-{{ str_pad((string) $commission->id, 6, '0', STR_PAD_LEFT) }}</h2></div><x-status-badge :status="$commission->status" class="px-3 py-1.5" /></div>
            <dl class="grid gap-x-8 gap-y-6 py-7 sm:grid-cols-2">
                <div><dt class="spa-detail-label">Transaction date</dt><dd class="spa-detail-value">{{ $commission->transaction?->transaction_date?->format('F j, Y g:i A') ?? 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $commission->appointment?->service_name_snapshot ?: $commission->appointment?->service?->name ?: 'Service unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Appointment</dt><dd class="spa-detail-value">{{ $commission->appointment ? '#'.$commission->appointment->id : 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Settlement date</dt><dd class="spa-detail-value">{{ $commission->paid_at?->format('F j, Y g:i A') ?? 'Not yet settled' }}</dd></div>
            </dl>
            <div class="rounded-2xl bg-cream-100 p-5 sm:p-6"><div class="grid gap-5 sm:grid-cols-3"><div><p class="spa-detail-label">Base amount</p><p class="mt-2 font-semibold text-cocoa-950">PHP {{ number_format((float) $commission->commission_base_amount, 2) }}</p></div><div><p class="spa-detail-label">Rate snapshot</p><p class="mt-2 font-semibold text-cocoa-950">{{ number_format((float) $commission->commission_rate, 2) }}%</p></div><div><p class="spa-detail-label">Commission</p><p class="mt-2 text-xl font-semibold text-sage-700">PHP {{ number_format((float) $commission->commission_amount, 2) }}</p></div></div></div>
            <p class="mt-6 text-sm leading-6 text-cocoa-500">Commission records are read-only for therapists. Management handles settlement after verification.</p>
        </x-card>
    </div>
@endsection
