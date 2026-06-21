@extends('layouts.app')

@section('title', 'Therapists | Casa Paraiso')
@section('page_title', 'Therapist Profiles')
@section('page_description', 'Support the Casa Paraiso care team with clear profiles, specialties, and account status.')

@section('content')
    @php
        $emptyTherapist = new \App\Models\TherapistProfile;
        $createHasErrors = $errors->any() && old('_modal') === 'therapist-create';
        $editHasErrors = $errors->any() && old('_modal') === 'therapist-edit';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.therapists.create')" data-modal-open="therapist-create-modal">Add therapist</x-button>
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
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-button type="button" variant="ghost" class="min-h-9 px-3 py-1.5" data-modal-open="therapist-detail-modal" data-modal-template="therapist-detail-{{ $therapist->id }}">View</x-button>
                                <x-button
                                    :href="route('management.therapists.edit', $therapist)"
                                    variant="secondary"
                                    class="min-h-9 px-3 py-1.5"
                                    data-modal-open="therapist-edit-modal"
                                    data-modal-action="{{ route('management.therapists.update', $therapist) }}"
                                    data-modal-record="{{ json_encode([
                                        '__record_id' => $therapist->id,
                                        'user_id' => $therapist->user_id,
                                        'employee_code' => $therapist->employee_code,
                                        'first_name' => $therapist->first_name,
                                        'last_name' => $therapist->last_name,
                                        'email' => $therapist->email,
                                        'phone' => $therapist->phone,
                                        'specialty' => $therapist->specialty,
                                        'commission_rate' => $therapist->commission_rate,
                                        'status' => $therapist->status,
                                        'hired_at' => $therapist->hired_at?->format('Y-m-d'),
                                        'notes' => $therapist->notes,
                                    ]) }}"
                                >Edit</x-button>
                                <x-button
                                    type="button"
                                    variant="subtle"
                                    class="min-h-9 px-3 py-1.5"
                                    data-confirm-modal="therapist-status-modal"
                                    data-confirm-action="{{ route('management.therapists.toggle-status', $therapist) }}"
                                    data-confirm-heading="{{ $therapist->status === 'active' ? 'Deactivate therapist?' : 'Reactivate therapist?' }}"
                                    data-confirm-message="{{ $therapist->status === 'active' ? trim($therapist->first_name.' '.$therapist->last_name).' will no longer appear as active for management and booking workflows.' : trim($therapist->first_name.' '.$therapist->last_name).' will return to active management workflows.' }}"
                                    data-confirm-label="{{ $therapist->status === 'active' ? 'Deactivate therapist' : 'Reactivate therapist' }}"
                                >{{ $therapist->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button>
                                <noscript><form method="POST" action="{{ route('management.therapists.toggle-status', $therapist) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $therapist->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></noscript>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No therapist profiles found" description="Add a therapist profile to begin planning availability and appointments." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $therapists->links() }}</div>

    @foreach ($therapists as $therapist)
        <template id="therapist-detail-{{ $therapist->id }}">
            <dl class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2"><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value text-lg">{{ trim($therapist->first_name.' '.$therapist->last_name) }}</dd></div>
                <div><dt class="spa-detail-label">Employee code</dt><dd class="spa-detail-value">{{ $therapist->employee_code ?: 'Not assigned' }}</dd></div>
                <div><dt class="spa-detail-label">Status</dt><dd class="mt-2"><x-status-badge :status="$therapist->status" /></dd></div>
                <div><dt class="spa-detail-label">Email</dt><dd class="spa-detail-value break-words">{{ $therapist->email ?: 'Not provided' }}</dd></div>
                <div><dt class="spa-detail-label">Phone</dt><dd class="spa-detail-value">{{ $therapist->phone ?: 'Not provided' }}</dd></div>
                <div><dt class="spa-detail-label">Specialty</dt><dd class="spa-detail-value">{{ $therapist->specialty ?: 'Not specified' }}</dd></div>
                <div><dt class="spa-detail-label">Commission rate</dt><dd class="spa-detail-value">{{ number_format((float) $therapist->commission_rate, 2) }}%</dd></div>
                <div><dt class="spa-detail-label">Linked account</dt><dd class="spa-detail-value">{{ $therapist->user?->email ?? 'No linked account' }}</dd></div>
                <div><dt class="spa-detail-label">Hire date</dt><dd class="spa-detail-value">{{ $therapist->hired_at?->format('M j, Y') ?? 'Not recorded' }}</dd></div>
                <div class="sm:col-span-2"><dt class="spa-detail-label">Notes</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $therapist->notes ?: 'No notes provided.' }}</dd></div>
            </dl>
        </template>
    @endforeach

    <x-modal id="therapist-create-modal" title="Add therapist profile" description="Create a care-team record without leaving the directory." size="xl" :open-on-load="$createHasErrors">
        @include('management.therapists._form', ['therapist' => $emptyTherapist, 'isModal' => true, 'useOld' => $createHasErrors, 'formIdPrefix' => 'therapist-create', 'modalKey' => 'therapist-create'])
    </x-modal>

    <x-modal id="therapist-edit-modal" title="Edit therapist profile" description="Update care-team and account-link details." size="xl" :open-on-load="$editHasErrors">
        @include('management.therapists._form', ['therapist' => $emptyTherapist, 'isModal' => true, 'useOld' => $editHasErrors, 'formIdPrefix' => 'therapist-edit', 'modalKey' => 'therapist-edit', 'formMethod' => 'PUT', 'formAction' => old('_record_action', route('management.therapists.index')), 'recordAction' => old('_record_action', ''), 'submitLabel' => 'Save changes'])
    </x-modal>

    <x-modal id="therapist-detail-modal" title="Therapist details" description="Review the complete care-team record." size="md"><div data-modal-detail-content></div></x-modal>
    <x-confirmation-modal id="therapist-status-modal" />
@endsection
