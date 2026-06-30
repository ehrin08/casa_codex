@extends('layouts.app')

@section('title', 'Cash Transactions | Casa Paraiso')
@section('page_title', 'Cash Transactions')
@section('page_description', 'Review over-the-counter payments recorded for completed Casa Paraiso appointments.')

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a>
        <x-button :href="route('management.transactions.create')">Record cash transaction</x-button>
    </div>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead>
                <tr><th>Date</th><th>Appointment / customer</th><th>Therapist</th><th>Service</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Status</th><th class="text-right">Action</th></tr>
            </thead>
            <tbody>
                @forelse ($transactions as $transaction)
                    @php
                        $appointment = $transaction->appointment;
                        $therapist = $appointment?->therapistProfile;
                    @endphp
                    <tr>
                        <td class="whitespace-nowrap"><p class="font-semibold text-cocoa-950">{{ $transaction->transaction_date->format('M j, Y') }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $transaction->transaction_date->format('g:i A') }}</p></td>
                        <td><p class="font-semibold text-cocoa-950">{{ $appointment ? '#'.$appointment->id : 'Appointment unavailable' }}</p><p class="mt-1 whitespace-nowrap text-xs text-cocoa-500">{{ $appointment?->customer_display_name ?? 'Customer unavailable' }}</p></td>
                        <td class="text-cocoa-600">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Unavailable' }}</td>
                        <td class="text-cocoa-600">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Unavailable' }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">PHP {{ number_format((float) $transaction->subtotal, 2) }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">PHP {{ number_format((float) $transaction->discount_amount, 2) }}</td>
                        <td class="whitespace-nowrap font-bold text-cocoa-900">PHP {{ number_format((float) $transaction->total_amount, 2) }}</td>
                        <td><x-status-badge :status="$transaction->payment_status" /></td>
                        <td class="text-right"><x-button :href="route('management.transactions.show', $transaction)" variant="secondary" class="min-h-9 px-3 py-1.5">View receipt</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="9"><x-empty-state title="No cash transactions recorded" description="Completed appointments that have not been paid can be selected from the transaction recording page."><x-slot:action><x-button :href="route('management.transactions.create')">Choose an appointment</x-button></x-slot:action></x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $transactions->links() }}</div>
@endsection
