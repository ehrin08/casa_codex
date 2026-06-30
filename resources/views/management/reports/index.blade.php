@extends('layouts.app')

@section('title', 'Financial Reports | Casa Paraiso')
@section('page_title', 'Sales and Commission Reports')
@section('page_description', 'Review sales performance, service revenue, and therapist commission obligations.')

@section('content')
    @php
        $salesSummary = $report['sales_summary'];
        $commissionSummary = $report['commission_summary'];
        $money = fn ($amount) => 'PHP '.number_format((float) $amount, 2);
        $salesCards = [
            ['label' => 'Gross sales', 'value' => $money($salesSummary['gross_sales']), 'hint' => 'Paid transaction subtotals'],
            ['label' => 'Total discounts', 'value' => $money($salesSummary['discounts']), 'hint' => 'Discounts on paid sales'],
            ['label' => 'Net sales', 'value' => $money($salesSummary['net_sales']), 'hint' => 'Paid transaction totals'],
            ['label' => 'Paid transactions', 'value' => number_format($salesSummary['paid_count']), 'hint' => 'Included in sales revenue'],
            ['label' => 'Pending transactions', 'value' => number_format($salesSummary['pending_count']), 'hint' => 'Awaiting payment confirmation'],
            ['label' => 'Void transactions', 'value' => number_format($salesSummary['void_count']), 'hint' => 'Excluded from sales revenue'],
        ];
        $commissionCards = [
            ['label' => 'Total generated', 'value' => $money($commissionSummary['total_generated']), 'hint' => 'All historical commission records'],
            ['label' => 'Pending total', 'value' => $money($commissionSummary['pending_total']), 'hint' => 'Payable but not settled'],
            ['label' => 'Paid total', 'value' => $money($commissionSummary['paid_total']), 'hint' => 'Already settled'],
            ['label' => 'Void total', 'value' => $money($commissionSummary['void_total']), 'hint' => 'Not payable'],
            ['label' => 'Pending commissions', 'value' => number_format($commissionSummary['pending_count']), 'hint' => 'Awaiting settlement'],
            ['label' => 'Paid commissions', 'value' => number_format($commissionSummary['paid_count']), 'hint' => 'Settled records'],
            ['label' => 'Void commissions', 'value' => number_format($commissionSummary['void_count']), 'hint' => 'Excluded from payable totals'],
        ];
    @endphp

    <div class="mb-6"><a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a></div>

    <form method="GET" action="{{ route('management.reports.index') }}" class="spa-panel mb-7 p-5 sm:p-6">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div><h2 class="font-semibold text-cocoa-950">Report period</h2><p class="mt-1 text-xs text-cocoa-500">Choose a preset or enter a custom transaction date range.</p></div>
            <span class="rounded-full bg-sage-100 px-3 py-1 text-xs font-bold text-sage-700">{{ $rangeLabel }}</span>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <x-form.select name="period" label="Preset period" required>
                <option value="today" @selected($filters['period'] === 'today')>Today</option>
                <option value="this_week" @selected($filters['period'] === 'this_week')>This week</option>
                <option value="custom" @selected($filters['period'] === 'custom')>Custom range</option>
            </x-form.select>
            <x-form.input name="date_from" label="From date" type="date" :value="$filters['date_from']" />
            <x-form.input name="date_to" label="To date" type="date" :value="$filters['date_to']" />
        </div>
        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><x-button :href="route('management.reports.index')" variant="secondary">Reset to today</x-button><x-button :href="route('management.reports.print', $filters)" variant="secondary">Print / Save as PDF</x-button><x-button type="submit">Apply report period</x-button></div>
    </form>

    <section>
        <div class="mb-4"><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Sales overview</p><h2 class="mt-1 text-xl font-semibold text-cocoa-950">Transaction summary</h2></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($salesCards as $card)
                <x-card class="p-5"><p class="text-xs font-bold uppercase tracking-[0.12em] text-cocoa-500">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-semibold text-cocoa-950">{{ $card['value'] }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $card['hint'] }}</p></x-card>
            @endforeach
        </div>
    </section>

    <section class="mt-9">
        <div class="mb-4"><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Therapist earnings</p><h2 class="mt-1 text-xl font-semibold text-cocoa-950">Commission summary</h2></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($commissionCards as $card)
                <x-card class="p-5"><p class="text-xs font-bold uppercase tracking-[0.12em] text-cocoa-500">{{ $card['label'] }}</p><p class="mt-3 text-2xl font-semibold text-cocoa-950">{{ $card['value'] }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $card['hint'] }}</p></x-card>
            @endforeach
        </div>
    </section>

    @if ($report['transactions']->isEmpty() && $report['commissions']->isEmpty())
        <x-empty-state class="mt-9" title="No financial records found" description="No sales or commission records match this report period." />
    @endif

    <div class="mt-9 grid gap-7 2xl:grid-cols-2 2xl:items-start">
        <section>
            <div class="mb-4"><h2 class="text-xl font-semibold text-cocoa-950">Service performance</h2><p class="mt-1 text-sm text-cocoa-500">Paid sales grouped by the service name captured at booking.</p></div>
            <div class="spa-table-wrap">
                <table class="spa-table">
                    <thead><tr><th>Service</th><th>Paid sales</th><th>Gross</th><th>Discounts</th><th>Net</th><th>Average</th></tr></thead>
                    <tbody>
                        @forelse ($report['service_performance'] as $service)
                            <tr>
                                <td class="font-semibold text-cocoa-950">{{ $service['service'] }}</td>
                                <td class="text-cocoa-700">{{ $service['paid_sales_count'] }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($service['gross_sales']) }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($service['discounts']) }}</td>
                                <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $money($service['net_sales']) }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($service['average_sale']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state title="No paid service sales" description="Paid transactions in this period will appear here." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <div class="mb-4"><h2 class="text-xl font-semibold text-cocoa-950">Therapist commission totals</h2><p class="mt-1 text-sm text-cocoa-500">Generated commissions grouped by therapist and settlement status.</p></div>
            <div class="spa-table-wrap">
                <table class="spa-table">
                    <thead><tr><th>Therapist</th><th>Count</th><th>Pending</th><th>Paid</th><th>Void</th><th>Generated</th></tr></thead>
                    <tbody>
                        @forelse ($report['therapist_summary'] as $therapist)
                            <tr>
                                <td class="font-semibold text-cocoa-950">{{ $therapist['therapist'] }}</td>
                                <td class="text-cocoa-700">{{ $therapist['commission_count'] }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($therapist['pending_total']) }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($therapist['paid_total']) }}</td>
                                <td class="whitespace-nowrap text-cocoa-700">{{ $money($therapist['void_total']) }}</td>
                                <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $money($therapist['total_generated']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state title="No therapist commissions" description="Commission records in this period will appear here." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="mt-10">
        <div class="mb-4"><h2 class="text-xl font-semibold text-cocoa-950">Detailed sales records</h2><p class="mt-1 text-sm text-cocoa-500">All paid, pending, and void transactions within the selected period.</p></div>
        <div class="spa-table-wrap">
            <table class="spa-table">
                <thead><tr><th>Date</th><th>Reference</th><th>Appointment / customer</th><th>Therapist</th><th>Service</th><th>Subtotal</th><th>Discount</th><th>Total</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse ($report['transactions'] as $transaction)
                        @php
                            $appointment = $transaction->appointment;
                            $customer = $appointment?->customerProfile ?: $transaction->customerProfile;
                            $customerName = $appointment?->customer_display_name
                                ?? ($customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable');
                            $therapist = $appointment?->therapistProfile;
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap"><p class="font-semibold text-cocoa-950">{{ $transaction->transaction_date->format('M j, Y') }}</p><p class="mt-1 text-xs text-cocoa-500">{{ $transaction->transaction_date->format('g:i A') }}</p></td>
                            <td class="font-bold text-cocoa-950">#{{ $transaction->id }}</td>
                            <td><p class="font-semibold text-cocoa-900">{{ $appointment ? '#'.$appointment->id : 'Appointment unavailable' }}</p><p class="mt-1 whitespace-nowrap text-xs text-cocoa-500">{{ $customerName }}</p></td>
                            <td class="text-cocoa-700">{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Therapist unavailable' }}</td>
                            <td class="text-cocoa-700">{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Service unavailable' }}</td>
                            <td class="whitespace-nowrap text-cocoa-700">{{ $money($transaction->subtotal) }}</td>
                            <td class="whitespace-nowrap text-cocoa-700">{{ $money($transaction->discount_amount) }}</td>
                            <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $money($transaction->total_amount) }}</td>
                            <td><x-status-badge :status="$transaction->payment_status" /></td>
                            <td class="text-right"><x-button :href="route('management.transactions.show', $transaction)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><x-empty-state title="No sales records" description="No transactions match this report period." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-10">
        <div class="mb-4"><h2 class="text-xl font-semibold text-cocoa-950">Detailed commission records</h2><p class="mt-1 text-sm text-cocoa-500">Commission snapshots linked to transactions in the selected period.</p></div>
        <div class="spa-table-wrap">
            <table class="spa-table">
                <thead><tr><th>Reference</th><th>Therapist</th><th>Transaction date</th><th>Appointment / customer</th><th>Base</th><th>Rate</th><th>Commission</th><th>Status</th><th class="text-right">Action</th></tr></thead>
                <tbody>
                    @forelse ($report['commissions'] as $commission)
                        @php
                            $commissionTherapist = $commission->therapistProfile ? trim($commission->therapistProfile->first_name.' '.$commission->therapistProfile->last_name) : ($commission->therapistUser?->name ?: 'Therapist unavailable.');
                            $commissionCustomer = $commission->appointment?->customerProfile;
                            $commissionCustomerName = $commission->appointment?->customer_display_name
                                ?? ($commissionCustomer ? trim($commissionCustomer->first_name.' '.$commissionCustomer->last_name) : 'Customer unavailable');
                        @endphp
                        <tr>
                            <td class="font-bold text-cocoa-950">#{{ $commission->id }}</td>
                            <td class="text-cocoa-700">{{ $commissionTherapist }}</td>
                            <td class="whitespace-nowrap text-cocoa-700">{{ $commission->transaction?->transaction_date?->format('M j, Y') ?? 'Unavailable' }}</td>
                            <td><p class="font-semibold text-cocoa-900">{{ $commission->appointment ? '#'.$commission->appointment->id : 'Appointment unavailable' }}</p><p class="mt-1 whitespace-nowrap text-xs text-cocoa-500">{{ $commissionCustomerName }}</p></td>
                            <td class="whitespace-nowrap text-cocoa-700">{{ $money($commission->commission_base_amount) }}</td>
                            <td class="whitespace-nowrap text-cocoa-700">{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                            <td class="whitespace-nowrap font-semibold text-cocoa-950">{{ $money($commission->commission_amount) }}</td>
                            <td><x-status-badge :status="$commission->status" /></td>
                            <td class="text-right"><x-button :href="route('management.commissions.show', $commission)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty-state title="No commission records" description="No commissions match this report period." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
