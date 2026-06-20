@props([
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->class(['rounded-2xl border border-dashed border-cream-300 bg-cream-50/70 px-6 py-10 text-center']) }}>
    <div class="mx-auto flex size-10 items-center justify-center rounded-full bg-sage-100 text-sage-700" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path d="M19 5c-6.7.1-11 3.5-11 9.5M5 19c1.5-5.5 5.8-8.8 13-10" stroke-linecap="round"/><path d="M19 5c.2 6.2-2.6 10-7.5 10-2 0-3.5-.8-3.5-.8S7.2 12.7 7.5 11C8.3 6.5 13 4.8 19 5Z" stroke-linejoin="round"/></svg>
    </div>
    <p class="mt-3 font-semibold text-cocoa-900">{{ $title }}</p>
    @if ($description)<p class="mx-auto mt-1 max-w-md text-sm leading-6 text-cocoa-500">{{ $description }}</p>@endif
    @if (isset($action))<div class="mt-5">{{ $action }}</div>@endif
</div>
