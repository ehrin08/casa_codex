@props(['title' => null])

<section {{ $attributes }}>
    @if ($title)
        <h2 class="mb-2 px-3 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-cream-300/65">{{ $title }}</h2>
    @endif
    <div class="grid gap-1">
        {{ $slot }}
    </div>
</section>
