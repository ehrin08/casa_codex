@extends('layouts.app')

@section('title', 'My Schedule | Casa Paraiso Spa Management System')
@section('page_title', 'My Schedule')
@section('page_description', 'View today\'s work and your upcoming assigned appointments.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('therapist.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to therapist area</a>
    </div>

    <div class="space-y-8">
        <section>
            <h2 class="text-xl font-semibold text-zinc-950">Today</h2>
            <p class="mt-1 text-sm text-zinc-500">{{ today()->format('F j, Y') }}</p>
            <div class="mt-4 space-y-3">
                @forelse ($todayAppointments as $appointment)
                    <article class="flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold text-zinc-950">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }} - {{ $appointment->service_name_snapshot }}</p>
                            <p class="mt-1 text-sm text-zinc-600">Customer: {{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</p>
                            @if ($appointment->notes)<p class="mt-2 text-sm text-zinc-500">{{ \Illuminate\Support\Str::limit($appointment->notes, 120) }}</p>@endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                            <a href="{{ route('therapist.appointments.show', $appointment) }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">View</a>
                        </div>
                    </article>
                @empty
                    <p class="rounded-lg border border-zinc-200 bg-white p-6 text-sm text-zinc-500">No appointments are assigned to you today.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-xl font-semibold text-zinc-950">Upcoming</h2>
            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Service</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($upcomingAppointments as $appointment)
                            <tr>
                                <td class="px-4 py-3 font-medium text-zinc-950">{{ $appointment->appointment_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Unavailable' }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ $appointment->service_name_snapshot }}</td>
                                <td class="px-4 py-3 text-zinc-600">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('therapist.appointments.show', $appointment) }}" class="font-semibold text-emerald-700 hover:text-emerald-800">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No upcoming appointments are assigned to you.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $upcomingAppointments->links() }}</div>
        </section>
    </div>
@endsection
