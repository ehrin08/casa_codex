@extends('layouts.app')

@section('title', 'Reviews & Sentiment | Casa Paraiso')
@section('page_title')
Reviews & Sentiment
@endsection
@section('page_description', 'Review customer feedback, ratings, and sentiment trends.')

@section('content')
    @php
        $cards = [
            ['label' => 'Total reviews', 'value' => number_format($summary['total'])],
            ['label' => 'Average rating', 'value' => $summary['total'] ? number_format($summary['average_rating'], 2).' / 5' : 'No ratings'],
            ['label' => 'Positive', 'value' => number_format($summary['positive'])],
            ['label' => 'Neutral', 'value' => number_format($summary['neutral'])],
            ['label' => 'Negative', 'value' => number_format($summary['negative'])],
        ];
    @endphp

    <div class="mb-6"><a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a></div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($cards as $card)
            <x-card class="p-5">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-cocoa-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-semibold text-cocoa-950">{{ $card['value'] }}</p>
            </x-card>
        @endforeach
    </div>

    <form method="GET" action="{{ route('management.reviews.index') }}" class="spa-panel my-7 p-5 sm:p-6">
        <div class="mb-5"><h2 class="font-semibold text-cocoa-950">Filter review insights</h2><p class="mt-1 text-xs text-cocoa-500">Summary cards and review records reflect the selected filters.</p></div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-form.select name="sentiment" label="Sentiment">
                <option value="">All sentiments</option>
                @foreach (\App\Models\CustomerReview::SENTIMENTS as $sentiment)
                    <option value="{{ $sentiment }}" @selected(($filters['sentiment'] ?? '') === $sentiment)>{{ ucfirst($sentiment) }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="rating" label="Rating">
                <option value="">All ratings</option>
                @for ($rating = 5; $rating >= 1; $rating--)
                    <option value="{{ $rating }}" @selected((string) ($filters['rating'] ?? '') === (string) $rating)>{{ $rating }} / 5</option>
                @endfor
            </x-form.select>
            <x-form.select name="service_id" label="Service">
                <option value="">All services</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected((string) ($filters['service_id'] ?? '') === (string) $service->id)>{{ $service->name }}</option>
                @endforeach
            </x-form.select>
            <x-form.input name="date_from" label="From date" type="date" :value="$filters['date_from'] ?? ''" />
            <x-form.input name="date_to" label="To date" type="date" :value="$filters['date_to'] ?? ''" />
        </div>
        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><x-button :href="route('management.reviews.index')" variant="secondary">Clear filters</x-button><x-button type="submit">Apply filters</x-button></div>
    </form>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Reviewed</th><th>Customer</th><th>Service</th><th>Rating</th><th>Sentiment</th><th>Comment</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $review->reviewed_at->format('M j, Y') }}</td>
                        <td class="font-semibold text-cocoa-950">{{ $review->customerProfile ? trim($review->customerProfile->first_name.' '.$review->customerProfile->last_name) : 'Customer unavailable' }}</td>
                        <td class="text-cocoa-700">{{ $review->service?->name ?: $review->appointment?->service_name_snapshot ?: 'Service unavailable' }}</td>
                        <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $review->rating }} / 5</td>
                        <td><x-status-badge :status="$review->sentiment_label" /></td>
                        <td class="max-w-sm text-sm leading-5 text-cocoa-600">{{ $review->comment ? \Illuminate\Support\Str::limit($review->comment, 90) : 'No comment provided.' }}</td>
                        <td class="text-right"><x-button :href="route('management.reviews.show', $review)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state title="No reviews found" description="Completed appointment reviews will appear here when they match the selected filters." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
@endsection
