@extends('layouts.app')

@section('title', 'Therapist | Casa Paraiso Spa Management System')
@section('page_title', 'Therapist Area')
@section('page_description', 'Placeholder structure for future therapist schedules, appointment assignments, and commission summaries.')

@section('content')
    @php
        $sections = [
            ['title' => 'My Schedule', 'description' => 'View assigned work dates and availability windows.'],
            ['title' => 'Assigned Appointments', 'description' => 'Review upcoming and completed appointment assignments.'],
            ['title' => 'Commission Summary', 'description' => 'Track future commission totals and payment status.'],
            ['title' => 'Customer Notes', 'description' => 'Prepare space for relevant service notes and customer preferences.'],
        ];
    @endphp

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($sections as $section)
            <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-950">{{ $section['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $section['description'] }}</p>
                <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-400">Coming later</p>
            </article>
        @endforeach
    </div>
@endsection
