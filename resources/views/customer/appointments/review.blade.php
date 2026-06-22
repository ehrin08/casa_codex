@extends('layouts.app')

@section('title', 'Review Appointment #'.$appointment->id.' | Casa Paraiso')
@section('page_title', 'Write a Review')
@section('page_description', 'Share feedback about your completed Casa Paraiso appointment.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6"><a href="{{ route('customer.appointments.show', $appointment) }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to appointment</a></div>

        <form method="POST" action="{{ route('customer.appointments.review.store', $appointment) }}" class="spa-panel p-6 sm:p-8">
            @csrf

            <div class="rounded-2xl bg-sage-50 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.14em] text-sage-700">Completed appointment #{{ $appointment->id }}</p>
                <h2 class="mt-2 text-xl font-semibold text-cocoa-950">{{ $appointment->service_name_snapshot ?: $appointment->service?->name }}</h2>
                <p class="mt-2 text-sm text-cocoa-600">{{ $appointment->appointment_date->format('F j, Y') }} at {{ \Carbon\Carbon::parse($appointment->start_time)->format('g:i A') }}</p>
            </div>

            <div class="mt-7 space-y-6">
                <x-form.select name="rating" label="Rating" required>
                    <option value="">Select a rating</option>
                    @for ($rating = 5; $rating >= 1; $rating--)
                        <option value="{{ $rating }}" @selected((string) old('rating') === (string) $rating)>{{ $rating }} / 5</option>
                    @endfor
                </x-form.select>

                <x-form.textarea name="comment" label="Comment" rows="7" maxlength="2000" hint="Optional. Tell us what went well or what we could improve." />
            </div>

            <p class="mt-6 rounded-xl bg-cream-100 px-4 py-3 text-xs leading-5 text-cocoa-600">Your review is visible to management for service quality tracking.</p>

            <div class="mt-7 flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
                <x-button :href="route('customer.appointments.show', $appointment)" variant="secondary">Cancel</x-button>
                <x-button type="submit">Submit review</x-button>
            </div>
        </form>
    </div>
@endsection
