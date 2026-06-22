@extends('layouts.app')

@section('title', 'Analytics Dashboard | Casa Paraiso')
@section('page_title', 'Analytics Dashboard')
@section('page_description', 'Revenue, booking, customer, promotion, and service insights for '.$rangeLabel.'.')

@section('content')
    @php
        $money = fn ($amount) => 'PHP '.number_format((float) $amount, 2);
        $revenue = $analytics['revenue'];
        $bookings = $analytics['bookingPeriods'];
        $promotions = $analytics['promotions'];
        $reviews = $analytics['reviews'];
        $maxDay = max(1, (int) $bookings['days']->max('count'));
        $maxHour = max(1, (int) $bookings['hours']->max('count'));
    @endphp

    <section class="spa-panel mb-7 p-5 sm:p-6" aria-labelledby="analytics-filters-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="analytics-filters-heading" class="spa-section-title">Filters</h2>
                <p class="mt-1 text-sm text-cocoa-500">Showing activity from {{ $rangeLabel }}.</p>
            </div>
            <x-button :href="route('management.analytics.index')" variant="ghost">Reset filters</x-button>
        </div>

        <form method="GET" action="{{ route('management.analytics.index') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-form.select name="range" label="Date range" :use-old="false">
                @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'custom' => 'Custom'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>
                @endforeach
            </x-form.select>

            <x-form.input name="date_from" type="date" label="From" :value="$filters['date_from']" :use-old="false" />
            <x-form.input name="date_to" type="date" label="To" :value="$filters['date_to']" :use-old="false" />

            <x-form.select name="service_id" label="Service" :use-old="false">
                <option value="">All services</option>
                @foreach ($services as $service)
                    <option value="{{ $service->id }}" @selected($filters['service_id'] === $service->id)>{{ $service->name }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="therapist_profile_id" label="Therapist" :use-old="false">
                <option value="">All therapists</option>
                @foreach ($therapists as $therapist)
                    <option value="{{ $therapist->id }}" @selected($filters['therapist_profile_id'] === $therapist->id)>
                        {{ trim($therapist->first_name.' '.$therapist->last_name) }}
                    </option>
                @endforeach
            </x-form.select>

            <div class="md:col-span-2 xl:col-span-5">
                <x-button type="submit">Apply filters</x-button>
            </div>
        </form>
    </section>

    @unless ($analytics['hasData'])
        <x-empty-state
            class="mb-7"
            title="No analytics data found"
            description="No transactions, appointments, customer segments, promotion usage, or reviews match the selected filters."
        />
    @endunless

    <section aria-labelledby="revenue-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="revenue-heading" class="spa-section-title">Revenue Overview</h2>
                <p class="mt-1 text-sm text-cocoa-500">Confirmed revenue includes paid transactions only.</p>
            </div>
            @if (Route::has('management.transactions.index'))
                <a href="{{ route('management.transactions.index') }}" class="text-sm font-semibold text-sage-700 hover:text-sage-800">View transactions &rarr;</a>
            @endif
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="spa-panel p-5">
                <p class="spa-detail-label">Net paid revenue</p>
                <p class="mt-2 text-2xl font-semibold text-cocoa-950">{{ $money($revenue['net_revenue']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">{{ $revenue['paid_count'] }} paid {{ Str::plural('transaction', $revenue['paid_count']) }}</p>
            </article>
            <article class="spa-panel p-5">
                <p class="spa-detail-label">Gross subtotal</p>
                <p class="mt-2 text-2xl font-semibold text-cocoa-950">{{ $money($revenue['gross_subtotal']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">Before paid discounts</p>
            </article>
            <article class="spa-panel p-5">
                <p class="spa-detail-label">Paid discounts</p>
                <p class="mt-2 text-2xl font-semibold text-gold-600">{{ $money($revenue['discount_total']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">Deducted from paid sales</p>
            </article>
            <article class="rounded-2xl border border-gold-300 bg-gold-100/60 p-5">
                <p class="spa-detail-label">Pending</p>
                <p class="mt-2 text-2xl font-semibold text-cocoa-950">{{ $money($revenue['pending_total']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">{{ $revenue['pending_count'] }} pending, excluded from revenue</p>
            </article>
            <article class="rounded-2xl border border-cream-300 bg-cream-100 p-5">
                <p class="spa-detail-label">Void</p>
                <p class="mt-2 text-2xl font-semibold text-cocoa-700">{{ $money($revenue['void_total']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">{{ $revenue['void_count'] }} void, excluded from revenue</p>
            </article>
        </div>

        @if ($revenue['daily']->isNotEmpty())
            <div class="spa-table-wrap mt-4">
                <table class="spa-table">
                    <thead><tr><th>Date</th><th>Paid transactions</th><th class="text-right">Net revenue</th></tr></thead>
                    <tbody>
                        @foreach ($revenue['daily'] as $day)
                            <tr>
                                <td class="font-semibold text-cocoa-900">{{ \Carbon\CarbonImmutable::parse($day['date'])->format('M j, Y') }}</td>
                                <td>{{ $day['count'] }}</td>
                                <td class="text-right font-semibold">{{ $money($day['net_revenue']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state class="mt-4" title="No paid revenue" description="No paid transactions match this date range and filter selection." />
        @endif
    </section>

    <section class="mt-9" aria-labelledby="services-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="services-heading" class="spa-section-title">Service Popularity</h2>
                <p class="mt-1 text-sm text-cocoa-500">Ranked by bookings, then paid service revenue.</p>
            </div>
            @if (Route::has('management.services.index'))
                <a href="{{ route('management.services.index') }}" class="text-sm font-semibold text-sage-700 hover:text-sage-800">Manage services &rarr;</a>
            @endif
        </div>

        @if ($analytics['services']->isNotEmpty())
            <div class="spa-table-wrap mt-4">
                <table class="spa-table">
                    <thead><tr><th>Rank</th><th>Service</th><th>Bookings</th><th>Completed</th><th>Paid sales</th><th class="text-right">Revenue</th><th class="text-right">Average sale</th></tr></thead>
                    <tbody>
                        @foreach ($analytics['services'] as $service)
                            <tr>
                                <td class="font-bold text-sage-700">#{{ $loop->iteration }}</td>
                                <td class="font-semibold text-cocoa-900">{{ $service['service'] }}</td>
                                <td>{{ $service['appointment_count'] }}</td>
                                <td>{{ $service['completed_count'] }}</td>
                                <td>{{ $service['paid_transaction_count'] }}</td>
                                <td class="text-right font-semibold">{{ $money($service['revenue']) }}</td>
                                <td class="text-right">{{ $money($service['average_transaction']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-empty-state class="mt-4" title="No service activity" description="No service bookings or paid service transactions match these filters." />
        @endif
    </section>

    <section class="mt-9" aria-labelledby="booking-periods-heading">
        <div>
            <h2 id="booking-periods-heading" class="spa-section-title">Peak Booking Periods</h2>
            <p class="mt-1 text-sm text-cocoa-500">Booking volume by weekday, start time, and busiest dates.</p>
        </div>

        @if ($bookings['total'] > 0)
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="spa-panel p-5"><p class="spa-detail-label">Total bookings</p><p class="mt-2 text-2xl font-semibold">{{ $bookings['total'] }}</p></article>
                <article class="spa-panel p-5"><p class="spa-detail-label">Completed</p><p class="mt-2 text-2xl font-semibold">{{ $bookings['completed'] }}</p><p class="mt-1 text-xs text-cocoa-500">{{ number_format($bookings['completion_rate'], 1) }}% completion rate</p></article>
                <article class="spa-panel p-5"><p class="spa-detail-label">Cancelled</p><p class="mt-2 text-2xl font-semibold">{{ $bookings['cancelled'] }}</p></article>
                <article class="spa-panel p-5"><p class="spa-detail-label">No-show</p><p class="mt-2 text-2xl font-semibold">{{ $bookings['no_show'] }}</p></article>
            </div>

            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                <article class="spa-panel p-5 sm:p-6">
                    <h3 class="font-semibold text-cocoa-900">Bookings by weekday</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($bookings['days'] as $day)
                            <div>
                                <div class="flex items-center justify-between text-sm"><span>{{ $day['label'] }}</span><strong>{{ $day['count'] }}</strong></div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-sage-600" style="width: {{ ($day['count'] / $maxDay) * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </article>

                <article class="spa-panel p-5 sm:p-6">
                    <h3 class="font-semibold text-cocoa-900">Bookings by start time</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($bookings['hours'] as $hour)
                            <div>
                                <div class="flex items-center justify-between text-sm"><span>{{ $hour['label'] }}</span><strong>{{ $hour['count'] }}</strong></div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-gold-500" style="width: {{ ($hour['count'] / $maxHour) * 100 }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>

            <div class="mt-4 grid gap-5 lg:grid-cols-2">
                <article class="spa-panel p-5 sm:p-6">
                    <h3 class="font-semibold text-cocoa-900">Busiest dates</h3>
                    <div class="mt-4 divide-y divide-cream-200">
                        @foreach ($bookings['busiest_dates'] as $date)
                            <div class="flex items-center justify-between py-3 text-sm"><span>{{ \Carbon\CarbonImmutable::parse($date['date'])->format('D, M j, Y') }}</span><strong>{{ $date['count'] }} bookings</strong></div>
                        @endforeach
                    </div>
                </article>
                <article class="spa-panel p-5 sm:p-6">
                    <h3 class="font-semibold text-cocoa-900">Therapist workload</h3>
                    <div class="mt-4 divide-y divide-cream-200">
                        @forelse ($bookings['therapist_workload'] as $workload)
                            <div class="flex items-center justify-between py-3 text-sm"><span>{{ $workload['therapist'] }}</span><strong>{{ $workload['count'] }} bookings</strong></div>
                        @empty
                            <p class="py-4 text-sm text-cocoa-500">No therapists are assigned to matching bookings.</p>
                        @endforelse
                    </div>
                </article>
            </div>
        @else
            <x-empty-state class="mt-4" title="No booking patterns" description="No appointments match this date range and filter selection." />
        @endif
    </section>

    <div class="mt-9 grid gap-7 xl:grid-cols-2">
        <section aria-labelledby="rfm-heading">
            <div class="flex items-end justify-between gap-3">
                <div><h2 id="rfm-heading" class="spa-section-title">Customer Segments</h2><p class="mt-1 text-sm text-cocoa-500">Latest RFM score per customer in the period.</p></div>
                @if (Route::has('management.rfm.index'))<a href="{{ route('management.rfm.index') }}" class="text-sm font-semibold text-sage-700 hover:text-sage-800">View RFM &rarr;</a>@endif
            </div>
            @if ($analytics['rfm']['total'] > 0)
                <article class="spa-panel mt-4 p-5 sm:p-6">
                    <p class="spa-detail-label">Scored customers</p><p class="mt-1 text-2xl font-semibold">{{ $analytics['rfm']['total'] }}</p>
                    <div class="mt-5 space-y-4">
                        @foreach ($analytics['rfm']['segments'] as $segment)
                            <div>
                                <div class="flex items-center justify-between gap-3 text-sm"><span>{{ $segment['label'] }}</span><strong>{{ $segment['count'] }} ({{ number_format($segment['percentage'], 1) }}%)</strong></div>
                                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-cream-200"><div class="h-full rounded-full bg-sage-600" style="width: {{ $segment['percentage'] }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @else
                <x-empty-state class="mt-4" title="No customer segment data" description="No RFM score snapshots match this date range and filter selection." />
            @endif
        </section>

        <section aria-labelledby="promotions-heading">
            <div class="flex items-end justify-between gap-3">
                <div><h2 id="promotions-heading" class="spa-section-title">Promotion Insights</h2><p class="mt-1 text-sm text-cocoa-500">Usage, discounts, and paid promo-driven revenue.</p></div>
                @if (Route::has('management.promotions.index'))<a href="{{ route('management.promotions.index') }}" class="text-sm font-semibold text-sage-700 hover:text-sage-800">View promotions &rarr;</a>@endif
            </div>
            @if ($promotions['usage_count'] > 0)
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <article class="spa-panel p-4"><p class="spa-detail-label">Uses</p><p class="mt-2 text-xl font-semibold">{{ $promotions['usage_count'] }}</p></article>
                    <article class="spa-panel p-4"><p class="spa-detail-label">Discounts</p><p class="mt-2 text-xl font-semibold">{{ $money($promotions['discount_total']) }}</p></article>
                    <article class="spa-panel p-4"><p class="spa-detail-label">Paid revenue</p><p class="mt-2 text-xl font-semibold">{{ $money($promotions['paid_revenue']) }}</p></article>
                </div>
                <div class="spa-table-wrap mt-4">
                    <table class="spa-table">
                        <thead><tr><th>Promotion</th><th>Uses</th><th class="text-right">Discounts</th><th class="text-right">Paid revenue</th></tr></thead>
                        <tbody>
                            @foreach ($promotions['top'] as $promotion)
                                <tr><td class="font-semibold">{{ $promotion['promotion'] }}</td><td>{{ $promotion['usage_count'] }}</td><td class="text-right">{{ $money($promotion['discount_total']) }}</td><td class="text-right font-semibold">{{ $money($promotion['paid_revenue']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <x-empty-state class="mt-4" title="No promotion usage" description="No promotion redemptions match this date range and filter selection." />
            @endif
        </section>
    </div>

    @if ($reviews['available'])
        <section class="mt-9" aria-labelledby="reviews-heading">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div><h2 id="reviews-heading" class="spa-section-title">Review &amp; Sentiment Snapshot</h2><p class="mt-1 text-sm text-cocoa-500">A concise view of ratings and classified customer sentiment.</p></div>
                @if (Route::has('management.reviews.index'))<a href="{{ route('management.reviews.index') }}" class="text-sm font-semibold text-sage-700 hover:text-sage-800">View reviews &rarr;</a>@endif
            </div>
            @if ($reviews['total'] > 0)
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                    <article class="spa-panel p-5"><p class="spa-detail-label">Reviews</p><p class="mt-2 text-2xl font-semibold">{{ $reviews['total'] }}</p></article>
                    <article class="spa-panel p-5"><p class="spa-detail-label">Average rating</p><p class="mt-2 text-2xl font-semibold">{{ number_format($reviews['average_rating'], 1) }} / 5</p></article>
                    <article class="rounded-2xl border border-sage-200 bg-sage-50 p-5"><p class="spa-detail-label">Positive</p><p class="mt-2 text-2xl font-semibold text-sage-800">{{ $reviews['positive'] }}</p></article>
                    <article class="rounded-2xl border border-gold-300 bg-gold-100/60 p-5"><p class="spa-detail-label">Neutral</p><p class="mt-2 text-2xl font-semibold">{{ $reviews['neutral'] }}</p></article>
                    <article class="rounded-2xl border border-red-200 bg-red-50 p-5"><p class="spa-detail-label">Negative</p><p class="mt-2 text-2xl font-semibold text-red-800">{{ $reviews['negative'] }}</p></article>
                    <article class="rounded-2xl border border-red-200 bg-red-50 p-5"><p class="spa-detail-label">Recent negative</p><p class="mt-2 text-2xl font-semibold text-red-800">{{ $reviews['recent_negative'] }}</p><p class="mt-1 text-xs text-cocoa-500">Last 7 days in range</p></article>
                </div>
            @else
                <x-empty-state class="mt-4" title="No reviews in this period" description="The review module is available, but no customer reviews match these filters." />
            @endif
        </section>
    @endif
@endsection
