@extends('layouts.app')

@section('title', 'Customers | Casa Paraiso')
@section('page_title', 'Customer Profiles')
@section('page_description', 'Care for registered and walk-in guest details in one organized directory.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.customers.create')">Add customer</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Customer</th><th>Contact</th><th>Profile type</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td><p class="font-semibold text-cocoa-950">{{ trim($customer->first_name.' '.$customer->last_name) }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $customer->gender ? str_replace('_', ' ', ucfirst($customer->gender)) : 'Gender not specified' }} @if ($customer->birth_date)&middot; Born {{ $customer->birth_date->format('M j, Y') }}@endif</p></td>
                        <td class="text-cocoa-600"><p>{{ $customer->email ?: 'No email' }}</p><p class="mt-1 text-xs">{{ $customer->phone ?: 'No phone' }}</p></td>
                        <td class="text-cocoa-600">{{ $customer->user ? 'Registered account' : 'Walk-in record' }}</td>
                        <td><x-status-badge :status="$customer->is_active ? 'active' : 'inactive'" /></td>
                        <td><div class="flex justify-end gap-2"><x-button :href="route('management.customers.edit', $customer)" variant="secondary" class="min-h-9 px-3 py-1.5">Edit</x-button><form method="POST" action="{{ route('management.customers.toggle-status', $customer) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }}</x-button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No customer profiles found" description="Create a profile for a registered guest or a walk-in customer." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $customers->links() }}</div>
@endsection
