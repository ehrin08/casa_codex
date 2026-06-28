@extends('layouts.app')

@section('title', 'Management Dashboard | Casa Paraiso')
@section('page_title', 'Management Dashboard')
@section('page_description', 'A clear view of Casa Paraiso business operations and daily status.')

@section('content')
    <div class="mb-8 flex flex-col gap-4 rounded-2xl bg-cocoa-800 p-6 text-cream-50 sm:flex-row sm:items-center sm:justify-between sm:p-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-200">Business overview</p>
            <h2 class="mt-2 text-2xl font-semibold">Welcome, {{ auth()->user()->name }}</h2>
            <p class="mt-2 text-sm leading-6 text-cream-200">Here's what is happening at the spa today.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <x-button :href="route('management.walk-ins.create')" variant="light">Book Walk-in</x-button>
        </div>
    </div>

    {{-- Business Summary KPI Cards --}}
    <div class="mb-8">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">Business Summary</h3>
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="spa-panel p-6">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Today’s Appointments</p>
                <p class="mt-2 text-3xl font-semibold text-cocoa-950">{{ $todayAppointments }}</p>
                <a href="{{ route('management.appointments.index') }}" class="mt-4 block text-sm font-medium text-sage-700 hover:text-sage-900">View appointments &rarr;</a>
            </div>

            <div class="spa-panel p-6">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Today’s Paid Revenue</p>
                <p class="mt-2 text-3xl font-semibold text-cocoa-950">${{ number_format($todayPaidRevenue, 2) }}</p>
                <a href="{{ route('management.transactions.index') }}" class="mt-4 block text-sm font-medium text-sage-700 hover:text-sage-900">View transactions &rarr;</a>
            </div>

            <div class="spa-panel p-6">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Pending Payments</p>
                <p class="mt-2 text-3xl font-semibold text-cocoa-950">{{ $pendingPayments }}</p>
                <a href="{{ route('management.transactions.index') }}" class="mt-4 block text-sm font-medium text-sage-700 hover:text-sage-900">Record payment &rarr;</a>
            </div>

            <div class="spa-panel p-6">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Therapist Workload</p>
                <p class="mt-2 text-3xl font-semibold text-cocoa-950">{{ $therapistsWorking }} <span class="text-sm font-normal text-cocoa-500">Active Today</span></p>
                <a href="{{ route('management.availability.index') }}" class="mt-4 block text-sm font-medium text-sage-700 hover:text-sage-900">View availability &rarr;</a>
            </div>
        </div>
    </div>

    {{-- Primary Staff Actions --}}
    <div class="mb-8">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">Primary Actions</h3>
        <div class="flex flex-wrap gap-4">
            <x-button :href="route('management.walk-ins.create')">Book Walk-in</x-button>
            <x-button :href="route('management.appointments.index')" variant="secondary">Today’s Appointments</x-button>
            <x-button :href="route('management.transactions.index')" variant="secondary">Record Payment</x-button>
            <x-button :href="route('management.reports.index')" variant="secondary">Print Reports</x-button>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-2">
        {{-- Attention Needed --}}
        <div>
            <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">Attention Needed</h3>
            <div class="spa-panel divide-y divide-cocoa-100 overflow-hidden">
                @forelse($attentionNeeded as $issue)
                    <div class="flex items-center justify-between p-5">
                        <div class="flex items-center gap-3">
                            @if($issue['type'] === 'negative_review')
                                <span class="flex size-8 items-center justify-center rounded-full bg-red-100 text-red-600">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </span>
                            @else
                                <span class="flex size-8 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </span>
                            @endif
                            <p class="text-sm font-medium text-cocoa-950">{{ $issue['message'] }}</p>
                        </div>
                        <a href="{{ route($issue['route'], $issue['route_params']) }}" class="text-sm font-semibold text-sage-700 hover:text-sage-900">{{ $issue['label'] }}</a>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-green-50 text-green-600 mb-3">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        </span>
                        <p class="text-sm font-medium text-cocoa-900">No urgent items need attention right now.</p>
                        <p class="text-sm text-cocoa-500 mt-1">Everything is running smoothly.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Business Insights Snapshot --}}
        <div>
            <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">Business Insights</h3>
            <div class="spa-panel p-6">
                @if($insights['most_booked_service'])
                    <div class="mb-6">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Most Booked Service Today</p>
                        <p class="mt-2 text-lg font-semibold text-cocoa-950">{{ $insights['most_booked_service'] }}</p>
                        <p class="text-sm text-cocoa-500">{{ $insights['most_booked_count'] }} bookings</p>
                    </div>
                @else
                    <div class="mb-6">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Most Booked Service Today</p>
                        <p class="mt-2 text-sm text-cocoa-500">No services booked yet today.</p>
                    </div>
                @endif
                <div class="mt-4 pt-4 border-t border-cocoa-100">
                    <a href="{{ route('management.analytics.index') }}" class="text-sm font-medium text-sage-700 hover:text-sage-900">View full analytics &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Management Links --}}
    <div class="mt-8">
        <h3 class="mb-4 text-xs font-bold uppercase tracking-[0.18em] text-cocoa-500">Manage Records</h3>
        <div class="spa-panel p-4">
            <div class="flex flex-wrap gap-x-6 gap-y-3">
                <a href="{{ route('management.services.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Services</a>
                <a href="{{ route('management.therapists.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Therapists</a>
                <a href="{{ route('management.customers.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Customers</a>
                <a href="{{ route('management.availability.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Therapist Workload</a>
                <a href="{{ route('management.promotions.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Promotions</a>
                <a href="{{ route('management.rfm.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">RFM Scores</a>
                <a href="{{ route('management.reviews.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Reviews & Sentiment</a>
                <a href="{{ route('management.analytics.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Analytics</a>
                <a href="{{ route('management.reports.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Reports</a>
                <a href="{{ route('management.commissions.index') }}" class="text-sm font-medium text-cocoa-600 hover:text-cocoa-900">Commissions</a>
            </div>
        </div>
    </div>
@endsection
