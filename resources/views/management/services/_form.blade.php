@php
    $isModal = $isModal ?? false;
    $useOld = $useOld ?? true;
    $formIdPrefix = $formIdPrefix ?? 'service-form';
    $formMethod = $formMethod ?? ($service->exists ? 'PUT' : 'POST');
    $formAction = $formAction ?? ($service->exists ? route('management.services.update', $service) : route('management.services.store'));
    $submitLabel = $submitLabel ?? ($service->exists ? 'Save changes' : 'Create service');
    $modalKey = $modalKey ?? null;
    $recordAction = $recordAction ?? $formAction;
    $fieldValue = fn (string $name, mixed $value = null) => $useOld ? old($name, $value) : $value;
@endphp

<form method="POST" action="{{ $formAction }}" @if ($isModal) data-record-form @endif class="{{ $isModal ? 'space-y-7 p-5 sm:p-6' : 'spa-panel mx-auto max-w-3xl space-y-7 p-6 sm:p-8' }}">
    @csrf
    @if (strtoupper($formMethod) !== 'POST') @method($formMethod) @endif
    @if ($modalKey)<input type="hidden" name="_modal" value="{{ $modalKey }}">@endif
    @if ($isModal)<input type="hidden" name="_record_action" value="{{ $recordAction }}">@endif

    <div>
        <h3 class="spa-section-title">Service information</h3>
        <p class="mt-1 text-sm text-cocoa-500">Keep the name concise and the description helpful for guests choosing a treatment.</p>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form.input name="name" label="Service name" :id="$formIdPrefix.'-name'" :value="$service->name" :use-old="$useOld" required wrapper-class="sm:col-span-2" />
        <x-form.select name="service_category_id" label="Category" :id="$formIdPrefix.'-category'" :use-old="$useOld">
            <option value="">Uncategorized</option>
            @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) $fieldValue('service_category_id', $service->service_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>@endforeach
        </x-form.select>
        <x-form.select name="status" label="Status" :id="$formIdPrefix.'-status'" :use-old="$useOld" required>
            <option value="active" @selected($fieldValue('status', $service->status ?: 'active') === 'active')>Active</option>
            <option value="inactive" @selected($fieldValue('status', $service->status) === 'inactive')>Inactive</option>
        </x-form.select>
        <x-form.input name="duration_minutes" label="Duration (minutes)" :id="$formIdPrefix.'-duration'" type="number" :value="$service->duration_minutes" :use-old="$useOld" min="1" max="1440" required />
        <x-form.input name="price" label="Price (PHP)" :id="$formIdPrefix.'-price'" type="number" :value="$service->price" :use-old="$useOld" min="0" step="0.01" required />
        <x-form.textarea name="description" label="Description" :id="$formIdPrefix.'-description'" :value="$service->description" :use-old="$useOld" rows="4" wrapper-class="sm:col-span-2" />
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
        @if ($isModal)
            <x-button type="button" variant="secondary" data-modal-close>Cancel</x-button>
        @else
            <x-button :href="route('management.services.index')" variant="secondary">Cancel</x-button>
        @endif
        <x-button type="submit">{{ $submitLabel }}</x-button>
    </div>
</form>
