@extends('layouts.app')

@section('title', ($service->exists ? 'Edit Service' : 'Add Service').' | Casa Paraiso')
@section('page_title', $service->exists ? 'Edit Service' : 'Add Service')
@section('page_description', 'Set the treatment details guests and the Casa Paraiso team will see.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.services.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to services</a></div>

    <form method="POST" action="{{ $service->exists ? route('management.services.update', $service) : route('management.services.store') }}" class="spa-panel mx-auto max-w-3xl space-y-7 p-6 sm:p-8">
        @csrf
        @if ($service->exists) @method('PUT') @endif

        <div>
            <h2 class="spa-section-title">Service information</h2>
            <p class="mt-1 text-sm text-cocoa-500">Keep the name concise and the description helpful for guests choosing a treatment.</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="name" label="Service name" :value="$service->name" required wrapper-class="sm:col-span-2" />
            <x-form.select name="service_category_id" label="Category">
                <option value="">Uncategorized</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('service_category_id', $service->service_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>@endforeach
            </x-form.select>
            <x-form.select name="status" label="Status" required>
                <option value="active" @selected(old('status', $service->status ?: 'active') === 'active')>Active</option>
                <option value="inactive" @selected(old('status', $service->status) === 'inactive')>Inactive</option>
            </x-form.select>
            <x-form.input name="duration_minutes" label="Duration (minutes)" type="number" :value="$service->duration_minutes" min="1" max="1440" required />
            <x-form.input name="price" label="Price (PHP)" type="number" :value="$service->price" min="0" step="0.01" required />
            <x-form.textarea name="description" label="Description" :value="$service->description" rows="4" wrapper-class="sm:col-span-2" />
        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:justify-end">
            <x-button :href="route('management.services.index')" variant="secondary">Cancel</x-button>
            <x-button type="submit">{{ $service->exists ? 'Save changes' : 'Create service' }}</x-button>
        </div>
    </form>
@endsection
