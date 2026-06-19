@extends('layouts.app')

@section('title', 'Management | Casa Paraiso Spa Management System')
@section('page_title', 'Management Area')
@section('page_description', 'Manage Casa Paraiso appointments, services, profiles, and therapist availability.')

@section('content')
    @php
        $sections = [
            ['title' => 'Appointments', 'description' => 'Review bookings and maintain appointment status history.', 'route' => 'management.appointments.index'],
            ['title' => 'Services', 'description' => 'Maintain spa services, durations, prices, categories, and status.', 'route' => 'management.services.index'],
            ['title' => 'Therapists', 'description' => 'Manage therapist profiles, account links, specialties, and staff status.', 'route' => 'management.therapists.index'],
            ['title' => 'Customers', 'description' => 'Maintain registered and walk-in customer profile details.', 'route' => 'management.customers.index'],
            ['title' => 'Availability', 'description' => 'Manage recurring and date-specific therapist working windows.', 'route' => 'management.availability.index'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($sections as $section)
            <a href="{{ route($section['route']) }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md">
                <h2 class="text-lg font-semibold text-zinc-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $section['description'] }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700">Manage records</p>
            </a>
        @endforeach
    </div>
@endsection
