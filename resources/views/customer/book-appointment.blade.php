@extends('layouts.app')

@section('title', 'Book Appointment | Casa Paraiso Spa Management System')
@section('page_title', 'Book an Appointment')
@section('page_description', 'Choose your preferred spa service, therapist, date, and start time. Your request will be submitted as pending.')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <form method="POST" action="{{ route('customer.appointments.store') }}" class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            @csrf

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="service_id" class="block text-sm font-medium text-zinc-700">Service</label>
                    <select id="service_id" name="service_id" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Select a service</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                {{ $service->name }} - {{ $service->duration_minutes }} minutes - PHP {{ number_format((float) $service->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('service_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="therapist_profile_id" class="block text-sm font-medium text-zinc-700">Therapist</label>
                    <select id="therapist_profile_id" name="therapist_profile_id" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        <option value="">Select a therapist</option>
                        @foreach ($therapists as $therapist)
                            <option value="{{ $therapist->id }}" @selected((string) old('therapist_profile_id') === (string) $therapist->id)>
                                {{ trim($therapist->first_name.' '.$therapist->last_name) }}{{ $therapist->specialty ? ' - '.$therapist->specialty : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('therapist_profile_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="appointment_date" class="block text-sm font-medium text-zinc-700">Appointment date</label>
                    <input id="appointment_date" name="appointment_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('appointment_date') }}" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    @error('appointment_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-zinc-700">Start time</label>
                    <input id="appointment_time" name="appointment_time" type="time" value="{{ old('appointment_time') }}" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    @error('appointment_time') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-zinc-700">Notes <span class="font-normal text-zinc-500">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="4" maxlength="2000" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20" placeholder="Share preferences or details that may help us prepare for your visit.">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-zinc-200 pt-5">
                <a href="{{ route('customer.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Cancel</a>
                <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Submit appointment request</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-zinc-950">Active services</h2>
                <div class="mt-4 space-y-4">
                    @forelse ($services as $service)
                        <div class="border-b border-zinc-100 pb-4 last:border-0 last:pb-0">
                            <p class="text-sm font-semibold text-zinc-900">{{ $service->name }}</p>
                            <p class="mt-1 text-xs text-zinc-500">{{ $service->duration_minutes }} minutes &middot; PHP {{ number_format((float) $service->price, 2) }}</p>
                            @if ($service->description)
                                <p class="mt-2 text-sm leading-5 text-zinc-600">{{ $service->description }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-600">No active services are currently available.</p>
                    @endforelse
                </div>
            </div>

            <p class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Your request must fit the therapist's active availability and must not overlap another active appointment. Submitting this form does not create a payment.
            </p>
        </aside>
    </div>
@endsection
