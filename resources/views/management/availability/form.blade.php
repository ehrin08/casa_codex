@extends('layouts.app')

@section('title', ($availability->exists ? 'Edit Availability' : 'Add Availability').' | Casa Paraiso')
@section('page_title', $availability->exists ? 'Edit Availability' : 'Add Availability')
@section('page_description', 'Choose a recurring weekday or one specific date, then define the therapist working window.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.availability.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to availability</a></div>
    <form method="POST" action="{{ $availability->exists ? route('management.availability.update', $availability) : route('management.availability.store') }}" class="spa-panel mx-auto max-w-3xl space-y-8 p-6 sm:p-8">
        @csrf
        @if ($availability->exists) @method('PUT') @endif

        <div><h2 class="spa-section-title">Working window</h2><p class="mt-1 text-sm text-cocoa-500">Use either a weekday for a repeating schedule or a date for a one-time availability.</p></div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="therapist_profile_id" label="Therapist" required wrapper-class="sm:col-span-2"><option value="">Select a therapist</option>@foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) old('therapist_profile_id', $availability->therapist_profile_id) === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->status === 'active' ? '' : ' (inactive)' }}</option>@endforeach</x-form.select>
            <x-form.select name="day_of_week" label="Recurring weekday" hint="Choose a weekday or a specific date, not both."><option value="">Not recurring</option>@foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $value => $day)<option value="{{ $value }}" @selected((string) old('day_of_week', $availability->day_of_week) === (string) $value)>{{ $day }}</option>@endforeach</x-form.select>
            <x-form.input name="availability_date" label="Specific date" type="date" :value="$availability->availability_date?->format('Y-m-d')" />
            <x-form.input name="start_time" label="Start time" type="time" :value="$availability->start_time ? substr($availability->start_time, 0, 5) : ''" required />
            <x-form.input name="end_time" label="End time" type="time" :value="$availability->end_time ? substr($availability->end_time, 0, 5) : ''" required />
            <x-form.select name="status" label="Status" required><option value="active" @selected(old('status', $availability->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $availability->status) === 'inactive')>Inactive</option></x-form.select>
            <x-form.textarea name="notes" label="Notes" :value="$availability->notes" rows="4" wrapper-class="sm:col-span-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end"><x-button :href="route('management.availability.index')" variant="secondary">Cancel</x-button><x-button type="submit">{{ $availability->exists ? 'Save changes' : 'Create availability' }}</x-button></div>
    </form>
@endsection
