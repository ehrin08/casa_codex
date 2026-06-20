@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = match ($variant) {
        'secondary' => 'border border-cream-300 bg-white text-cocoa-700 hover:border-cocoa-500 hover:bg-cream-50',
        'subtle' => 'bg-cream-100 text-cocoa-700 hover:bg-cream-200',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
        'ghost' => 'text-cocoa-600 hover:bg-cream-100 hover:text-cocoa-900',
        'light' => 'bg-cream-50 text-cocoa-900 shadow-sm hover:bg-cream-200',
        'outline-light' => 'border border-cream-50/30 bg-transparent text-cream-50 hover:bg-white/10',
        default => 'bg-cocoa-700 text-white shadow-sm hover:bg-cocoa-800',
    };
    $base = 'inline-flex min-h-10 items-center justify-center whitespace-nowrap rounded-xl px-4 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$base, $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$base, $classes]) }}>{{ $slot }}</button>
@endif
