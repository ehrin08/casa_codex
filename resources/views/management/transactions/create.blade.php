@extends('layouts.app')

@section('title', 'Record Cash Transaction | Casa Paraiso')
@section('page_title', 'Record Cash Transaction')
@section('page_description', 'Select a completed appointment and record its over-the-counter cash payment.')

@section('content')
    <div class="mb-7 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.transactions.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to transactions</a>
        @if ($selectedAppointment)<x-button :href="route('management.transactions.create')" variant="secondary">Choose another appointment</x-button>@endif
    </div>

    @if ($selectedAppointment)
        @php
            $subtotal = $selectedAppointment->service_price_snapshot ?? $selectedAppointment->service?->price;
            $customer = $selectedAppointment->customerProfile;
            $therapist = $selectedAppointment->therapistProfile;
        @endphp
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)] lg:items-start">
            <form method="POST" action="{{ route('management.transactions.store') }}" class="spa-panel p-6 sm:p-8">
                @csrf
                <input type="hidden" name="appointment_id" value="{{ $selectedAppointment->id }}">

                <div class="border-b border-cream-200 pb-6">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">Cash payment</p>
                    <h2 class="mt-2 text-xl font-semibold text-cocoa-950">Appointment #{{ $selectedAppointment->id }}</h2>
                    <p class="mt-2 text-sm leading-6 text-cocoa-500">The subtotal is fixed from the appointment price snapshot. The final total is calculated securely when this form is submitted.</p>
                </div>

                @error('appointment_id')<x-alert type="error" class="mt-6">{{ $message }}</x-alert>@enderror

                <section class="mt-7 border-b border-cream-200 pb-7">
                    <h2 class="font-semibold text-cocoa-950">Eligible Promotions</h2>
                    <p class="mt-1 text-sm leading-6 text-cocoa-500">Choose at most one recommendation. Eligibility and the final discount are recalculated on the server using the transaction date.</p>

                    @error('promotion_id')<x-alert type="error" class="mt-4">{{ $message }}</x-alert>@enderror

                    <div class="mt-4 space-y-3">
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-cream-300 bg-white p-4">
                            <input type="radio" name="promotion_id" value="" class="mt-1" @checked(old('promotion_id', '') === '')>
                            <span><span class="block font-semibold text-cocoa-900">No promotion</span><span class="mt-1 block text-xs leading-5 text-cocoa-500">Use the manual discount amount entered below.</span></span>
                        </label>

                        @forelse ($promotionRecommendations as $recommendation)
                            @php
                                $promotion = $recommendation['promotion'];
                            @endphp
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-sage-200 bg-sage-50/60 p-4">
                                <input type="radio" name="promotion_id" value="{{ $promotion->id }}" class="mt-1" @checked((string) old('promotion_id') === (string) $promotion->id)>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-semibold text-cocoa-900">{{ $promotion->title }}</span>
                                        <span class="font-bold text-sage-700">PHP {{ number_format((float) $recommendation['discount_amount'], 2) }} off</span>
                                    </span>
                                    <span class="mt-1 block text-xs leading-5 text-cocoa-600">{{ $recommendation['reason'] }}</span>
                                    <span class="mt-1 block text-xs font-semibold text-cocoa-500">{{ $promotion->discount_type === \App\Models\Promotion::DISCOUNT_TYPE_PERCENTAGE ? number_format((float) $promotion->discount_value, 2).'%' : 'PHP '.number_format((float) $promotion->discount_value, 2) }} {{ $promotion->discount_type === \App\Models\Promotion::DISCOUNT_TYPE_PERCENTAGE ? 'percentage discount' : 'fixed discount' }}</span>
                                </span>
                            </label>
                        @empty
                            <div class="rounded-xl border border-dashed border-cream-300 p-4 text-sm text-cocoa-500">
                                <p class="font-semibold text-cocoa-700">No eligible promotions</p>
                                <p class="mt-1 text-xs leading-5">This customer and service do not currently match an active promotion rule.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <div class="mt-7 grid gap-6 sm:grid-cols-2">
                    <x-form.input name="discount_amount" label="Manual discount amount (PHP)" type="number" value="0.00" min="0" :max="$subtotal" step="0.01" required hint="Used only when No promotion is selected. Promotion discounts are calculated server-side." />
                    <x-form.select name="payment_status" label="Payment status" required hint="Paid means money was received. Pending awaits payment. Void cancels the record.">
                        @foreach (\App\Models\Transaction::PAYMENT_STATUSES as $status)<option value="{{ $status }}" @selected(old('payment_status', \App\Models\Transaction::STATUS_PAID) === $status)>{{ ucfirst($status) }}</option>@endforeach
                    </x-form.select>
                    <x-form.input name="amount_tendered" label="Cash tendered (PHP)" type="number" :value="$subtotal" min="0" step="0.01" hint="Required for paid transactions. Leave blank for pending or void." />
                    <x-form.input name="transaction_date" label="Transaction date and time" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />
                    <x-form.textarea name="notes" label="Transaction notes (optional)" rows="4" maxlength="2000" wrapper-class="sm:col-span-2" />
                </div>

                <div class="mt-7 flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end"><x-button :href="route('management.transactions.index')" variant="secondary">Cancel</x-button><x-button type="submit">Record cash transaction</x-button></div>
            </form>

            <aside class="space-y-5 lg:sticky lg:top-28">
                <x-card>
                    <div class="flex items-center justify-between gap-3 border-b border-cream-200 pb-4"><h2 class="font-semibold text-cocoa-950">Appointment summary</h2><x-status-badge :status="$selectedAppointment->status" /></div>
                    <dl class="mt-5 space-y-4">
                        <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable' }}</dd></div>
                        <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $selectedAppointment->service_name_snapshot ?: $selectedAppointment->service?->name ?: 'Service unavailable' }}</dd></div>
                        <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Therapist unavailable' }}</dd></div>
                        <div class="grid grid-cols-2 gap-4"><div><dt class="spa-detail-label">Appointment date</dt><dd class="spa-detail-value">{{ $selectedAppointment->appointment_date->format('M j, Y') }}</dd></div><div><dt class="spa-detail-label">Time</dt><dd class="spa-detail-value">{{ date('g:i A', strtotime($selectedAppointment->start_time)) }}</dd></div></div>
                    </dl>
                    <div class="mt-6 rounded-2xl bg-cocoa-800 p-5 text-cream-50"><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-200">Service subtotal</p><p class="mt-2 text-3xl font-semibold">PHP {{ number_format((float) $subtotal, 2) }}</p></div>
                </x-card>
                <x-alert title="Server-calculated totals">The stored subtotal, discount, total, and cash change are recalculated on submission. Values displayed in the browser are never trusted as financial totals.</x-alert>
            </aside>
        </div>
    @else
        <div class="mb-6"><h2 class="spa-section-title">Choose a completed appointment</h2><p class="mt-1 text-sm text-cocoa-500">Only completed appointments without an existing transaction are available.</p></div>
        <div class="spa-table-wrap">
            <table class="spa-table">
                <thead><tr><th>Appointment</th><th>Customer</th><th>Service</th><th>Therapist</th><th>Schedule</th><th>Subtotal</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse ($eligibleAppointments as $appointment)
                        @php
                            $subtotal = $appointment->service_price_snapshot ?? $appointment->service?->price;
                        @endphp
                        <tr>
                            <td class="font-bold text-cocoa-950">#{{ $appointment->id }}</td>
                            <td class="text-cocoa-600">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</td>
                            <td class="text-cocoa-600">{{ $appointment->service_name_snapshot ?: $appointment->service?->name ?: 'Unavailable' }}</td>
                            <td class="text-cocoa-600">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Unavailable' }}</td>
                            <td class="whitespace-nowrap text-cocoa-600">{{ $appointment->appointment_date->format('M j, Y') }}<p class="mt-1 text-xs text-cocoa-500">{{ date('g:i A', strtotime($appointment->start_time)) }}</p></td>
                            <td class="whitespace-nowrap font-semibold text-cocoa-900">{{ $subtotal === null ? 'Unavailable' : 'PHP '.number_format((float) $subtotal, 2) }}</td>
                            <td class="text-right">@if ($subtotal !== null)<x-button :href="route('management.transactions.create', ['appointment_id' => $appointment->id])" class="min-h-9 px-3 py-1.5">Select</x-button>@else<span class="text-xs text-red-700">Missing price</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="No appointments ready for payment" description="Complete an appointment first, or review receipts for appointments that have already been recorded."><x-slot:action><x-button :href="route('management.appointments.index')" variant="secondary">Review appointments</x-button></x-slot:action></x-empty-state></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $eligibleAppointments->links() }}</div>
    @endif
@endsection
