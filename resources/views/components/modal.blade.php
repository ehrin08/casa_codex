@props([
    'id',
    'title',
    'description' => null,
    'size' => 'lg',
    'openOnLoad' => false,
])

@php
    $maxWidth = match ($size) {
        'sm' => 'max-w-lg',
        'md' => 'max-w-2xl',
        'xl' => 'max-w-5xl',
        default => 'max-w-4xl',
    };
@endphp

<dialog
    id="{{ $id }}"
    data-modal
    @if ($openOnLoad) data-modal-open-on-load @endif
    aria-labelledby="{{ $id }}-title"
    @if ($description) aria-describedby="{{ $id }}-description" @endif
    {{ $attributes->class(['spa-modal', $maxWidth]) }}
>
    <div class="spa-modal-shell">
        <header class="flex items-start justify-between gap-5 border-b border-cream-200 bg-cream-50 px-5 py-4 sm:px-6 sm:py-5">
            <div>
                <h2 id="{{ $id }}-title" class="text-xl font-semibold tracking-tight text-cocoa-950">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-description" class="mt-1 text-sm leading-6 text-cocoa-500">{{ $description }}</p>
                @endif
            </div>
            <button type="button" data-modal-close class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl text-cocoa-500 transition hover:bg-cream-200 hover:text-cocoa-950" aria-label="Close {{ strtolower($title) }}">
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" /></svg>
            </button>
        </header>
        <div class="spa-modal-body">
            {{ $slot }}
        </div>
    </div>
</dialog>
