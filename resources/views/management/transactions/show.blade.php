@extends('layouts.app')

@section('title', 'Cash Receipt #'.$transaction->id.' | Casa Paraiso')
@section('page_title', 'Cash Transaction Receipt')
@section('page_description', 'A receipt-style record of the appointment payment captured at Casa Paraiso.')

@section('content')
    @php
        $appointment = $transaction->appointment;
        $customer = $appointment?->customerProfile ?? $transaction->customerProfile;
        $therapist = $appointment?->therapistProfile;
    @endphp
    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3"><a href="{{ route('management.transactions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to transactions</a><div class="flex flex-wrap gap-3">@if ($transaction->therapistCommission)<x-button :href="route('management.commissions.show', $transaction->therapistCommission)" variant="secondary">View commission</x-button>@endif @if ($appointment)<x-button :href="route('management.appointments.show', $appointment)" variant="secondary">View appointment</x-button>@endif</div></div>

        <article class="spa-panel overflow-hidden">
            <header class="relative overflow-hidden bg-cocoa-900 px-6 py-8 text-center text-cream-50 sm:px-10">
                <div class="absolute -right-12 -top-20 size-48 rounded-full border-[24px] border-sage-600/20" aria-hidden="true"></div>
                <div class="relative">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-sage-700" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none" class="size-8" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg></div>
                    <p class="mt-4 text-lg font-black tracking-[0.14em]">CASA PARAISO</p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-sage-200">Body and Wellness Spa</p>
                    <p class="mt-5 text-sm text-cream-300">Cash receipt</p>
                    <p class="mt-1 text-xl font-semibold">CP-{{ str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT) }}</p>
                </div>
            </header>

            <div class="p-6 sm:p-10">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-dashed border-cream-300 pb-6"><div><p class="spa-detail-label">Transaction date</p><p class="mt-1 font-semibold text-cocoa-900">{{ $transaction->transaction_date->format('F j, Y g:i A') }}</p></div><x-status-badge :status="$transaction->payment_status" class="px-3 py-1.5" /></div>

                <dl class="grid gap-x-8 gap-y-6 border-b border-dashed border-cream-300 py-7 sm:grid-cols-2">
                    <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Cashier</dt><dd class="spa-detail-value">{{ $transaction->cashier?->name ?? 'Cashier unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Service unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Therapist unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Appointment date</dt><dd class="spa-detail-value">{{ $appointment?->appointment_date?->format('F j, Y') ?? 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Appointment time</dt><dd class="spa-detail-value">{{ $appointment ? date('g:i A', strtotime($appointment->start_time)).' to '.date('g:i A', strtotime($appointment->end_time)) : 'Unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Payment method</dt><dd class="spa-detail-value capitalize">{{ $transaction->payment_method }}</dd></div>
                    <div><dt class="spa-detail-label">Appointment reference</dt><dd class="spa-detail-value">{{ $appointment ? '#'.$appointment->id : 'Unavailable' }}</dd></div>
                </dl>

                <div class="space-y-3 border-b border-dashed border-cream-300 py-7 text-sm">
                    <div class="flex justify-between gap-4 text-cocoa-600"><span>Service subtotal</span><span>PHP {{ number_format((float) $transaction->subtotal, 2) }}</span></div>
                    <div class="flex justify-between gap-4 text-cocoa-600"><span>Discount</span><span>- PHP {{ number_format((float) $transaction->discount_amount, 2) }}</span></div>
                    <div class="flex items-end justify-between gap-4 border-t border-cream-200 pt-4"><span class="font-bold text-cocoa-900">Total amount</span><span class="text-2xl font-semibold text-cocoa-950">PHP {{ number_format((float) $transaction->total_amount, 2) }}</span></div>
                    @if ($transaction->amount_tendered !== null)<div class="flex justify-between gap-4 pt-2 text-cocoa-600"><span>Cash tendered</span><span>PHP {{ number_format((float) $transaction->amount_tendered, 2) }}</span></div><div class="flex justify-between gap-4 font-semibold text-sage-700"><span>Change due</span><span>PHP {{ number_format((float) $transaction->change_due, 2) }}</span></div>@endif
                </div>

                @if ($transaction->notes)<div class="border-b border-dashed border-cream-300 py-6"><p class="spa-detail-label">Transaction notes</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $transaction->notes }}</p></div>@endif

                <p class="pt-7 text-center text-sm leading-6 text-cocoa-500">Thank you for choosing Casa Paraiso. This receipt records an over-the-counter cash transaction and is not an online payment confirmation.</p>
            </div>
        </article>
    </div>
@endsection
