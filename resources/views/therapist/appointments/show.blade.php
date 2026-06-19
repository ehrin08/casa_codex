@extends('layouts.app')

@section('title', 'Assigned Appointment #'.$appointment->id.' | Casa Paraiso Spa Management System')
@section('page_title', 'Assigned Appointment #'.$appointment->id)
@section('page_description', 'Review the schedule and service details for this assigned appointment.')

@section('content')
    <div class="mb-6"><a href="{{ route('therapist.schedule.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to my schedule</a></div>

    <div class="mx-auto max-w-2xl rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-5">
            <h2 class="text-lg font-semibold text-zinc-950">Appointment details</h2>
            <span class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-semibold text-zinc-700">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
        </div>
        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><dt class="text-sm font-medium text-zinc-500">Customer</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</dd></div>
            <div><dt class="text-sm font-medium text-zinc-500">Service</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->service_name_snapshot }}</dd></div>
            <div><dt class="text-sm font-medium text-zinc-500">Date</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->appointment_date->format('F j, Y') }}</dd></div>
            <div><dt class="text-sm font-medium text-zinc-500">Time</dt><dd class="mt-1 font-semibold text-zinc-900">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</dd></div>
            @if ($appointment->notes)<div class="sm:col-span-2"><dt class="text-sm font-medium text-zinc-500">Customer notes</dt><dd class="mt-1 whitespace-pre-line text-zinc-900">{{ $appointment->notes }}</dd></div>@endif
        </dl>
    </div>
@endsection
