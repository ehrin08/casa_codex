@extends('layouts.app')

@section('title', 'Management Dashboard | Casa Paraiso')
@section('page_title', 'Management Dashboard')
@section('page_description', 'A clear view of Casa Paraiso appointments, services, people, and daily availability.')

@section('content')
    @php
        $sections = [
            ['title' => 'Appointments', 'description' => 'Review bookings and maintain status history.', 'route' => 'management.appointments.index', 'label' => 'Manage bookings'],
            ['title' => 'Walk-in Booking', 'description' => 'Create same-day or scheduled appointments for walk-in guests.', 'route' => 'management.walk-ins.create', 'label' => 'Book walk-in'],
            ['title' => 'Transactions', 'description' => 'Record cash payments and review receipt-style transaction details.', 'route' => 'management.transactions.index', 'label' => 'Manage cash sales'],
            ['title' => 'Commissions', 'description' => 'Monitor therapist commission calculations and settle pending records.', 'route' => 'management.commissions.index', 'label' => 'Review commissions'],
            ['title' => 'Reports', 'description' => 'Review sales, service performance, and therapist commission summaries.', 'route' => 'management.reports.index', 'label' => 'View reports'],
            ['title' => 'Analytics', 'description' => 'Review revenue trends, service popularity, customer segments, promotion usage, and booking patterns.', 'route' => 'management.analytics.index', 'label' => 'View analytics'],
            ['title' => 'Customer Segments', 'description' => 'Review RFM scores and segment customers for future promotions.', 'route' => 'management.rfm.index', 'label' => 'View segments'],
            ['title' => 'Reviews & Sentiment', 'description' => 'Review customer feedback, ratings, and sentiment trends.', 'route' => 'management.reviews.index', 'label' => 'View feedback'],
            ['title' => 'Promotion Rules', 'description' => 'Configure RFM-driven discounts and eligibility thresholds.', 'route' => 'management.promotions.index', 'label' => 'Manage rules'],
            ['title' => 'Services', 'description' => 'Maintain treatments, durations, prices, and categories.', 'route' => 'management.services.index', 'label' => 'Manage services'],
            ['title' => 'Therapists', 'description' => 'Manage therapist profiles, specialties, and staff status.', 'route' => 'management.therapists.index', 'label' => 'Manage team'],
            ['title' => 'Customers', 'description' => 'Maintain registered and walk-in guest profiles.', 'route' => 'management.customers.index', 'label' => 'Manage guests'],
            ['title' => 'Availability', 'description' => 'Set recurring and date-specific working windows.', 'route' => 'management.availability.index', 'label' => 'Manage schedule'],
            ['title' => 'Notifications', 'description' => 'Stay current on new requests and appointment activity.', 'route' => 'notifications.index', 'label' => 'View updates'],
        ];
    @endphp

    <div class="mb-8 flex flex-col gap-4 rounded-2xl bg-cocoa-800 p-6 text-cream-50 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-200">Operations overview</p>
            <h2 class="mt-2 text-2xl font-semibold">Welcome, {{ auth()->user()->name }}</h2>
            <p class="mt-2 text-sm leading-6 text-cream-200">Everything your team needs to keep the spa day flowing smoothly.</p>
        </div>
        <x-button :href="route('management.appointments.index')" variant="light">Review appointments</x-button>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($sections as $index => $section)
            <a href="{{ route($section['route']) }}" class="spa-panel group relative overflow-hidden p-6 transition hover:-translate-y-0.5 hover:border-sage-200 hover:shadow-lg">
                <span class="flex size-10 items-center justify-center rounded-xl bg-sage-100 text-sm font-black text-sage-700">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <h2 class="mt-5 text-lg font-semibold text-cocoa-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">{{ $section['description'] }}</p>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-sage-700">{{ $section['label'] }} <span aria-hidden="true">&rarr;</span></p>
            </a>
        @endforeach

    </div>
@endsection
