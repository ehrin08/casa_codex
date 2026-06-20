@extends('layouts.app')

@section('title', 'Services | Casa Paraiso')
@section('page_title', 'Spa Services')
@section('page_description', 'Curate treatment categories, durations, pricing, and guest availability.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.services.create')">Add service</x-button>
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
                            <div class="flex justify-end gap-2">
                                <x-button :href="route('management.services.edit', $service)" variant="secondary" class="min-h-9 px-3 py-1.5">Edit</x-button>
                                <form method="POST" action="{{ route('management.services.toggle-status', $service) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $service->status === 'active' ? 'Deactivate' : 'Reactivate' }}</x-button></form>
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
@endsection
