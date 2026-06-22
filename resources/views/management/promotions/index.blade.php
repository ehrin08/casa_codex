@extends('layouts.app')

@section('title', 'Promotion Rules | Casa Paraiso')
@section('page_title', 'Promotion Rules')
@section('page_description', 'Configure RFM-driven discounts, eligibility thresholds, and active date windows.')

@section('content')
    @php
        $cards = [
            ['label' => 'Total rules', 'value' => $summary['total'], 'hint' => 'All configured promotion rules'],
            ['label' => 'Active rules', 'value' => $summary['active'], 'hint' => 'Available to the future engine'],
            ['label' => 'Draft rules', 'value' => $summary['draft'], 'hint' => 'Still being prepared'],
            ['label' => 'Inactive rules', 'value' => $summary['inactive'], 'hint' => 'Excluded from consideration'],
        ];
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.promotions.create')">Create promotion rule</x-button>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-6" title="Promotion rules updated">{{ session('success') }}</x-alert>
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

    <form method="GET" action="{{ route('management.promotions.index') }}" class="spa-panel my-7 p-5 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
            <x-form.select name="status" label="Filter by status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </x-form.select>
            <x-form.select name="segment" label="Filter by RFM segment">
                <option value="">All segments</option>
                @foreach ($segments as $segment)
                    <option value="{{ $segment }}" @selected($selectedSegment === $segment)>{{ $segment }}</option>
                @endforeach
            </x-form.select>
            <div class="flex gap-3">
                <x-button type="submit">Apply filters</x-button>
                @if ($selectedStatus || $selectedSegment)
                    <x-button :href="route('management.promotions.index')" variant="secondary">Clear</x-button>
                @endif
            </div>
        </div>
    </form>

    @if ($promotions->isEmpty() && ! $selectedStatus && ! $selectedSegment && $summary['total'] === 0)
        <x-empty-state title="No promotion rules yet" description="Create the first rule to define an RFM audience and discount settings.">
            <x-slot:action><x-button :href="route('management.promotions.create')">Create promotion rule</x-button></x-slot:action>
        </x-empty-state>
    @else
        <div class="spa-table-wrap">
            <table class="spa-table">
                <thead>
                    <tr><th>Rule</th><th>Discount</th><th>Eligibility criteria</th><th>Active window</th><th>Status</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($promotions as $promotion)
                        <tr>
                            <td>
                                <p class="font-semibold text-cocoa-950">{{ $promotion->title }}</p>
                                <p class="mt-1 max-w-xs text-xs leading-5 text-cocoa-500">{{ $promotion->description ?: 'No description' }}</p>
                            </td>
                            <td class="whitespace-nowrap font-semibold text-cocoa-700">
                                @if ($promotion->discount_type === \App\Models\Promotion::DISCOUNT_TYPE_PERCENTAGE)
                                    {{ number_format((float) $promotion->discount_value, 2) }}%
                                @else
                                    PHP {{ number_format((float) $promotion->discount_value, 2) }}
                                @endif
                            </td>
                            <td class="min-w-64 text-sm text-cocoa-600">
                                <p><span class="font-semibold text-cocoa-800">Segment:</span> {{ $promotion->rfm_segment_label ?: 'Any' }}</p>
                                <p class="mt-1"><span class="font-semibold text-cocoa-800">Minimum R/F/M:</span> {{ $promotion->rule_min_recency_score ?? 'Any' }} / {{ $promotion->rule_min_frequency_score ?? 'Any' }} / {{ $promotion->rule_min_monetary_score ?? 'Any' }}</p>
                                <p class="mt-1 text-xs text-cocoa-500">All configured criteria must match.</p>
                            </td>
                            <td class="min-w-48 text-sm text-cocoa-600">
                                <p><span class="font-semibold text-cocoa-800">From:</span> {{ $promotion->starts_at?->format('M j, Y g:i A') ?? 'No start limit' }}</p>
                                <p class="mt-1"><span class="font-semibold text-cocoa-800">Until:</span> {{ $promotion->ends_at?->format('M j, Y g:i A') ?? 'No end limit' }}</p>
                            </td>
                            <td><x-status-badge :status="$promotion->status" /></td>
                            <td>
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-button :href="route('management.promotions.edit', $promotion)" variant="secondary" class="min-h-9 px-3 py-1.5">Edit</x-button>
                                    <form method="POST" action="{{ route('management.promotions.toggle-status', $promotion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $promotion->status === \App\Models\Promotion::STATUS_ACTIVE ? 'Deactivate' : 'Activate' }}</x-button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state title="No promotion rules match" description="Adjust or clear the current filters." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $promotions->links() }}</div>
    @endif
@endsection
