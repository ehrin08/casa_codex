@props([
    'href' => null,
    'active' => false,
    'icon' => 'dashboard',
    'badge' => null,
    'disabled' => false,
])

@php
    $classes = [
        'group flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition',
        'bg-cream-50 text-cocoa-950 shadow-[0_10px_28px_-20px_rgba(33,23,19,0.9)]' => $active && ! $disabled,
        'text-cream-200 hover:bg-white/10 hover:text-white' => ! $active && ! $disabled,
        'cursor-not-allowed text-cream-300/45' => $disabled,
    ];
@endphp

@if ($disabled)
    <span {{ $attributes->class($classes) }} aria-disabled="true">
@else
    <a href="{{ $href }}" {{ $attributes->class($classes) }} @if ($active) aria-current="page" @endif>
@endif
    <span @class([
        'flex size-8 shrink-0 items-center justify-center rounded-lg transition',
        'bg-sage-100 text-sage-800' => $active && ! $disabled,
        'bg-white/8 text-cream-300 group-hover:bg-white/15 group-hover:text-cream-50' => ! $active && ! $disabled,
        'bg-white/5' => $disabled,
    ]) aria-hidden="true">
        @switch($icon)
            @case('services')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v15H7.5A2.5 2.5 0 0 0 5 20.5m0-15v15M8 7h7M8 10h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('people')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20m6-9a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm6.5-6.6a3.5 3.5 0 0 1 0 6.2M20 20v-1.5a3.5 3.5 0 0 0-2.6-3.38" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('availability')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M7 3v3m10-3v3M4.5 9h15M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Zm4 9 1.5 1.5L15 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('appointments')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M8 3v3m8-3v3M5 9h14M6 5h12a2 2 0 0 1 2 2v12H4V7a2 2 0 0 1 2-2Zm2 8h3m2 0h3m-8 3h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('transactions')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Zm3 5h6m-6 4h6m-6 4h3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('commissions')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M14.5 9.2c-.5-.6-1.3-1-2.4-1-1.4 0-2.4.7-2.4 1.8 0 2.8 5 1.2 5 4 0 1.1-1 1.9-2.5 1.9-1.2 0-2.2-.4-2.8-1.2M12 6.5v1.7m0 7.7v1.6" stroke-linecap="round"/></svg>
                @break
            @case('notifications')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 8.5h18C21 16 18 16 18 9ZM9.5 20h5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @break
            @case('booking')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M12 4v16M4 12h16" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/></svg>
                @break
            @case('future')
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M4 19V9m5 10V5m5 14v-7m5 7V3" stroke-linecap="round"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="m4 10 8-7 8 7v10h-6v-6h-4v6H4V10Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @endswitch
    </span>
    <span class="min-w-0 flex-1 truncate">{{ $slot }}</span>
    @if ($badge)
        <span class="min-w-6 rounded-full bg-gold-300 px-1.5 py-0.5 text-center text-[0.65rem] font-black text-cocoa-950">{{ $badge }}</span>
    @elseif ($disabled)
        <span class="shrink-0 rounded-full border border-cream-200/15 px-1.5 py-0.5 text-[0.58rem] font-bold uppercase tracking-wide">Soon</span>
    @endif
@if ($disabled)
    </span>
@else
    </a>
@endif
