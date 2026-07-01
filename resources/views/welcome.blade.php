@extends('layouts.app')

@section('title', 'Casa Paraiso | Spa Booking and Wellness Care')
@section('hide_page_header', 'true')

@section('content')
    @php
        $homeUrl = Route::has('home') ? route('home') : url('/');
        $loginUrl = Route::has('login') ? route('login') : $homeUrl;
        $registerUrl = Route::has('register') ? route('register') : $loginUrl;
        $dashboardUrl = Route::has('dashboard') ? route('dashboard') : $homeUrl;
        $customerBookingUrl = Route::has('customer.appointments.create') ? route('customer.appointments.create') : $dashboardUrl;
        $verificationUrl = Route::has('verification.notice') ? route('verification.notice') : $dashboardUrl;

        $bookingUrl = auth()->guest() ? $registerUrl : $dashboardUrl;
        $bookingNote = 'Create a verified customer account before choosing your service and time.';
        $secondaryUrl = '#booking-flow';
        $secondaryLabel = 'How Booking Works';

        if (auth()->check() && auth()->user()->isCustomer()) {
            $bookingUrl = auth()->user()->hasVerifiedEmail() ? $customerBookingUrl : $verificationUrl;
            $bookingNote = auth()->user()->hasVerifiedEmail()
                ? 'Book from your verified Casa Paraiso customer account.'
                : 'Verify your email to unlock customer appointment booking.';
            $secondaryUrl = $dashboardUrl;
            $secondaryLabel = 'Open Customer Dashboard';
        } elseif (auth()->check()) {
            $bookingNote = 'Use your workspace for the Casa Paraiso account tools available to your role.';
            $secondaryUrl = $dashboardUrl;
            $secondaryLabel = 'Open Workspace';
        }

        $services = [
            [
                'title' => 'Massage Therapy',
                'description' => 'Restorative treatments for tired muscles, body tension, and everyday stress.',
            ],
            [
                'title' => 'Body Relaxation',
                'description' => 'Comforting spa care designed for slower breathing and a calmer visit.',
            ],
            [
                'title' => 'Facial Care',
                'description' => 'Refresh-focused services for skin care, renewal, and a lighter glow.',
            ],
            [
                'title' => 'Wellness Treatments',
                'description' => 'Thoughtful service combinations for customers planning regular self-care.',
            ],
        ];

        $benefits = [
            [
                'title' => 'Easy Online Booking',
                'description' => 'Customers can prepare their appointment details before arriving at the spa.',
            ],
            [
                'title' => 'Verified Customer Access',
                'description' => 'Email verification helps keep booking activity tied to real customer accounts.',
            ],
            [
                'title' => 'Therapist Scheduling',
                'description' => 'Appointments are organized around available therapists and service times.',
            ],
            [
                'title' => 'Staff-Supported Walk-ins',
                'description' => 'The front desk can still assist guests who visit Casa Paraiso without an account.',
            ],
        ];

        $steps = [
            'Create Customer Account',
            'Verify your email',
            'Choose your service and time',
            'Visit Casa Paraiso',
        ];
    @endphp

    <section class="relative isolate overflow-hidden bg-cocoa-900 px-6 py-12 text-cream-50 shadow-[0_30px_70px_-42px_rgba(48,33,28,0.75)] sm:px-10 sm:py-16 lg:px-16 lg:py-20">
        <div class="absolute inset-y-0 right-0 -z-10 hidden w-1/2 bg-sage-700/25 lg:block" aria-hidden="true"></div>
        <svg viewBox="0 0 260 260" fill="none" class="absolute -right-10 bottom-0 -z-10 hidden w-72 text-sage-200/30 sm:block" stroke="currentColor" stroke-width="3" aria-hidden="true">
            <path d="M229 21C137 35 70 109 66 222M21 248C51 147 119 70 226 59" stroke-linecap="round" />
            <path d="M229 21c7 101-33 163-108 170-30 3-54-9-54-9s-16-22-13-50C63 62 134 19 229 21Z" stroke-linejoin="round" />
        </svg>

        <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-sage-100">Casa Paraiso Body and Wellness Spa</p>
                <h1 class="mt-5 max-w-3xl text-4xl font-black leading-tight sm:text-5xl lg:text-6xl">
                    Book restorative spa care with less waiting and more clarity.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-8 text-cream-200 sm:text-lg">
                    Casa Paraiso is a spa booking and wellness management system for customer appointments, verified accounts, therapist availability, staff-assisted walk-ins, and service quality feedback.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <x-button :href="$bookingUrl" variant="light">Book an Appointment</x-button>
                    <x-button :href="$secondaryUrl" variant="outline-light">{{ $secondaryLabel }}</x-button>
                </div>

                <p class="mt-5 text-sm leading-6 text-cream-300">
                    Online booking, verified customer accounts, and staff-supported walk-ins.
                    <span class="block text-sage-100">{{ $bookingNote }}</span>
                </p>
            </div>

            <div class="bg-cream-50 p-6 text-cocoa-900 shadow-[0_24px_60px_-36px_rgba(18,12,9,0.8)] sm:p-8">
                <p class="text-sm font-semibold text-sage-700">Plan your visit</p>
                <h2 class="mt-3 text-2xl font-semibold leading-snug">A calmer appointment path from account to arrival.</h2>
                <div class="mt-6 space-y-4">
                    @foreach ($steps as $index => $step)
                        <div class="flex gap-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sm font-bold text-sage-800">{{ $index + 1 }}</span>
                            <div>
                                <h3 class="font-semibold text-cocoa-950">{{ $step }}</h3>
                                <p class="mt-1 text-sm leading-6 text-cocoa-500">
                                    @switch($index)
                                        @case(0)
                                            Register with your name, email, and optional phone number.
                                            @break
                                        @case(1)
                                            Confirm your email so your booking access is ready.
                                            @break
                                        @case(2)
                                            Pick the wellness service and appointment time that fit your day.
                                            @break
                                        @default
                                            Come in for your scheduled Casa Paraiso spa visit.
                                    @endswitch
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="py-10 sm:py-12" aria-labelledby="booking-essentials-heading">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-sage-700">Booking essentials</p>
                <h2 id="booking-essentials-heading" class="mt-2 text-2xl font-semibold text-cocoa-950">What customers need before booking</h2>
            </div>
            <p class="max-w-2xl text-sm leading-6 text-cocoa-500">The primary booking path stays focused on customer account creation, email verification, and appointment preparation.</p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <x-card class="h-full">
                <span class="text-sm font-semibold text-sage-700">Verified account</span>
                <span class="mt-2 block text-sm leading-6 text-cocoa-500">Online booking starts from a customer account connected to a real email address.</span>
            </x-card>

            <x-card class="h-full">
                <span class="text-sm font-semibold text-sage-700">Email verification</span>
                <span class="mt-2 block text-sm leading-6 text-cocoa-500">Customers verify their email before choosing an appointment slot.</span>
            </x-card>

            <x-card class="h-full">
                <span class="text-sm font-semibold text-sage-700">Service and time</span>
                <span class="mt-2 block text-sm leading-6 text-cocoa-500">The booking form checks service duration, therapist availability, and open times.</span>
            </x-card>

            <x-card class="h-full">
                <span class="text-sm font-semibold text-sage-700">Staff-supported walk-ins</span>
                <span class="mt-2 block text-sm leading-6 text-cocoa-500">The front desk can assist guests who visit without creating an online account.</span>
            </x-card>
        </div>
    </section>

    <section class="py-10 sm:py-12">
        <x-page-header
            title="Spa services customers can plan around"
            description="A simple public preview of the wellness categories customers can expect when booking Casa Paraiso care."
            eyebrow="Services preview"
        />

        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $service)
                <x-card class="h-full">
                    <div class="mb-5 flex size-11 items-center justify-center rounded-full bg-sage-100 text-sage-800" aria-hidden="true">
                        <span class="size-3 rounded-full bg-sage-600"></span>
                    </div>
                    <h3 class="text-lg font-semibold text-cocoa-950">{{ $service['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-cocoa-500">{{ $service['description'] }}</p>
                </x-card>
            @endforeach
        </div>
    </section>

    <section id="booking-flow" class="py-10 sm:py-12" aria-labelledby="booking-flow-heading">
        <div class="bg-sage-50 px-6 py-8 sm:px-8 lg:px-10">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold text-sage-700">Booking flow</p>
                <h2 id="booking-flow-heading" class="mt-2 text-2xl font-semibold text-cocoa-950 sm:text-3xl">From customer account to spa visit</h2>
                <p class="mt-3 text-sm leading-6 text-cocoa-600 sm:text-base">Customer booking is designed to protect appointment access while keeping the path clear.</p>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-4">
                @foreach ($steps as $index => $step)
                    <div class="border-l-4 border-sage-300 bg-white p-5">
                        <span class="text-sm font-bold text-gold-600">Step {{ $index + 1 }}</span>
                        <h3 class="mt-3 font-semibold text-cocoa-950">{{ $step }}</h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-10 sm:py-12">
        <x-page-header
            title="Why choose Casa Paraiso"
            description="The system keeps the customer experience welcoming while helping the spa team coordinate each appointment with care."
            eyebrow="Customer-friendly care"
        />

        <div class="mt-7 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($benefits as $benefit)
                <x-card class="h-full">
                    <h3 class="text-lg font-semibold text-cocoa-950">{{ $benefit['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-cocoa-500">{{ $benefit['description'] }}</p>
                </x-card>
            @endforeach
        </div>
    </section>

    <section class="py-10 sm:py-12" aria-labelledby="staff-access-heading">
        <div class="flex flex-col gap-5 border border-cream-200 bg-white p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <p class="text-sm font-semibold text-sage-700">Staff access</p>
                <h2 id="staff-access-heading" class="mt-2 text-xl font-semibold text-cocoa-950">Staff and management can log in here.</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">Customer links stay focused on booking. Staff access remains behind sign-in.</p>
            </div>
            @if (Route::has('login'))
                <x-button :href="$loginUrl" variant="secondary">Go to Login</x-button>
            @endif
        </div>
    </section>
@endsection
