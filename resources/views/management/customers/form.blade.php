@extends('layouts.app')

@section('title', ($customer->exists ? 'Edit Customer' : 'Add Customer').' | Casa Paraiso')
@section('page_title', $customer->exists ? 'Edit Customer Profile' : 'Add Customer Profile')
@section('page_description', 'Maintain a linked customer account or a standalone walk-in guest record.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.customers.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to customers</a></div>
    <form method="POST" action="{{ $customer->exists ? route('management.customers.update', $customer) : route('management.customers.store') }}" class="spa-panel mx-auto max-w-4xl space-y-8 p-6 sm:p-8">
        @csrf
        @if ($customer->exists) @method('PUT') @endif

        <div><h2 class="spa-section-title">Guest details</h2><p class="mt-1 text-sm text-cocoa-500">Contact and profile information helps the team prepare a more thoughtful visit.</p></div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.select name="user_id" label="Linked customer account" hint="Only customer-role accounts without another profile are available." wrapper-class="sm:col-span-2">
                <option value="">No linked account (walk-in)</option>
                @foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) old('user_id', $customer->user_id) === (string) $user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach
            </x-form.select>
            <x-form.input name="first_name" label="First name" :value="$customer->first_name" required />
            <x-form.input name="last_name" label="Last name" :value="$customer->last_name" />
            <x-form.input name="email" label="Email" type="email" :value="$customer->email" />
            <x-form.input name="phone" label="Phone" :value="$customer->phone" />
            <x-form.input name="birth_date" label="Birth date" type="date" :value="$customer->birth_date?->format('Y-m-d')" :max="now()->format('Y-m-d')" />
            <x-form.select name="gender" label="Gender"><option value="">Prefer not to specify</option><option value="female" @selected(old('gender', $customer->gender) === 'female')>Female</option><option value="male" @selected(old('gender', $customer->gender) === 'male')>Male</option><option value="other" @selected(old('gender', $customer->gender) === 'other')>Other</option><option value="prefer_not_to_say" @selected(old('gender', $customer->gender) === 'prefer_not_to_say')>Prefer not to say</option></x-form.select>
            <x-form.select name="is_active" label="Status" required><option value="1" @selected((string) old('is_active', (int) ($customer->exists ? $customer->is_active : true)) === '1')>Active</option><option value="0" @selected((string) old('is_active', (int) $customer->is_active) === '0')>Inactive</option></x-form.select>
            <x-form.textarea name="address" label="Address" :value="$customer->address" rows="3" wrapper-class="sm:col-span-2" />
            <x-form.textarea name="notes" label="Notes" :value="$customer->notes" rows="4" wrapper-class="sm:col-span-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end"><x-button :href="route('management.customers.index')" variant="secondary">Cancel</x-button><x-button type="submit">{{ $customer->exists ? 'Save changes' : 'Create customer' }}</x-button></div>
    </form>
@endsection
