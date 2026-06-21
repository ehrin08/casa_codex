@extends('layouts.app')

@section('title', 'Services | Casa Paraiso')
@section('page_title', 'Spa Services')
@section('page_description', 'Curate treatment categories, durations, pricing, and guest availability.')

@section('content')
    @php
        $emptyService = new \App\Models\Service;
        $createHasErrors = $errors->any() && old('_modal') === 'service-create';
        $editHasErrors = $errors->any() && old('_modal') === 'service-edit';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.services.create')" data-modal-open="service-create-modal">Add service</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Service</th><th>Category</th><th>Duration</th><th>Price</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($services as $service)
                    <tr>
                        <td><p class="font-semibold text-cocoa-950">{{ $service->name }}</p><p class="mt-1 max-w-xs truncate text-xs text-cocoa-500">{{ $service->description ?: 'No description' }}</p></td>
                        <td class="text-cocoa-600">{{ $service->category?->name ?? 'Uncategorized' }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $service->duration_minutes }} minutes</td>
                        <td class="whitespace-nowrap font-medium text-cocoa-700">PHP {{ number_format((float) $service->price, 2) }}</td>
                        <td><x-status-badge :status="$service->status" /></td>
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-button type="button" variant="ghost" class="min-h-9 px-3 py-1.5" data-modal-open="service-detail-modal" data-modal-template="service-detail-{{ $service->id }}">View</x-button>
                                <x-button
                                    :href="route('management.services.edit', $service)"
                                    variant="secondary"
                                    class="min-h-9 px-3 py-1.5"
                                    data-modal-open="service-edit-modal"
                                    data-modal-action="{{ route('management.services.update', $service) }}"
                                    data-modal-record="{{ json_encode([
                                        '__record_id' => $service->id,
                                        'service_category_id' => $service->service_category_id,
                                        'name' => $service->name,
                                        'description' => $service->description,
                                        'duration_minutes' => $service->duration_minutes,
                                        'price' => $service->price,
                                        'status' => $service->status,
                                    ]) }}"
                                >Edit</x-button>
                                <x-button
                                    type="button"
                                    variant="subtle"
                                    class="min-h-9 px-3 py-1.5"
                                    data-confirm-modal="service-status-modal"
                                    data-confirm-action="{{ route('management.services.toggle-status', $service) }}"
                                    data-confirm-heading="{{ $service->status === 'active' ? 'Deactivate service?' : 'Reactivate service?' }}"
                                    data-confirm-message="{{ $service->status === 'active' ? 'Guests will no longer be able to select '.$service->name.' for new bookings.' : $service->name.' will become available for management workflows again.' }}"
                                    data-confirm-label="{{ $service->status === 'active' ? 'Deactivate service' : 'Reactivate service' }}"
                                >{{ $service->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button>
                                <noscript><form method="POST" action="{{ route('management.services.toggle-status', $service) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $service->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form></noscript>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state title="No services found" description="Add the first treatment to begin building the Casa Paraiso service menu." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $services->links() }}</div>

    @foreach ($services as $service)
        <template id="service-detail-{{ $service->id }}">
            <dl class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2"><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value text-lg">{{ $service->name }}</dd></div>
                <div><dt class="spa-detail-label">Category</dt><dd class="spa-detail-value">{{ $service->category?->name ?? 'Uncategorized' }}</dd></div>
                <div><dt class="spa-detail-label">Status</dt><dd class="mt-2"><x-status-badge :status="$service->status" /></dd></div>
                <div><dt class="spa-detail-label">Duration</dt><dd class="spa-detail-value">{{ $service->duration_minutes }} minutes</dd></div>
                <div><dt class="spa-detail-label">Price</dt><dd class="spa-detail-value">PHP {{ number_format((float) $service->price, 2) }}</dd></div>
                <div class="sm:col-span-2"><dt class="spa-detail-label">Description</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $service->description ?: 'No description provided.' }}</dd></div>
            </dl>
        </template>
    @endforeach

    <x-modal id="service-create-modal" title="Add service" description="Create a treatment without leaving the service directory." :open-on-load="$createHasErrors">
        @include('management.services._form', ['service' => $emptyService, 'isModal' => true, 'useOld' => $createHasErrors, 'formIdPrefix' => 'service-create', 'modalKey' => 'service-create'])
    </x-modal>

    <x-modal id="service-edit-modal" title="Edit service" description="Update treatment details while keeping the directory in view." :open-on-load="$editHasErrors">
        @include('management.services._form', ['service' => $emptyService, 'isModal' => true, 'useOld' => $editHasErrors, 'formIdPrefix' => 'service-edit', 'modalKey' => 'service-edit', 'formMethod' => 'PUT', 'formAction' => old('_record_action', route('management.services.index')), 'recordAction' => old('_record_action', ''), 'submitLabel' => 'Save changes'])
    </x-modal>

    <x-modal id="service-detail-modal" title="Service details" description="Review the complete treatment record." size="md">
        <div data-modal-detail-content></div>
    </x-modal>

    <x-confirmation-modal id="service-status-modal" />
@endsection
