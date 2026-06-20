@extends('layouts.app')

@section('title', 'Therapists | Casa Paraiso')
@section('page_title', 'Therapist Profiles')
@section('page_description', 'Support the Casa Paraiso care team with clear profiles, specialties, and account status.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.therapists.create')">Add therapist</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Therapist</th><th>Contact</th><th>Specialty</th><th>Commission</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($therapists as $therapist)
                    <tr>
                        <td><p class="font-semibold text-cocoa-950">{{ trim($therapist->first_name.' '.$therapist->last_name) }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $therapist->employee_code ?: 'No employee code' }} &middot; {{ $therapist->user ? 'Linked account' : 'No account' }}</p></td>
                        <td class="text-cocoa-600"><p>{{ $therapist->email ?: 'No email' }}</p><p class="mt-1 text-xs">{{ $therapist->phone ?: 'No phone' }}</p></td>
                        <td class="text-cocoa-600">{{ $therapist->specialty ?: 'Not specified' }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ number_format((float) $therapist->commission_rate, 2) }}%</td>
                        <td><x-status-badge :status="$therapist->status" /></td>
                        <td><div class="flex justify-end gap-2"><x-button :href="route('management.therapists.edit', $therapist)" variant="secondary" class="min-h-9 px-3 py-1.5">Edit</x-button><form method="POST" action="{{ route('management.therapists.toggle-status', $therapist) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $therapist->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No therapist profiles found" description="Add a therapist profile to begin planning availability and appointments." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $therapists->links() }}</div>
@endsection
