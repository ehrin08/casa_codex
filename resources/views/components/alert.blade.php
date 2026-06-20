@props(['type' => 'info', 'title' => null])

@php
    $classes = match ($type) {
        'success' => 'border-sage-200 bg-sage-50 text-sage-800',
        'error' => 'border-red-200 bg-red-50 text-red-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        default => 'border-cream-300 bg-cream-50 text-cocoa-800',
    };
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['rounded-2xl border px-4 py-3.5 text-sm leading-6', $classes]) }}>
    @if ($title)<p class="font-bold">{{ $title }}</p>@endif
    <div @class(['mt-1' => $title])>{{ $slot }}</div>
</div>
