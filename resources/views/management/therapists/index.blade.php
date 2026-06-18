@extends('layouts.app')

@section('title', 'Therapists | Casa Paraiso Spa Management System')
@section('page_title', 'Therapist Profiles')
@section('page_description', 'Manage therapist contact details, specialties, commissions, account links, and status.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
        <a href="{{ route('management.therapists.create') }}" class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Add therapist</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr><th class="px-4 py-3">Therapist</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Specialty</th><th class="px-4 py-3">Commission</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($therapists as $therapist)
                    <tr>
                        <td class="px-4 py-3"><p class="font-medium text-zinc-950">{{ trim($therapist->first_name.' '.$therapist->last_name) }}</p><p class="mt-1 text-xs text-zinc-500">{{ $therapist->employee_code ?: 'No employee code' }} · {{ $therapist->user ? 'Linked account' : 'No account' }}</p></td>
                        <td class="px-4 py-3 text-zinc-600"><p>{{ $therapist->email ?: 'No email' }}</p><p class="mt-1 text-xs">{{ $therapist->phone ?: 'No phone' }}</p></td>
                        <td class="px-4 py-3 text-zinc-600">{{ $therapist->specialty ?: 'Not specified' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ number_format((float) $therapist->commission_rate, 2) }}%</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $therapist->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ ucfirst($therapist->status) }}</span></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2"><a href="{{ route('management.therapists.edit', $therapist) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">Edit</a><form method="POST" action="{{ route('management.therapists.toggle-status', $therapist) }}">@csrf @method('PATCH')<button class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">{{ $therapist->status === 'active' ? 'Deactivate' : 'Reactivate' }}</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">No therapist profiles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $therapists->links() }}</div>
@endsection
