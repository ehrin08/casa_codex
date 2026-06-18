@extends('layouts.app')

@section('title', 'Availability | Casa Paraiso Spa Management System')
@section('page_title', 'Therapist Availability')
@section('page_description', 'Manage recurring weekly schedules and date-specific therapist availability windows.')

@section('content')
    @php($days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
        <a href="{{ route('management.availability.create') }}" class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Add availability</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr><th class="px-4 py-3">Therapist</th><th class="px-4 py-3">Schedule</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($availabilities as $availability)
                    <tr>
                        <td class="px-4 py-3 font-medium text-zinc-950">{{ trim($availability->therapistProfile->first_name.' '.$availability->therapistProfile->last_name) }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $availability->availability_date?->format('M j, Y') ?? ($days[$availability->day_of_week] ?? 'Not set') }}<p class="mt-1 text-xs text-zinc-500">{{ $availability->availability_date ? 'Specific date' : 'Repeats weekly' }}</p></td>
                        <td class="px-4 py-3 text-zinc-600">{{ date('g:i A', strtotime($availability->start_time)) }} – {{ date('g:i A', strtotime($availability->end_time)) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $availability->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ ucfirst($availability->status) }}</span></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2"><a href="{{ route('management.availability.edit', $availability) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">Edit</a><form method="POST" action="{{ route('management.availability.toggle-status', $availability) }}">@csrf @method('PATCH')<button class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">{{ $availability->status === 'active' ? 'Deactivate' : 'Reactivate' }}</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">No availability records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $availabilities->links() }}</div>
@endsection
