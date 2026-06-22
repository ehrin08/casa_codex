@extends('layouts.app')

@section('title', 'Review #'.$review->id.' | Casa Paraiso')
@section('page_title', 'Customer Review Detail')
@section('page_description', 'Review the full customer feedback and its sentiment classification.')

@section('content')
    @php($customer = $review->customerProfile)
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('management.reviews.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to reviews</a>
            @if ($review->appointment)<x-button :href="route('management.appointments.show', $review->appointment)" variant="secondary">View appointment</x-button>@endif
        </div>

        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-cream-200 pb-6">
                <div><p class="spa-detail-label">Review reference</p><h2 class="mt-2 text-2xl font-semibold text-cocoa-950">#{{ $review->id }}</h2></div>
                <x-status-badge :status="$review->sentiment_label" class="px-3 py-1.5" />
            </div>

            <dl class="grid gap-x-8 gap-y-6 py-7 sm:grid-cols-2">
                <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $review->service?->name ?: $review->appointment?->service_name_snapshot ?: 'Service unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Appointment</dt><dd class="spa-detail-value">{{ $review->appointment ? '#'.$review->appointment->id : 'Appointment unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $review->appointment?->therapistProfile ? trim($review->appointment->therapistProfile->first_name.' '.$review->appointment->therapistProfile->last_name) : 'Therapist unavailable' }}</dd></div>
                <div><dt class="spa-detail-label">Rating</dt><dd class="spa-detail-value">{{ $review->rating }} / 5</dd></div>
                <div><dt class="spa-detail-label">Reviewed</dt><dd class="spa-detail-value">{{ $review->reviewed_at->format('F j, Y g:i A') }}</dd></div>
            </dl>

            <section class="rounded-2xl bg-cream-100 p-5 sm:p-6">
                <h3 class="spa-detail-label">Customer comment</h3>
                <p class="mt-3 whitespace-pre-line text-base leading-7 text-cocoa-800">{{ $review->comment ?: 'No comment was provided with this rating.' }}</p>
            </section>
        </x-card>
    </div>
@endsection
