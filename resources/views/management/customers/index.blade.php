@extends('layouts.app')

@section('title', 'Customers | Casa Paraiso')
@section('page_title', 'Customer Profiles')
@section('page_description', 'Care for registered and walk-in guest details in one organized directory.')

@section('content')
    @php
        $emptyCustomer = new \App\Models\CustomerProfile;
        $createHasErrors = $errors->any() && old('_modal') === 'customer-create';
        $editHasErrors = $errors->any() && old('_modal') === 'customer-edit';
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.customers.create')" data-modal-open="customer-create-modal">Add customer</x-button>
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
                        <td>
                            <div class="flex flex-wrap justify-end gap-2">
                                <x-button type="button" variant="ghost" class="min-h-9 px-3 py-1.5" data-modal-open="customer-detail-modal" data-modal-template="customer-detail-{{ $customer->id }}">View</x-button>
                                <x-button
                                    :href="route('management.customers.edit', $customer)"
                                    variant="secondary"
                                    class="min-h-9 px-3 py-1.5"
                                    data-modal-open="customer-edit-modal"
                                    data-modal-action="{{ route('management.customers.update', $customer) }}"
                                    data-modal-record="{{ json_encode([
                                        '__record_id' => $customer->id,
                                        'user_id' => $customer->user_id,
                                        'first_name' => $customer->first_name,
                                        'last_name' => $customer->last_name,
                                        'email' => $customer->email,
                                        'phone' => $customer->phone,
                                        'birth_date' => $customer->birth_date?->format('Y-m-d'),
                                        'gender' => $customer->gender,
                                        'address' => $customer->address,
                                        'notes' => $customer->notes,
                                        'is_active' => (int) $customer->is_active,
                                    ]) }}"
                                >Edit</x-button>
                                <x-button
                                    type="button"
                                    variant="subtle"
                                    class="min-h-9 px-3 py-1.5"
                                    data-confirm-modal="customer-status-modal"
                                    data-confirm-action="{{ route('management.customers.toggle-status', $customer) }}"
                                    data-confirm-heading="{{ $customer->is_active ? 'Deactivate customer?' : 'Reactivate customer?' }}"
                                    data-confirm-message="{{ $customer->is_active ? trim($customer->first_name.' '.$customer->last_name).' will no longer have an active customer profile.' : trim($customer->first_name.' '.$customer->last_name).' will regain an active customer profile.' }}"
                                    data-confirm-label="{{ $customer->is_active ? 'Deactivate customer' : 'Reactivate customer' }}"
                                >{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }}</x-button>
                                <noscript><form method="POST" action="{{ route('management.customers.toggle-status', $customer) }}">@csrf @method('PATCH')<x-button type="submit" variant="subtle" class="min-h-9 px-3 py-1.5">{{ $customer->is_active ? 'Deactivate' : 'Reactivate' }}</x-button></form></noscript>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state title="No customer profiles found" description="Create a profile for a registered guest or a walk-in customer." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $customers->links() }}</div>

    @foreach ($customers as $customer)
        <template id="customer-detail-{{ $customer->id }}">
            <dl class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div class="sm:col-span-2"><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value text-lg">{{ trim($customer->first_name.' '.$customer->last_name) }}</dd></div>
                <div><dt class="spa-detail-label">Profile type</dt><dd class="spa-detail-value">{{ $customer->user ? 'Registered account' : 'Walk-in record' }}</dd></div>
                <div><dt class="spa-detail-label">Status</dt><dd class="mt-2"><x-status-badge :status="$customer->is_active ? 'active' : 'inactive'" /></dd></div>
                <div><dt class="spa-detail-label">Email</dt><dd class="spa-detail-value break-words">{{ $customer->email ?: 'Not provided' }}</dd></div>
                <div><dt class="spa-detail-label">Phone</dt><dd class="spa-detail-value">{{ $customer->phone ?: 'Not provided' }}</dd></div>
                <div><dt class="spa-detail-label">Birth date</dt><dd class="spa-detail-value">{{ $customer->birth_date?->format('M j, Y') ?? 'Not provided' }}</dd></div>
                <div><dt class="spa-detail-label">Gender</dt><dd class="spa-detail-value">{{ $customer->gender ? str_replace('_', ' ', ucfirst($customer->gender)) : 'Not specified' }}</dd></div>
                <div class="sm:col-span-2"><dt class="spa-detail-label">Address</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $customer->address ?: 'No address provided.' }}</dd></div>
                <div class="sm:col-span-2"><dt class="spa-detail-label">Notes</dt><dd class="mt-2 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $customer->notes ?: 'No notes provided.' }}</dd></div>
            </dl>
        </template>
    @endforeach

    <x-modal id="customer-create-modal" title="Add customer profile" description="Create a registered or walk-in guest record." size="xl" :open-on-load="$createHasErrors">
        @include('management.customers._form', ['customer' => $emptyCustomer, 'isModal' => true, 'useOld' => $createHasErrors, 'formIdPrefix' => 'customer-create', 'modalKey' => 'customer-create'])
    </x-modal>

    <x-modal id="customer-edit-modal" title="Edit customer profile" description="Update guest and account-link details." size="xl" :open-on-load="$editHasErrors">
        @include('management.customers._form', ['customer' => $emptyCustomer, 'isModal' => true, 'useOld' => $editHasErrors, 'formIdPrefix' => 'customer-edit', 'modalKey' => 'customer-edit', 'formMethod' => 'PUT', 'formAction' => old('_record_action', route('management.customers.index')), 'recordAction' => old('_record_action', ''), 'submitLabel' => 'Save changes'])
    </x-modal>

    <x-modal id="customer-detail-modal" title="Customer details" description="Review the complete guest record." size="md"><div data-modal-detail-content></div></x-modal>
    <x-confirmation-modal id="customer-status-modal" />
@endsection
