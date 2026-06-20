@props(['status'])

@php
    $normalized = strtolower(str_replace(' ', '_', (string) $status));
    $classes = match ($normalized) {
        'active', 'confirmed', 'completed', 'read' => 'bg-sage-100 text-sage-800 ring-sage-600/20',
        'pending', 'pending_confirmation' => 'bg-amber-100 text-amber-900 ring-amber-600/20',
        'cancelled', 'canceled', 'inactive', 'no_show' => 'bg-stone-200 text-stone-700 ring-stone-500/20',
        'in_progress' => 'bg-sky-100 text-sky-800 ring-sky-600/20',
        'unread' => 'bg-cocoa-700 text-white ring-cocoa-800/20',
        default => 'bg-cream-200 text-cocoa-700 ring-cocoa-500/20',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold capitalize ring-1 ring-inset', $classes]) }}>
    {{ str_replace('_', ' ', $status) }}
</span>
