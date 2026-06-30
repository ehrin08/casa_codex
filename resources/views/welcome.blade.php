@extends('layouts.app')

@section('title', 'Casa Paraiso | Body and Wellness Spa')
@section('hide_page_header', 'true')

@section('content')
    <section class="relative isolate overflow-hidden rounded-3xl bg-cocoa-900 px-6 py-12 text-cream-50 shadow-[0_30px_70px_-35px_rgba(48,33,28,0.8)] sm:px-10 sm:py-16 lg:px-16 lg:py-20">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_78%_15%,rgba(199,219,192,0.28),transparent_32%),radial-gradient(circle_at_92%_82%,rgba(217,189,131,0.2),transparent_28%)]"></div>
        <svg viewBox="0 0 240 300" fill="none" class="absolute -bottom-16 -right-5 -z-10 w-52 text-sage-200/20 sm:w-72" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="M213 18C128 32 65 101 61 205M19 289C47 190 110 117 210 67" stroke-linecap="round"/><path d="M213 18c6 93-31 151-100 158-28 3-50-8-50-8s-15-20-12-46C59 57 125 16 213 18Z" stroke-linejoin="round"/></svg>

        <div class="max-w-3xl">
            <div class="inline-flex items-center gap-2 rounded-full border border-cream-50/15 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-sage-100">
                <span class="size-1.5 rounded-full bg-gold-300"></span>
                Body and Wellness Spa
            </div>
            <h1 class="mt-7 text-4xl font-black tracking-[0.08em] sm:text-5xl lg:text-6xl">CASA PARAISO</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-cream-200 sm:text-xl">A calmer way to plan restorative care. Book spa appointments, explore wellness services, and keep every visit beautifully organized.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                @guest
                    <x-button :href="route('register')" variant="light">Register as a customer</x-button>
                    <x-button :href="route('login')" variant="outline-light">Log in to your account</x-button>
                @else
                    <x-button :href="route('dashboard')" variant="light">Open my dashboard</x-button>
                    @if (auth()->user()->isCustomer())
                        <x-button :href="route('customer.appointments.create')" variant="outline-light">Book an appointment</x-button>
                    @endif
                @endguest
            </div>
        </div>
    </section>

    <section class="py-12 sm:py-16">
        <x-page-header title="Wellness, thoughtfully managed" description="Each workspace keeps the right tools close while preserving a warm, uncluttered experience for the people who use them." eyebrow="One connected spa experience" />

        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['number' => '01', 'title' => 'Management', 'text' => 'Coordinate services, therapists, customers, availability, and appointment progress from one clear operations hub.'],
                ['number' => '02', 'title' => 'Therapist', 'text' => 'See today\'s guests and upcoming assignments in a focused schedule designed for effortless preparation.'],
                ['number' => '03', 'title' => 'Customer', 'text' => 'Choose a treatment, request a preferred therapist and time, then follow every appointment update.'],
            ] as $area)
                <x-card class="group relative overflow-hidden transition hover:-translate-y-1 hover:border-sage-200 hover:shadow-lg">
                    <span class="text-xs font-black tracking-[0.2em] text-gold-600">{{ $area['number'] }}</span>
                    <h2 class="mt-5 text-xl font-semibold text-cocoa-900">{{ $area['title'] }}</h2>
                    <p class="mt-3 text-sm leading-6 text-cocoa-500">{{ $area['text'] }}</p>
                    <div class="absolute -bottom-8 -right-8 size-24 rounded-full bg-sage-100 transition group-hover:scale-125" aria-hidden="true"></div>
                </x-card>
            @endforeach
        </div>
    </section>

    <section class="grid gap-6 rounded-3xl border border-cream-200 bg-cream-50 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center lg:p-10">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-sage-700">Designed around your visit</p>
            <h2 class="mt-2 text-2xl font-semibold text-cocoa-900">Simple booking. Clear schedules. More room to unwind.</h2>
            <p class="mt-3 max-w-3xl leading-7 text-cocoa-500">Casa Paraiso brings appointment booking and service management together so the team can spend less time chasing details and more time creating a welcoming spa experience.</p>
        </div>
        @guest
            <x-button :href="route('register')">Begin your wellness journey</x-button>
        @else
            <x-button :href="route('dashboard')">Continue to your workspace</x-button>
        @endguest
    </section>
@endsection
