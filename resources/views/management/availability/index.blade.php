@extends('layouts.app')

@section('title', 'Availability | Casa Paraiso')
@section('page_title', 'Therapist Availability')
@section('page_description', 'Shape recurring weekly schedules and date-specific working windows.')

@section('content')
    @php
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    @endphp
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.availability.create')">Add availability</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Therapist</th><th>Schedule</th><th>Time</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($availabilities as $availability)
                    <tr>
                        <td class="font-semibold text-cocoa-950">{{ trim($availability->therapistProfile->first_name.' '.$availability->therapistProfile->last_name) }}</td>
                        <td class="text-cocoa-600">{{ $availability->availability_date?->format('M j, Y') ?? ($days[$availability->day_of_week] ?? 'Not set') }}<p class="mt-1 text-xs text-cocoa-500">{{ $availability->availability_date ? 'Specific date' : 'Repeats weekly' }}</p></td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ date('g:i A', strtotime($availability->start_time)) }} &ndash; {{ date('g:i A', strtotime($availability->end_time)) }}</td>
                        <td><x-status-badge :status="$availability->status" /></td>
                        <td><div class="flex justify-end gap-2"><x-button :href="route('management.availability.edit', $availability)" variant="secondary" class="min-h-9 px-3 py-1.5">Edit</x-button><form method="POST" action="{{ route('management.availability.toggle-status', $availability) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $availability->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No availability records found" description="Add a recurring weekday or a date-specific therapist schedule." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $availabilities->links() }}</div>
@endsection
