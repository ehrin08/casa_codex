@extends('layouts.app')

@section('title', 'Services | Casa Paraiso Spa Management System')
@section('page_title', 'Services')
@section('page_description', 'Manage service categories, durations, prices, and availability to customers.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
        <a href="{{ route('management.services.create') }}" class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Add service</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Duration</th>
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($services as $service)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-zinc-950">{{ $service->name }}</p>
                            <p class="mt-1 max-w-xs truncate text-xs text-zinc-500">{{ $service->description ?: 'No description' }}</p>
                        </td>
                        <td class="px-4 py-3 text-zinc-600">{{ $service->category?->name ?? 'Uncategorized' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $service->duration_minutes }} minutes</td>
                        <td class="px-4 py-3 text-zinc-600">PHP {{ number_format((float) $service->price, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $service->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">
                                {{ ucfirst($service->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('management.services.edit', $service) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">Edit</a>
                                <form method="POST" action="{{ route('management.services.toggle-status', $service) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">
                                        {{ $service->status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">No services found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $services->links() }}</div>
@endsection
