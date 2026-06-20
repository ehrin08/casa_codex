@props([
    'title',
    'description' => null,
    'eyebrow' => 'Casa Paraiso',
])

<div {{ $attributes->class(['flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="max-w-3xl">
        @if ($eyebrow)
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-sage-700">{{ $eyebrow }}</p>
        @endif
        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-cocoa-950 sm:text-3xl">{{ $title }}</h2>
        @if ($description)
            <p class="mt-2 max-w-2xl text-sm leading-6 text-cocoa-500 sm:text-base">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex shrink-0 flex-wrap gap-3">{{ $actions }}</div>
    @endif
</div>
