@extends('layouts.app')

@section('title', 'Availability | Casa Paraiso')
@section('page_title', 'Therapist Availability')
@section('page_description', 'Shape recurring weekly schedules and date-specific working windows.')

@section('content')
    @php
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $emptyAvailability = new \App\Models\TherapistAvailability;
        $createHasErrors = $errors->any() && old('_modal') === 'availability-create';
        $editHasErrors = $errors->any() && old('_modal') === 'availability-edit';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.availability.create')" data-modal-open="availability-create-modal">Add availability</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Therapist</th><th>Schedule</th><th>Time</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($availabilities as $availability)
                    @php($therapistName = $availability->therapistProfile ? trim($availability->therapistProfile->first_name.' '.$availability->therapistProfile->last_name) : 'Therapist unavailable')
                    <tr>
                        <td class="font-semibold text-cocoa-950">{{ $therapistName }}</td>
                        <td class="text-cocoa-600">{{ $availability->availability_date?->format('M j, Y') ?? ($days[$availability->day_of_week] ?? 'Not set') }}<p class="mt-1 text-xs text-cocoa-500">{{ $availability->availability_date ? 'Specific date' : 'Repeats weekly' }}</p></td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ date('g:i A', strtotime($availability->start_time)) }} &ndash; {{ date('g:i A', strtotime($availability->end_time)) }}</td>
                        <td><x-status-badge :status="$availability->status" /></td>
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-button type="button" variant="ghost" class="min-h-9 px-3 py-1.5" data-modal-open="availability-detail-modal" data-modal-template="availability-detail-{{ $availability->id }}">View</x-button>
                                <x-button
                                    :href="route('management.availability.edit', $availability)"
                                    variant="secondary"
                                    class="min-h-9 px-3 py-1.5"
                                    data-modal-open="availability-edit-modal"
                                    data-modal-action="{{ route('management.availability.update', $availability) }}"
                                    data-modal-record="{{ json_encode([
                                        '__record_id' => $availability->id,
                                        'therapist_profile_id' => $availability->therapist_profile_id,
                                        'availability_date' => $availability->availability_date?->format('Y-m-d'),
                                        'day_of_week' => $availability->day_of_week,
                                        'start_time' => $availability->start_time ? substr($availability->start_time, 0, 5) : null,
                                        'end_time' => $availability->end_time ? substr($availability->end_time, 0, 5) : null,
                                        'status' => $availability->status,
                                        'notes' => $availability->notes,
                                    ]) }}"
                                >Edit</x-button>
                                <x-button
                                    type="button"
                                    variant="subtle"
                                    class="min-h-9 px-3 py-1.5"
                                    data-confirm-modal="availability-status-modal"
                                    data-confirm-action="{{ route('management.availability.toggle-status', $availability) }}"
                                    data-confirm-heading="{{ $availability->status === 'active' ? 'Deactivate availability?' : 'Reactivate availability?' }}"
                                    data-confirm-message="{{ $availability->status === 'active' ? 'This working window for '.$therapistName.' will no longer permit new bookings.' : 'This working window for '.$therapistName.' will permit scheduling again.' }}"
                                    data-confirm-label="{{ $availability->status === 'active' ? 'Deactivate availability' : 'Reactivate availability' }}"
                                >{{ $availability->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button>
                                <noscript><form method="POST" action="{{ route('management.availability.toggle-status', $availability) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $availability->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></noscript>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No availability records found" description="Add a recurring weekday or a date-specific therapist schedule." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $availabilities->links() }}</div>

    @foreach ($availabilities as $availability)
        @php($detailTherapistName = $availability->therapistProfile ? trim($availability->therapistProfile->first_name.' '.$availability->therapistProfile->last_name) : 'Therapist unavailable')
        <template id="availability-detail-{{ $availability->id }}">
            <dl class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2"><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value text-lg">{{ $detailTherapistName }}</dd></div>
                <div><dt class="spa-detail-label">Schedule type</dt><dd class="spa-detail-value">{{ $availability->availability_date ? 'Specific date' : 'Repeats weekly' }}</dd></div>
                <div><dt class="spa-detail-label">Status</dt><dd class="mt-2"><x-status-badge :status="$availability->status" /></dd></div>
                <div><dt class="spa-detail-label">Date or weekday</dt><dd class="spa-detail-value">{{ $availability->availability_date?->format('F j, Y') ?? ($days[$availability->day_of_week] ?? 'Not set') }}</dd></div>
                <div><dt class="spa-detail-label">Working hours</dt><dd class="spa-detail-value">{{ date('g:i A', strtotime($availability->start_time)) }} &ndash; {{ date('g:i A', strtotime($availability->end_time)) }}</dd></div>
                <div class="sm:col-span-2"><dt class="spa-detail-label">Notes</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $availability->notes ?: 'No notes provided.' }}</dd></div>
            </dl>
        </template>
    @endforeach

    <x-modal id="availability-create-modal" title="Add availability" description="Create a recurring or date-specific working window." :open-on-load="$createHasErrors">
        @include('management.availability._form', ['availability' => $emptyAvailability, 'isModal' => true, 'useOld' => $createHasErrors, 'formIdPrefix' => 'availability-create', 'modalKey' => 'availability-create'])
    </x-modal>

    <x-modal id="availability-edit-modal" title="Edit availability" description="Update the therapist working window." :open-on-load="$editHasErrors">
        @include('management.availability._form', ['availability' => $emptyAvailability, 'isModal' => true, 'useOld' => $editHasErrors, 'formIdPrefix' => 'availability-edit', 'modalKey' => 'availability-edit', 'formMethod' => 'PUT', 'formAction' => old('_record_action', route('management.availability.index')), 'recordAction' => old('_record_action', ''), 'submitLabel' => 'Save changes'])
    </x-modal>

    <x-modal id="availability-detail-modal" title="Availability details" description="Review the complete therapist working window." size="md"><div data-modal-detail-content></div></x-modal>
    <x-confirmation-modal id="availability-status-modal" />
@endsection
