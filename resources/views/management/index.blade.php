@extends('layouts.app')

@section('title', 'Management Dashboard | Casa Paraiso')
@section('page_title', 'Management Dashboard')
@section('page_description', 'A clear view of Casa Paraiso appointments, services, people, and daily availability.')

@section('content')
    @php
        $dashboardSections = [
            [
                'heading' => 'Daily Operations',
                'cards' => [
                    ['title' => 'Walk-in Booking', 'description' => 'Create same-day or scheduled appointments for walk-in guests.', 'route' => 'management.walk-ins.create', 'label' => 'Book walk-in'],
                    ['title' => 'Appointments', 'description' => 'Review, update, and manage customer appointments.', 'route' => 'management.appointments.index', 'label' => 'View appointments'],
                    ['title' => 'Availability', 'description' => 'Set recurring and date-specific working windows.', 'route' => 'management.availability.index', 'label' => 'Manage schedule'],
                    ['title' => 'Notifications', 'description' => 'Stay current on new requests and appointment activity.', 'route' => 'notifications.index', 'label' => 'View updates'],
                ],
            ],
            [
                'heading' => 'Payments & Reports',
                'cards' => [
                    ['title' => 'Transactions', 'description' => 'Record and manage customer payments.', 'route' => 'management.transactions.index', 'label' => 'View transactions'],
                    ['title' => 'Commissions', 'description' => 'Monitor therapist commission calculations and settle pending records.', 'route' => 'management.commissions.index', 'label' => 'Review commissions'],
                    ['title' => 'Reports', 'description' => 'Review financial summaries and print-ready reports.', 'route' => 'management.reports.index', 'label' => 'View reports'],
                ],
            ],
            [
                'heading' => 'Customer Insights',
                'cards' => [
                    ['title' => 'Analytics', 'description' => 'Review revenue trends, booking patterns, customer segments, and promotion performance.', 'route' => 'management.analytics.index', 'label' => 'View analytics'],
                    ['title' => 'RFM Scores', 'description' => 'Review customer value segments and retention indicators.', 'route' => 'management.rfm.index', 'label' => 'View RFM scores'],
                    ['title' => 'Reviews & Sentiment', 'description' => 'Review customer feedback, ratings, and sentiment trends.', 'route' => 'management.reviews.index', 'label' => 'View feedback'],
                    ['title' => 'Customers', 'description' => 'Maintain registered and walk-in guest profiles.', 'route' => 'management.customers.index', 'label' => 'Manage guests'],
                ],
            ],
            [
                'heading' => 'Marketing & Promotions',
                'cards' => [
                    ['title' => 'Promotions', 'description' => 'Manage customer promotions and eligibility rules.', 'route' => 'management.promotions.index', 'label' => 'Manage promotions'],
                ],
            ],
            [
                'heading' => 'System Records',
                'cards' => [
                    ['title' => 'Services', 'description' => 'Maintain treatments, durations, prices, and categories.', 'route' => 'management.services.index', 'label' => 'Manage services'],
                    ['title' => 'Therapists', 'description' => 'Manage therapist profiles, specialties, and staff status.', 'route' => 'management.therapists.index', 'label' => 'Manage team'],
                ],
            ],
        ];

        $cardIndex = 0;
    @endphp

    <div class="mb-8 flex flex-col gap-4 rounded-2xl bg-cocoa-800 p-6 text-cream-50 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-200">Operations overview</p>
            <h2 class="mt-2 text-2xl font-semibold">Welcome, {{ auth()->user()->name }}</h2>
            <p class="mt-2 text-sm leading-6 text-cream-200">Everything your team needs to keep the spa day flowing smoothly.</p>
        </div>
        <x-button :href="route('management.walk-ins.create')" variant="light">Book walk-in</x-button>
    </div>

    @foreach ($dashboardSections as $section)
        <div class="mb-8">
            <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">{{ $section['heading'] }}</h3>
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($section['cards'] as $card)
                    <a href="{{ route($card['route']) }}" class="spa-panel group relative overflow-hidden p-6 transition hover:-translate-y-0.5 hover:border-sage-200 hover:shadow-lg">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-sage-100 text-sm font-black text-sage-700">{{ str_pad(++$cardIndex, 2, '0', STR_PAD_LEFT) }}</span>
                        <h2 class="mt-5 text-lg font-semibold text-cocoa-950">{{ $card['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-cocoa-500">{{ $card['description'] }}</p>
                        <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-sage-700">{{ $card['label'] }} <span aria-hidden="true">&rarr;</span></p>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
