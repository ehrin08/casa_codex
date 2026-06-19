@extends('layouts.app')

@section('title', 'Appointments | Casa Paraiso Spa Management System')
@section('page_title', 'Appointment Management')
@section('page_description', 'Review customer bookings, therapist assignments, schedules, and current appointment status.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Reference</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Therapist</th>
                    <th class="px-4 py-3">Schedule</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($appointments as $appointment)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-zinc-950">#{{ $appointment->id }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Customer unavailable' }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $appointment->service_name_snapshot ?: 'Service unavailable' }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Therapist unavailable' }}</td>
                        <td class="px-4 py-3 text-zinc-600">
                            {{ $appointment->appointment_date->format('M j, Y') }}
                            <p class="mt-1 text-xs text-zinc-500">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('management.appointments.show', $appointment) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-zinc-500">No appointments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $appointments->links() }}</div>
@endsection
