@extends('layouts.app')

@section('title', 'Assigned Appointment #'.$appointment->id.' | Casa Paraiso')
@section('page_title', 'Assigned Appointment #'.$appointment->id)
@section('page_description', 'Prepare for the guest with a clear view of the schedule and selected service.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><a href="{{ route('therapist.schedule.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to my schedule</a></div>
        <x-card>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-cream-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Guest preparation</p><h2 class="mt-1 text-lg font-semibold text-cocoa-950">Appointment details</h2></div><x-status-badge :status="$appointment->status" class="px-3 py-1.5" /></div>
            <dl class="mt-7 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment->service_name_snapshot }}</dd></div>
                <div><dt class="spa-detail-label">Date</dt><dd class="spa-detail-value">{{ $appointment->appointment_date->format('F j, Y') }}</dd></div>
                <div><dt class="spa-detail-label">Time</dt><dd class="spa-detail-value">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</dd></div>
                @if ($appointment->notes)<div class="sm:col-span-2"><dt class="spa-detail-label">Customer notes</dt><dd class="mt-1.5 whitespace-pre-line rounded-xl bg-cream-100 p-4 leading-6 text-cocoa-800">{{ $appointment->notes }}</dd></div>@endif
            </dl>
        </x-card>
    </div>
@endsection
