@extends('layouts.app')

@section('title', 'Therapist Dashboard | Casa Paraiso')
@section('page_title', 'Therapist Dashboard')
@section('page_description', 'Prepare for today\'s guests and keep upcoming appointments close at hand.')

@section('content')
    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['My Schedule', 'View today\'s guests and all upcoming assigned appointments.', 'therapist.schedule.index', 'Open schedule'],
            ['My Commissions', 'Review calculated earnings and commission settlement status.', 'therapist.commissions.index', 'View commissions'],
            ['Notifications', 'Review new assignments and appointment status updates.', 'notifications.index', 'View updates'],
        ] as $section)
            <a href="{{ route($section[2]) }}" class="spa-panel group p-6 transition hover:-translate-y-0.5 hover:border-sage-200 hover:shadow-lg">
                <div class="flex size-11 items-center justify-center rounded-full bg-sage-100 text-sage-700" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M7 3v3m10-3v3M4.5 9h15M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h2 class="mt-5 text-lg font-semibold text-cocoa-950">{{ $section[0] }}</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">{{ $section[1] }}</p>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-sage-700">{{ $section[3] }} <span aria-hidden="true">&rarr;</span></p>
            </a>
        @endforeach
    </div>
@endsection
