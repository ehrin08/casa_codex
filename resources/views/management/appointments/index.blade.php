@extends('layouts.app')

@section('title', 'Appointments | Casa Paraiso')
@section('page_title', 'Appointment Management')
@section('page_description', 'Review guest bookings, therapist assignments, schedules, and appointment progress.')

@section('content')
    @php($walkInHasErrors = $errors->any() && old('_modal') === 'walk-in-create')

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.walk-ins.create')" data-modal-open="walk-in-booking-modal">Book walk-in</x-button>
    </div>

    <form method="GET" action="{{ route('management.appointments.index') }}" class="spa-panel mb-7 p-5 sm:p-6">
        <div class="mb-5 flex items-center justify-between gap-3"><div><h2 class="font-semibold text-cocoa-950">Filter appointments</h2><p class="mt-1 text-xs text-cocoa-500">Narrow the schedule by date, status, therapist, or customer.</p></div><span class="hidden rounded-full bg-sage-100 px-2.5 py-1 text-xs font-bold text-sage-700 sm:inline-flex">Schedule tools</span></div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-form.input name="appointment_date" label="Date" type="date" :value="$filters['appointment_date'] ?? ''" />
            <x-form.select name="status" label="Status"><option value="">All statuses</option>@foreach (\App\Models\Appointment::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</x-form.select>
            <x-form.select name="therapist_profile_id" label="Therapist"><option value="">All therapists</option>@foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) ($filters['therapist_profile_id'] ?? '') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}</option>@endforeach</x-form.select>
            <x-form.select name="customer_profile_id" label="Customer"><option value="">All customers</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) ($filters['customer_profile_id'] ?? '') === (string) $customer->id)>{{ trim($customer->first_name.' '.$customer->last_name) }}</option>@endforeach</x-form.select>
        </div>
        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><x-button :href="route('management.appointments.index')" variant="secondary">Clear filters</x-button><x-button type="submit">Apply filters</x-button></div>
    </form>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Therapist</th><th>Schedule</th><th>Status</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($appointments as $appointment)
                    <tr>
                        <td class="font-bold text-cocoa-950">#{{ $appointment->id }}</td>
                        <td class="text-cocoa-700">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Customer unavailable' }}</td>
                        <td class="text-cocoa-700">{{ $appointment->service_name_snapshot ?: 'Service unavailable' }}</td>
                        <td class="text-cocoa-700">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Therapist unavailable' }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $appointment->appointment_date->format('M j, Y') }}<p class="mt-1 text-xs text-cocoa-500">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</p></td>
                        <td><x-status-badge :status="$appointment->status" /></td>
                        <td class="text-right"><x-button :href="route('management.appointments.show', $appointment)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="No appointments found" description="Try clearing the filters or check again after new booking requests arrive." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $appointments->links() }}</div>

    <x-modal id="walk-in-booking-modal" title="Book a walk-in guest" description="Create a same-day or scheduled staff appointment." size="xl" :open-on-load="$walkInHasErrors">
        @include('management.walk-ins._form', [
            'customers' => $walkInCustomers,
            'services' => $walkInServices,
            'therapists' => $walkInTherapists,
            'isModal' => true,
            'formIdPrefix' => 'walk-in-modal',
            'modalKey' => 'walk-in-create',
        ])
    </x-modal>
@endsection
