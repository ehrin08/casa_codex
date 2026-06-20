@props([
    'name',
    'label',
    'value' => null,
    'required' => false,
    'hint' => null,
    'wrapperClass' => null,
])

<div @class([$wrapperClass])>
    <label for="{{ $attributes->get('id', $name) }}" class="block text-sm font-semibold text-cocoa-700">{{ $label }} @if ($required)<span class="text-red-700" aria-hidden="true">*</span>@endif</label>
    <textarea name="{{ $name }}" @required($required) {{ $attributes->except('id')->merge(['id' => $attributes->get('id', $name)])->class(['mt-2 block w-full rounded-xl border bg-white px-3.5 py-2.5 text-cocoa-950 shadow-sm transition placeholder:text-cocoa-500/60', 'border-red-300' => $errors->has($name), 'border-cream-300 hover:border-cocoa-500/50' => ! $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<p class="mt-1.5 text-xs leading-5 text-cocoa-500">{{ $hint }}</p>@endif
    @error($name)<p class="mt-1.5 text-sm font-medium text-red-700">{{ $message }}</p>@enderror
</div>
