@extends('layouts.app')

@section('title', 'My Appointments | Casa Paraiso Spa Management System')
@section('page_title', 'My Appointments')
@section('page_description', 'Review your upcoming and previous Casa Paraiso appointments.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('customer.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to customer area</a>
        <a href="{{ route('customer.appointments.create') }}" class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Book appointment</a>
    </div>

    <div class="space-y-8">
        <section>
            <h2 class="text-xl font-semibold text-zinc-950">Upcoming appointments</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @forelse ($upcomingAppointments as $appointment)
                    <article class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold text-zinc-950">{{ $appointment->service_name_snapshot }}</h3>
                                <p class="mt-1 text-sm text-zinc-600">{{ $appointment->appointment_date->format('F j, Y') }} at {{ date('g:i A', strtotime($appointment->start_time)) }}</p>
                            </div>
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                        </div>
                        <p class="mt-3 text-sm text-zinc-600">Therapist: {{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Unavailable' }}</p>
                        <a href="{{ route('customer.appointments.show', $appointment) }}" class="mt-4 inline-flex text-sm font-semibold text-emerald-700 hover:text-emerald-800">View details</a>
                    </article>
                @empty
                    <p class="rounded-lg border border-zinc-200 bg-white p-6 text-sm text-zinc-500 md:col-span-2">You have no upcoming appointments.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $upcomingAppointments->links() }}</div>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-zinc-950">Past appointments</h2>
            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500"><tr><th class="px-4 py-3">Service</th><th class="px-4 py-3">Date and time</th><th class="px-4 py-3">Therapist</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($pastAppointments as $appointment)
                            <tr>
                                <td class="px-4 py-3 font-medium text-zinc-950">{{ $appointment->service_name_snapshot }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $appointment->appointment_date->format('M j, Y') }} at {{ date('g:i A', strtotime($appointment->start_time)) }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Unavailable' }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('customer.appointments.show', $appointment) }}" class="font-semibold text-emerald-700 hover:text-emerald-800">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">You have no past appointments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $pastAppointments->links() }}</div>
        </section>
    </div>
@endsection
