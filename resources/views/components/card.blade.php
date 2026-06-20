@props(['padding' => true])

<div {{ $attributes->class(['spa-panel', 'p-5 sm:p-6' => $padding]) }}>
    {{ $slot }}
</div>
