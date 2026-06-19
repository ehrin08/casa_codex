@extends('layouts.app')

@section('title', 'Customer | Casa Paraiso Spa Management System')
@section('page_title', 'Customer Area')
@section('page_description', 'Book a spa service and manage your customer experience with Casa Paraiso.')

@section('content')
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article class="rounded-lg border border-emerald-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-zinc-950">Book Appointment</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-600">Select an active spa service, therapist, date, and preferred start time.</p>
            <a href="{{ route('customer.appointments.create') }}" class="mt-5 inline-flex rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">
                Book an appointment
            </a>
        </article>

        <a href="{{ route('customer.appointments.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md"><h2 class="text-lg font-semibold text-zinc-950">My Appointments</h2><p class="mt-2 text-sm leading-6 text-zinc-600">Review upcoming and previous appointment details.</p><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700">View appointments</p></a>
        <a href="{{ route('notifications.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md"><h2 class="text-lg font-semibold text-zinc-950">Notifications</h2><p class="mt-2 text-sm leading-6 text-zinc-600">Review appointment status updates.</p><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700">View notifications</p></a>
    </div>
@endsection
