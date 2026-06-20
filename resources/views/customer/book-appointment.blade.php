@extends('layouts.app')

@section('title', 'Book Appointment | Casa Paraiso')
@section('page_title', 'Book an Appointment')
@section('page_description', 'Choose a service, preferred therapist, and time for your next restorative visit.')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)] lg:items-start">
        <form method="POST" action="{{ route('customer.appointments.store') }}" class="spa-panel p-6 sm:p-8">
            @csrf
            <div class="mb-7">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-sage-700">Appointment request</p>
                <h2 class="mt-2 text-xl font-semibold text-cocoa-950">Plan your visit</h2>
                <p class="mt-1 text-sm text-cocoa-500">Your request will be submitted as pending while the schedule is confirmed.</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <x-form.select name="service_id" label="Service" required wrapper-class="sm:col-span-2">
                    <option value="">Select a service</option>
                    @foreach ($services as $service)<option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>{{ $service->name }} - {{ $service->duration_minutes }} minutes - PHP {{ number_format((float) $service->price, 2) }}</option>@endforeach
                </x-form.select>
                <x-form.select name="therapist_profile_id" label="Preferred therapist" required wrapper-class="sm:col-span-2">
                    <option value="">Select a therapist</option>
                    @foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) old('therapist_profile_id') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->specialty ? ' - '.$therapist->specialty : '' }}</option>@endforeach
                </x-form.select>
                <x-form.input name="appointment_date" label="Appointment date" type="date" :min="now()->toDateString()" required />
                <x-form.input name="appointment_time" label="Start time" type="time" required />
                <x-form.textarea name="notes" label="Notes (optional)" rows="4" maxlength="2000" placeholder="Share preferences or details that may help us prepare for your visit." wrapper-class="sm:col-span-2" />
            </div>

            <div class="mt-7 flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
                <x-button :href="route('customer.index')" variant="secondary">Cancel</x-button>
                <x-button type="submit">Submit appointment request</x-button>
            </div>
        </form>

        <aside class="space-y-5 lg:sticky lg:top-28">
            <x-card>
                <div class="flex items-center justify-between gap-3"><h2 class="font-semibold text-cocoa-950">Service menu</h2><span class="text-xs font-bold uppercase tracking-wider text-sage-700">Active</span></div>
                <div class="mt-4 divide-y divide-cream-200">
                    @forelse ($services as $service)
                        <div class="py-4 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3"><p class="text-sm font-semibold text-cocoa-900">{{ $service->name }}</p><p class="whitespace-nowrap text-sm font-bold text-cocoa-700">PHP {{ number_format((float) $service->price, 2) }}</p></div>
                            <p class="mt-1 text-xs font-medium text-sage-700">{{ $service->duration_minutes }} minutes</p>
                            @if ($service->description)<p class="mt-2 text-sm leading-5 text-cocoa-500">{{ $service->description }}</p>@endif
                        </div>
                    @empty
                        <x-empty-state title="No active services" description="Please check again soon." class="border-0 px-2" />
                    @endforelse
                </div>
            </x-card>
            <x-alert type="warning" title="Before you submit">The time must fit the therapist's active availability and cannot overlap another active appointment. No payment is created by this request.</x-alert>
        </aside>
    </div>
@endsection
