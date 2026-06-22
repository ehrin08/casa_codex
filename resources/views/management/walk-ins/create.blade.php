@extends('layouts.app')

@section('title', 'Walk-in Booking | Casa Paraiso')
@section('page_title', 'Walk-in Booking')
@section('page_description', 'Create a same-day or scheduled appointment for a walk-in guest.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a></div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)] lg:items-start">
        @include('management.walk-ins._form', ['formIdPrefix' => 'walk-in-page'])

        <aside class="space-y-5 lg:sticky lg:top-28">
            <x-alert type="info" title="Walk-in record">The appointment is created as pending and includes your staff name in the appointment notes for a clear audit trail.</x-alert>
            <x-card>
                <h2 class="font-semibold text-cocoa-950">Before booking</h2>
                <p class="mt-3 text-sm leading-6 text-cocoa-500">Confirm the guest profile, service duration, therapist, and selected time. Availability is checked again when the form is submitted.</p>
            </x-card>
        </aside>
    </div>
@endsection
