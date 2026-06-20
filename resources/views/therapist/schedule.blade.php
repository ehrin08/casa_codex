@extends('layouts.app')

@section('title', 'My Schedule | Casa Paraiso')
@section('page_title', 'My Schedule')
@section('page_description', 'A focused view of today\'s guests and your upcoming assigned appointments.')

@section('content')
    <div class="mb-8"><a href="{{ route('therapist.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Therapist dashboard</a></div>

    <div class="space-y-10">
        <section>
            <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">{{ today()->format('l') }}</p><h2 class="mt-2 spa-section-title">Today</h2></div><p class="rounded-full bg-cream-200 px-3 py-1 text-sm font-semibold text-cocoa-600">{{ today()->format('F j, Y') }}</p></div>
            <div class="mt-5 space-y-4">
                @forelse ($todayAppointments as $appointment)
                    <article class="spa-panel flex flex-col gap-5 border-l-4 border-l-sage-600 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                        <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</p><h3 class="mt-1 font-semibold text-cocoa-950">{{ $appointment->service_name_snapshot }}</h3><p class="mt-2 text-sm text-cocoa-600">Customer: {{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</p>@if ($appointment->notes)<p class="mt-2 text-sm text-cocoa-500">{{ \Illuminate\Support\Str::limit($appointment->notes, 120) }}</p>@endif</div>
                        <div class="flex shrink-0 items-center gap-3"><x-status-badge :status="$appointment->status" /><x-button :href="route('therapist.appointments.show', $appointment)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></div>
                    </article>
                @empty
                    <x-empty-state title="No appointments today" description="Your schedule is clear for today. Upcoming assignments appear below." />
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="spa-section-title">Upcoming appointments</h2>
            <div class="spa-table-wrap mt-5">
                <table class="spa-table">
                    <thead><tr><th>Date</th><th>Time</th><th>Customer</th><th>Service</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse ($upcomingAppointments as $appointment)
                            <tr><td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $appointment->appointment_date->format('M j, Y') }}</td><td class="whitespace-nowrap text-cocoa-600">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</td><td class="text-cocoa-600">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</td><td class="text-cocoa-600">{{ $appointment->service_name_snapshot }}</td><td><x-status-badge :status="$appointment->status" /></td><td class="text-right"><x-button :href="route('therapist.appointments.show', $appointment)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td></tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state title="No upcoming appointments" description="New assigned bookings will appear here." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $upcomingAppointments->links() }}</div>
        </section>
    </div>
@endsection
