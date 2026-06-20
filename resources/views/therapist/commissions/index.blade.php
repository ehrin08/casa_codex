@extends('layouts.app')

@section('title', 'My Commissions | Casa Paraiso')
@section('page_title', 'My Commissions')
@section('page_description', 'Review your commission records from completed and paid Casa Paraiso services.')

@section('content')
    <div class="mb-6"><a href="{{ route('therapist.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Therapist dashboard</a></div>

    <x-alert class="mb-6" title="How earnings are calculated">Each commission uses the service subtotal before discount and the commission rate saved when the paid transaction was recorded.</x-alert>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Date</th><th>Service / customer</th><th>Basis</th><th>Rate</th><th>Commission</th><th>Status</th><th>Paid</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($commissions as $commission)
                    @php
                        $appointment = $commission->appointment;
                        $customer = $appointment?->customerProfile;
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $commission->created_at->format('M j, Y') }}</td>
                        <td><p class="text-cocoa-700">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Service unavailable' }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable' }}</p></td>
                        <td class="whitespace-nowrap text-cocoa-600">PHP {{ number_format((float) $commission->transaction?->subtotal, 2) }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                        <td class="whitespace-nowrap font-bold text-cocoa-900">PHP {{ number_format((float) $commission->commission_amount, 2) }}</td>
                        <td><x-status-badge :status="$commission->status" /></td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $commission->paid_at?->format('M j, Y') ?? 'Not paid' }}</td>
                        <td class="text-right"><x-button :href="route('therapist.commissions.show', $commission)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state title="No commission records yet" description="Commission records appear after your completed appointments receive paid cash transactions." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $commissions->links() }}</div>
@endsection
