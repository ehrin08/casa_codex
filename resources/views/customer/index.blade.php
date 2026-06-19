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

        @foreach ([
            ['title' => 'My Appointments', 'description' => 'Upcoming and past appointment lists will be added in the scheduling workflow.'],
            ['title' => 'Promotions and Reviews', 'description' => 'Customer offers and service feedback will be available in later sprints.'],
        ] as $section)
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $section['description'] }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400">Coming later</p>
            </article>
        @endforeach
    </div>
@endsection
