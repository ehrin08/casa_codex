@extends('layouts.app')

@section('title', 'Customer Segments | Casa Paraiso')
@section('page_title', 'Customer RFM Segments')
@section('page_description', 'Review recency, frequency, and monetary scores prepared for future promotions and analytics.')

@section('content')
    @php
        $cards = [
            ['label' => 'Total scored customers', 'value' => $summary['total'], 'hint' => 'Active and inactive profiles'],
            ['label' => 'Champions', 'value' => $summary['champions'], 'hint' => 'Recent, frequent, high-spend guests'],
            ['label' => 'At Risk', 'value' => $summary['at_risk'], 'hint' => 'Previously engaged but no longer recent'],
            ['label' => 'New / Low Activity', 'value' => $summary['new_low_activity'], 'hint' => 'Limited or no paid history'],
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <form method="POST" action="{{ route('management.rfm.recalculate') }}">
            @csrf
            <x-button type="submit">Recalculate RFM Scores</x-button>
        </form>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-6" title="RFM recalculation complete">{{ session('success') }}</x-alert>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <x-card class="p-5">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-cocoa-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-semibold text-cocoa-950">{{ number_format($card['value']) }}</p>
                <p class="mt-1 text-xs text-cocoa-500">{{ $card['hint'] }}</p>
            </x-card>
        @endforeach
    </div>

    <form method="GET" action="{{ route('management.rfm.index') }}" class="spa-panel my-7 p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="w-full sm:max-w-sm">
                <x-form.select name="segment" label="Filter by segment">
                    <option value="">All segments</option>
                    @foreach ($segments as $segment)
                        <option value="{{ $segment }}" @selected($selectedSegment === $segment)>{{ $segment }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <div class="flex gap-3">
                <x-button type="submit">Apply filter</x-button>
                @if ($selectedSegment)
                    <x-button :href="route('management.rfm.index')" variant="secondary">Clear</x-button>
                @endif
            </div>
        </div>
    </form>

    @if ($scores->isEmpty() && ! $selectedSegment && $summary['total'] === 0)
        <x-empty-state title="No RFM scores yet" description="Recalculate scores to evaluate every customer profile from paid transaction history.">
            <x-slot:action>
                <form method="POST" action="{{ route('management.rfm.recalculate') }}">
                    @csrf
                    <x-button type="submit">Recalculate RFM Scores</x-button>
                </form>
            </x-slot:action>
        </x-empty-state>
    @else
        <div class="spa-table-wrap">
            <table class="spa-table">
                <thead>
                    <tr><th>Customer</th><th>R</th><th>F</th><th>M</th><th>Score</th><th>Segment</th><th>Calculated</th><th>Source values</th></tr>
                </thead>
                <tbody>
                    @forelse ($scores as $score)
                        @php($customer = $score->customerProfile)
                        <tr>
                            <td>
                                <p class="font-semibold text-cocoa-950">{{ trim($customer->first_name.' '.$customer->last_name) }}</p>
                                <p class="mt-1 text-xs text-cocoa-500">{{ $customer->email ?: $customer->user?->email ?: 'No email' }}</p>
                            </td>
                            <td class="font-semibold text-cocoa-950">{{ $score->recency_score }}</td>
                            <td class="font-semibold text-cocoa-950">{{ $score->frequency_score }}</td>
                            <td class="font-semibold text-cocoa-950">{{ $score->monetary_score }}</td>
                            <td><span class="rounded-lg bg-cream-100 px-2.5 py-1 font-mono font-bold text-cocoa-800">{{ $score->recency_score }}{{ $score->frequency_score }}{{ $score->monetary_score }}</span></td>
                            <td><span class="whitespace-nowrap rounded-full bg-sage-100 px-3 py-1 text-xs font-bold text-sage-700">{{ $score->segment_label }}</span></td>
                            <td class="whitespace-nowrap text-cocoa-600">{{ $score->calculated_at->format('M j, Y') }}</td>
                            <td class="min-w-80 text-xs leading-5 text-cocoa-600">{{ $score->source_notes }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state title="No customers in this segment" description="Choose another segment or clear the current filter." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $scores->links() }}</div>
    @endif
@endsection
