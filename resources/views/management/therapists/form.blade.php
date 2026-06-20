@extends('layouts.app')

@section('title', ($therapist->exists ? 'Edit Therapist' : 'Add Therapist').' | Casa Paraiso')
@section('page_title', $therapist->exists ? 'Edit Therapist Profile' : 'Add Therapist Profile')
@section('page_description', 'Maintain the therapist identity, account link, contact details, and care specialty.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.therapists.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to therapists</a></div>
    <form method="POST" action="{{ $therapist->exists ? route('management.therapists.update', $therapist) : route('management.therapists.store') }}" class="spa-panel mx-auto max-w-4xl space-y-8 p-6 sm:p-8">
        @csrf
        @if ($therapist->exists) @method('PUT') @endif

        <div>
            <h2 class="spa-section-title">Profile details</h2>
            <p class="mt-1 text-sm text-cocoa-500">Account links are optional, while the care profile remains available for scheduling.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="user_id" label="Linked therapist account" hint="Only therapist-role accounts without another profile are available." wrapper-class="sm:col-span-2">
                <option value="">No linked account</option>
                @foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id', $therapist->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach
            </x-form.select>
            <x-form.input name="first_name" label="First name" :value="$therapist->first_name" required />
            <x-form.input name="last_name" label="Last name" :value="$therapist->last_name" />
            <x-form.input name="employee_code" label="Employee code" :value="$therapist->employee_code" />
            <x-form.input name="specialty" label="Specialty" :value="$therapist->specialty" />
            <x-form.input name="email" label="Email" type="email" :value="$therapist->email" />
            <x-form.input name="phone" label="Phone" :value="$therapist->phone" />
            <x-form.input name="commission_rate" label="Commission rate (%)" type="number" :value="$therapist->commission_rate ?? 0" min="0" max="100" step="0.01" required />
            <x-form.select name="status" label="Status" required><option value="active" @selected(old('status', $therapist->status ?: 'active') === 'active')>Active</option><option value="inactive" @selected(old('status', $therapist->status) === 'inactive')>Inactive</option></x-form.select>
            <x-form.input name="hired_at" label="Hire date" type="date" :value="$therapist->hired_at?->format('Y-m-d')" />
            <x-form.textarea name="notes" label="Notes" :value="$therapist->notes" rows="4" wrapper-class="sm:col-span-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end"><x-button :href="route('management.therapists.index')" variant="secondary">Cancel</x-button><x-button type="submit">{{ $therapist->exists ? 'Save changes' : 'Create therapist' }}</x-button></div>
    </form>
@endsection
