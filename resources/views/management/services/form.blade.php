@extends('layouts.app')

@section('title', ($service->exists ? 'Edit Service' : 'Add Service').' | Casa Paraiso Spa Management System')
@section('page_title', $service->exists ? 'Edit Service' : 'Add Service')
@section('page_description', 'Set the service details used by Casa Paraiso management and future booking workflows.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('management.services.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to services</a>
    </div>

    <form method="POST" action="{{ $service->exists ? route('management.services.update', $service) : route('management.services.store') }}" class="mx-auto max-w-3xl space-y-6 rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
        @csrf
        @if ($service->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="block text-sm font-medium text-zinc-700">Service name</label>
                <input id="name" name="name" value="{{ old('name', $service->name) }}" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="service_category_id" class="block text-sm font-medium text-zinc-700">Category</label>
                <select id="service_category_id" name="service_category_id" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="">Uncategorized</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('service_category_id', $service->service_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                    @endforeach
                </select>
                @error('service_category_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-700">Status</label>
                <select id="status" name="status" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                    <option value="active" @selected(old('status', $service->status ?: 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $service->status) === 'inactive')>Inactive</option>
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="duration_minutes" class="block text-sm font-medium text-zinc-700">Duration (minutes)</label>
                <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="1440" value="{{ old('duration_minutes', $service->duration_minutes) }}" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                @error('duration_minutes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="price" class="block text-sm font-medium text-zinc-700">Price (PHP)</label>
                <input id="price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $service->price) }}" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="description" class="block text-sm font-medium text-zinc-700">Description</label>
                <textarea id="description" name="description" rows="4" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">{{ old('description', $service->description) }}</textarea>
                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-zinc-200 pt-5">
            <a href="{{ route('management.services.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">Cancel</a>
            <button type="submit" class="rounded-md bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">{{ $service->exists ? 'Save changes' : 'Create service' }}</button>
        </div>
    </form>
@endsection
