@extends('layouts.app')

@section('title', 'Customer Dashboard | Casa Paraiso')
@section('page_title', 'Your Wellness Dashboard')
@section('page_description', 'Plan a restorative visit and follow your Casa Paraiso appointments in one calm space.')

@section('content')
    <x-card class="mb-8 overflow-hidden bg-cocoa-800 text-cream-50" padding="false">
        <div class="relative p-6 sm:p-8">
            <div class="absolute -right-10 -top-16 size-48 rounded-full bg-sage-600/30" aria-hidden="true"></div>
            <div class="relative max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-200">Your next moment of calm</p>
                <h2 class="mt-2 text-2xl font-semibold">What would feel restorative today?</h2>
                <p class="mt-2 text-sm leading-6 text-cream-200">Choose a service, preferred therapist, and time that fits your day.</p>
                <x-button :href="route('customer.appointments.create')" variant="light" class="mt-5">Book an Appointment</x-button>
            </div>
        </div>
    </x-card>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['My Appointments', 'Review upcoming visits and your previous appointment details.', 'customer.appointments.index', 'View appointments'],
            ['Notifications', 'Stay informed when your appointment status changes.', 'notifications.index', 'View updates'],
        ] as $section)
            <a href="{{ route($section[2]) }}" class="spa-panel group p-6 transition hover:-translate-y-0.5 hover:border-sage-200 hover:shadow-lg">
                <h2 class="text-lg font-semibold text-cocoa-950">{{ $section[0] }}</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">{{ $section[1] }}</p>
                <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-sage-700">{{ $section[3] }} <span aria-hidden="true">&rarr;</span></p>
            </a>
        @endforeach
        <div class="spa-panel p-6">
            <h2 class="text-lg font-semibold text-cocoa-950">Service menu</h2>
            <p class="mt-2 text-sm leading-6 text-cocoa-500">Active treatments, durations, and prices are shown inside the booking form before you submit.</p>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.14em] text-cocoa-500">Available while booking</p>
        </div>
        @foreach ([['Future Promotions', 'Seasonal wellness offers will appear here in a future sprint.'], ['Future Reviews', 'Service feedback will be introduced after transactions.']] as $future)
            <div class="rounded-2xl border border-dashed border-cream-300 bg-cream-50/60 p-6">
                <span class="inline-flex rounded-full bg-gold-100 px-2.5 py-1 text-xs font-bold text-gold-600">Coming soon</span>
                <h2 class="mt-5 text-lg font-semibold text-cocoa-800">{{ $future[0] }}</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">{{ $future[1] }}</p>
            </div>
        @endforeach
    </div>
@endsection
