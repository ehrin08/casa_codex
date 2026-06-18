@extends('layouts.app')

@section('title', 'Customers | Casa Paraiso Spa Management System')
@section('page_title', 'Customer Profiles')
@section('page_description', 'Manage registered and walk-in customer contact details and active status.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to management</a>
        <a href="{{ route('management.customers.create') }}" class="rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Add customer</a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Profile type</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($customers as $customer)
                    <tr>
                        <td class="px-4 py-3"><p class="font-medium text-zinc-950">{{ trim($customer->first_name.' '.$customer->last_name) }}</p><p class="mt-1 text-xs text-zinc-500">{{ $customer->gender ? str_replace('_', ' ', ucfirst($customer->gender)) : 'Gender not specified' }}{{ $customer->birth_date ? ' · Born '.$customer->birth_date->format('M j, Y') : '' }}</p></td>
                        <td class="px-4 py-3 text-zinc-600"><p>{{ $customer->email ?: 'No email' }}</p><p class="mt-1 text-xs">{{ $customer->phone ?: 'No phone' }}</p></td>
                        <td class="px-4 py-3 text-zinc-600">{{ $customer->user ? 'Registered account' : 'Walk-in record' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-200 text-zinc-700' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="px-4 py-3"><div class="flex justify-end gap-2"><a href="{{ route('management.customers.edit', $customer) }}" class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">Edit</a><form method="POST" action="{{ route('management.customers.toggle-status', $customer) }}">@csrf @method('PATCH')<button class="rounded-md border border-zinc-300 px-3 py-1.5 font-medium text-zinc-700 hover:bg-zinc-50">{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }}</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500">No customer profiles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
