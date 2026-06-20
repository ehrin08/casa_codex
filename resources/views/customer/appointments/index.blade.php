@extends('layouts.app')

@section('title', 'My Appointments | Casa Paraiso')
@section('page_title', 'My Appointments')
@section('page_description', 'Keep upcoming visits close and revisit your previous Casa Paraiso appointments.')

@section('content')
    <div class="mb-8 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('customer.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Customer dashboard</a>
        <x-button :href="route('customer.appointments.create')">Book appointment</x-button>
    </div>

    <div class="space-y-10">
        <section>
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">Your next visits</p><h2 class="mt-2 spa-section-title">Upcoming appointments</h2></div>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                @forelse ($upcomingAppointments as $appointment)
                    <article class="spa-panel overflow-hidden">
                        <div class="h-1.5 bg-sage-600"></div>
                        <div class="p-5 sm:p-6">
                            <div class="flex items-start justify-between gap-3">
                                <div><h3 class="font-semibold text-cocoa-950">{{ $appointment->service_name_snapshot }}</h3><p class="mt-1 text-sm text-cocoa-500">{{ $appointment->appointment_date->format('F j, Y') }} at {{ date('g:i A', strtotime($appointment->start_time)) }}</p></div>
                                <x-status-badge :status="$appointment->status" />
                            </div>
                            <p class="mt-4 text-sm text-cocoa-600"><span class="font-semibold text-cocoa-800">Therapist:</span> {{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Unavailable' }}</p>
                            <a href="{{ route('customer.appointments.show', $appointment) }}" class="mt-5 inline-flex text-sm font-bold text-sage-700 hover:text-sage-800">View appointment <span class="ml-1" aria-hidden="true">&rarr;</span></a>
                        </div>
                    </article>
                @empty
                    <x-empty-state title="No upcoming appointments" description="When you are ready for your next moment of calm, choose a service and preferred time." class="md:col-span-2">
                        <x-slot:action><x-button :href="route('customer.appointments.create')">Book your first visit</x-button></x-slot:action>
                    </x-empty-state>
                @endforelse
            </div>
            <div class="mt-5">{{ $upcomingAppointments->links() }}</div>
        </section>

        <section>
            <h2 class="spa-section-title">Past appointments</h2>
            <div class="spa-table-wrap mt-5">
                <table class="spa-table">
                    <thead><tr><th>Service</th><th>Date and time</th><th>Therapist</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                    <tbody>
                        @forelse ($pastAppointments as $appointment)
                            <tr><td class="font-semibold text-cocoa-950">{{ $appointment->service_name_snapshot }}</td><td class="whitespace-nowrap text-cocoa-600">{{ $appointment->appointment_date->format('M j, Y') }} at {{ date('g:i A', strtotime($appointment->start_time)) }}</td><td class="text-cocoa-600">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Unavailable' }}</td><td><x-status-badge :status="$appointment->status" /></td><td class="text-right"><x-button :href="route('customer.appointments.show', $appointment)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td></tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state title="No past appointments" description="Completed and previous visits will appear here." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $pastAppointments->links() }}</div>
        </section>
    </div>
@endsection
