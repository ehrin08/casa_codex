@php
    $isModal = $isModal ?? false;
    $useOld = $useOld ?? true;
    $formIdPrefix = $formIdPrefix ?? 'availability-form';
    $formMethod = $formMethod ?? ($availability->exists ? 'PUT' : 'POST');
    $formAction = $formAction ?? ($availability->exists ? route('management.availability.update', $availability) : route('management.availability.store'));
    $submitLabel = $submitLabel ?? ($availability->exists ? 'Save Availability' : 'Create Availability');
    $modalKey = $modalKey ?? null;
    $recordAction = $recordAction ?? $formAction;
    $fieldValue = fn (string $name, mixed $value = null) => $useOld ? old($name, $value) : $value;
@endphp

<form method="POST" action="{{ $formAction }}" @if ($isModal) data-record-form @endif class="{{ $isModal ? 'space-y-8 p-5 sm:p-6' : 'spa-panel mx-auto max-w-3xl space-y-8 p-6 sm:p-8' }}">
    @csrf
    @if (strtoupper($formMethod) !== 'POST') @method($formMethod) @endif
    @if ($modalKey)<input type="hidden" name="_modal" value="{{ $modalKey }}">@endif
    @if ($isModal)<input type="hidden" name="_record_action" value="{{ $recordAction }}">@endif

    <div><h3 class="spa-section-title">Working window</h3><p class="mt-1 text-sm text-cocoa-500">Use either a weekday for a repeating schedule or a date for a one-time availability.</p></div>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.select name="therapist_profile_id" label="Therapist" :id="$formIdPrefix.'-therapist'" :use-old="$useOld" required wrapper-class="sm:col-span-2"><option value="">Select a therapist</option>@foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) $fieldValue('therapist_profile_id', $availability->therapist_profile_id) === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->status === 'active' ? '' : ' (inactive)' }}</option>@endforeach</x-form.select>
        <x-form.select name="day_of_week" label="Recurring weekday" :id="$formIdPrefix.'-weekday'" :use-old="$useOld" hint="Choose a weekday or a specific date, not both."><option value="">Not recurring</option>@foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $value => $day)<option value="{{ $value }}" @selected((string) $fieldValue('day_of_week', $availability->day_of_week) === (string) $value)>{{ $day }}</option>@endforeach</x-form.select>
        <x-form.input name="availability_date" label="Specific date" :id="$formIdPrefix.'-date'" type="date" :value="$availability->availability_date?->format('Y-m-d')" :use-old="$useOld" />
        <x-form.input name="start_time" label="Start time" :id="$formIdPrefix.'-start-time'" type="time" :value="$availability->start_time ? substr($availability->start_time, 0, 5) : ''" :use-old="$useOld" required />
        <x-form.input name="end_time" label="End time" :id="$formIdPrefix.'-end-time'" type="time" :value="$availability->end_time ? substr($availability->end_time, 0, 5) : ''" :use-old="$useOld" required />
        <x-form.select name="status" label="Status" :id="$formIdPrefix.'-status'" :use-old="$useOld" required><option value="active" @selected($fieldValue('status', $availability->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected($fieldValue('status', $availability->status) === 'inactive')>Inactive</option></x-form.select>
        <x-form.textarea name="notes" label="Notes" :id="$formIdPrefix.'-notes'" :value="$availability->notes" :use-old="$useOld" rows="4" wrapper-class="sm:col-span-2" />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
        @if ($isModal)<x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>@else<x-button :href="route('management.availability.index')" variant="secondary">Cancel</x-button>@endif
        <x-button type="submit">{{ $submitLabel }}</x-button>
    </div>
</form>
