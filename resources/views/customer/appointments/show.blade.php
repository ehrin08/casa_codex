@extends('layouts.app')

@section('title', 'Appointment Confirmation | Casa Paraiso Spa Management System')
@section('page_title', 'Appointment Request Received')
@section('page_description', 'Review the details of your pending appointment request.')

@section('content')
    <div class="mx-auto max-w-2xl rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-5">
            <div>
                <p class="text-sm text-zinc-500">Appointment reference</p>
                <p class="mt-1 text-xl font-semibold text-zinc-950">#{{ $appointment->id }}</p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold capitalize text-amber-900">{{ $appointment->status }}</span>
        </div>

        <dl class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-zinc-500">Service</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->service_name_snapshot }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">Therapist</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">Date</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->appointment_date->format('F j, Y') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">Time</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }} to {{ \Carbon\Carbon::parse($appointment->end_time)->format('g:i A') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">Duration</dt>
                <dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->service_duration_minutes_snapshot }} minutes</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-zinc-500">Service price</dt>
                <dd class="mt-1 font-semibold text-zinc-900">PHP {{ number_format((float) $appointment->service_price_snapshot, 2) }}</dd>
            </div>
            @if ($appointment->notes)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-zinc-500">Notes</dt>
                    <dd class="mt-1 whitespace-pre-line text-zinc-900">{{ $appointment->notes }}</dd>
                </div>
            @endif
        </dl>

        <div class="mt-7 flex flex-wrap gap-3 border-t border-zinc-200 pt-5">
            <a href="{{ route('customer.appointments.create') }}" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Book another appointment</a>
            <a href="{{ route('customer.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Return to customer area</a>
        </div>
    </div>
@endsection
