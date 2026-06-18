@extends('layouts.app')

@section('title', 'Management | Casa Paraiso Spa Management System')
@section('page_title', 'Management Area')
@section('page_description', 'Placeholder structure for future Casa Paraiso management workflows.')

@section('content')
    @php
        $sections = [
            ['title' => 'Services', 'description' => 'Maintain spa services, durations, prices, and active status.'],
            ['title' => 'Therapists', 'description' => 'Manage therapist records, availability, and staff status.'],
            ['title' => 'Customers', 'description' => 'View customer profiles and future visit history.'],
            ['title' => 'Appointments', 'description' => 'Monitor booking records and appointment status.'],
            ['title' => 'Transactions', 'description' => 'Record and review cash payment activity.'],
            ['title' => 'Reports', 'description' => 'Prepare sales and commission report foundations.'],
            ['title' => 'Promotions', 'description' => 'Prepare RFM and rule-based promotion setup.'],
            ['title' => 'Reviews', 'description' => 'Review customer ratings and sentiment labels.'],
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($sections as $section)
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $section['description'] }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400">Coming later</p>
            </article>
        @endforeach
    </div>
@endsection
