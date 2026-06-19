@extends('layouts.app')

@section('title', 'Appointments | Casa Paraiso Spa Management System')
@section('page_title', 'Appointment Management')
@section('page_description', 'Review customer bookings, therapist assignments, schedules, and current appointment status.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
    </div>

    <form method="GET" action="{{ route('management.appointments.index') }}" class="mb-6 rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div><label for="appointment_date" class="block text-sm font-medium text-zinc-700">Date</label><input id="appointment_date" name="appointment_date" type="date" value="{{ $filters['appointment_date'] ?? '' }}" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2"></div>
            <div><label for="status" class="block text-sm font-medium text-zinc-700">Status</label><select id="status" name="status" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2"><option value="">All statuses</option>@foreach (\App\Models\Appointment::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
            <div><label for="therapist_profile_id" class="block text-sm font-medium text-zinc-700">Therapist</label><select id="therapist_profile_id" name="therapist_profile_id" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2"><option value="">All therapists</option>@foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) ($filters['therapist_profile_id'] ?? '') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}</option>@endforeach</select></div>
            <div><label for="customer_profile_id" class="block text-sm font-medium text-zinc-700">Customer</label><select id="customer_profile_id" name="customer_profile_id" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2"><option value="">All customers</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) ($filters['customer_profile_id'] ?? '') === (string) $customer->id)>{{ trim($customer->first_name.' '.$customer->last_name) }}</option>@endforeach</select></div>
        </div>
        <div class="mt-4 flex justify-end gap-3"><a href="{{ route('management.appointments.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Clear</a><button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Apply filters</button></div>
    </form>

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
