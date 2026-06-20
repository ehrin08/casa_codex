@extends('layouts.app')

@section('title', 'Therapist Commissions | Casa Paraiso')
@section('page_title', 'Therapist Commissions')
@section('page_description', 'Review automatically calculated therapist earnings from completed paid services.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a></div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Created</th><th>Therapist</th><th>Service / customer</th><th>Basis</th><th>Rate</th><th>Commission</th><th>Status</th><th>Paid</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($commissions as $commission)
                    @php
                        $appointment = $commission->appointment;
                        $customer = $appointment?->customerProfile;
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap"><p class="font-semibold text-cocoa-950">{{ $commission->created_at->format('M j, Y') }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $commission->created_at->format('g:i A') }}</p></td>
                        <td class="font-semibold text-cocoa-950">{{ $commission->therapistProfile ? trim($commission->therapistProfile->first_name.' '.$commission->therapistProfile->last_name) : 'Unavailable' }}</td>
                        <td><p class="text-cocoa-700">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Service unavailable' }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable' }}</p></td>
                        <td class="whitespace-nowrap text-cocoa-600">PHP {{ number_format((float) $commission->transaction?->subtotal, 2) }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                        <td class="whitespace-nowrap font-bold text-cocoa-900">PHP {{ number_format((float) $commission->commission_amount, 2) }}</td>
                        <td><x-status-badge :status="$commission->status" /></td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $commission->paid_at?->format('M j, Y') ?? 'Not paid' }}</td>
                        <td class="text-right"><x-button :href="route('management.commissions.show', $commission)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty-state title="No commissions recorded" description="A pending commission is created automatically when a completed appointment receives a paid cash transaction."><x-slot:action><x-button :href="route('management.transactions.index')" variant="secondary">View transactions</x-button></x-slot:action></x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $commissions->links() }}</div>
@endsection
