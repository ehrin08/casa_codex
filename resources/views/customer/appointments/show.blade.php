@extends('layouts.app')

@section('title', 'Appointment #'.$appointment->id.' | Casa Paraiso')
@section('page_title', 'Appointment Details')
@section('page_description', 'Your Casa Paraiso visit summary and current request status.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><a href="{{ route('customer.appointments.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to my appointments</a></div>
        <article class="spa-panel overflow-hidden">
            <div class="bg-cocoa-800 px-6 py-6 text-cream-50 sm:px-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-200">Appointment reference</p><h2 class="mt-2 text-2xl font-semibold">#{{ $appointment->id }}</h2></div>
                    <x-status-badge :status="$appointment->status" class="px-3 py-1.5" />
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <div class="rounded-2xl bg-sage-50 p-4 text-sm leading-6 text-sage-800">Your appointment request is safely recorded. Watch notifications for status updates from the Casa Paraiso team.</div>
                <dl class="mt-7 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment->service_name_snapshot }}</dd></div>
                    <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) }}</dd></div>
                    <div><dt class="spa-detail-label">Date</dt><dd class="spa-detail-value">{{ $appointment->appointment_date->format('F j, Y') }}</dd></div>
                    <div><dt class="spa-detail-label">Time</dt><dd class="spa-detail-value">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} to {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</dd></div>
                    <div><dt class="spa-detail-label">Duration</dt><dd class="spa-detail-value">{{ $appointment->service_duration_minutes_snapshot }} minutes</dd></div>
                    <div><dt class="spa-detail-label">Service price</dt><dd class="spa-detail-value">PHP {{ number_format((float) $appointment->service_price_snapshot, 2) }}</dd></div>
                    @if ($appointment->notes)<div class="sm:col-span-2"><dt class="spa-detail-label">Notes</dt><dd class="mt-1.5 whitespace-pre-line leading-6 text-cocoa-800">{{ $appointment->notes }}</dd></div>@endif
                </dl>

                <div class="mt-8 flex flex-col gap-3 border-t border-cream-200 pt-6 sm:flex-row"><x-button :href="route('customer.appointments.create')">Book another appointment</x-button><x-button :href="route('customer.index')" variant="secondary">Return to dashboard</x-button></div>
            </div>
        </article>
    </div>
@endsection
