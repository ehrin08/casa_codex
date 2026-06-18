@extends('layouts.app')

@section('title', 'Customer | Casa Paraiso Spa Management System')
@section('page_title', 'Customer Area')
@section('page_description', 'Placeholder structure for future customer booking, services, promotions, and review workflows.')

@section('content')
    @php
        $sections = [
            ['title' => 'Book Appointment', 'description' => 'Prepare a future path for selecting services, dates, and therapists.'],
            ['title' => 'My Appointments', 'description' => 'Show upcoming, completed, cancelled, and no-show records later.'],
            ['title' => 'Services', 'description' => 'Browse available spa services and service details.'],
            ['title' => 'Promotions', 'description' => 'Display future RFM-based offers and active promotions.'],
            ['title' => 'Reviews', 'description' => 'Submit and view future service ratings and comments.'],
        ];
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($sections as $section)
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $section['description'] }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400">Coming later</p>
            </article>
        @endforeach
    </div>
@endsection
