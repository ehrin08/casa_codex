@props([
    'name',
    'label',
    'required' => false,
    'hint' => null,
    'wrapperClass' => null,
    'useOld' => true,
])

@php
    $fieldId = $attributes->get('id', $name);
    $hasError = $useOld && $errors->has($name);
    $describedBy = collect([
        $hint ? $fieldId.'-hint' : null,
        $hasError ? $fieldId.'-error' : null,
    ])->filter()->implode(' ');
@endphp

<div @class([$wrapperClass])>
    <label for="{{ $fieldId }}" class="block text-sm font-semibold text-cocoa-700">{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
    <select name="{{ $name }}" @required($required) aria-invalid="{{ $hasError ? 'true' : 'false' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->except('id')->merge(['id' => $fieldId])->class(['mt-2 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-cocoa-950 shadow-sm transition', 'border-red-300' => $hasError, 'border-cream-300 hover:border-cocoa-500/50' => ! $hasError]) }}>{{ $slot }}</select>
    @if ($hint)<p id="{{ $fieldId }}-hint" class="mt-1.5 text-xs leading-5 text-cocoa-500">{{ $hint }}</p>@endif
    @if ($hasError)<p id="{{ $fieldId }}-error" class="mt-1.5 text-sm font-medium text-red-700">{{ $errors->first($name) }}</p>@endif
</div>
