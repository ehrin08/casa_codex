<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Financial Report | Casa Paraiso</title>
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; color: #171717; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eee; font-size: 12px; line-height: 1.4; }
        .report { width: min(1200px, calc(100% - 32px)); margin: 24px auto; padding: 32px; background: #fff; }
        .actions { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 20px; }
        .button { border: 1px solid #555; border-radius: 4px; padding: 9px 14px; background: #fff; color: #111; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 16px; border-bottom: 2px solid #111; }
        h1, h2 { margin: 0; }
        h1 { font-size: 24px; }
        h2 { margin-bottom: 8px; font-size: 16px; }
        .subtitle { margin: 3px 0 0; font-size: 14px; }
        .meta { margin: 0; text-align: right; }
        .meta dt { font-weight: 700; }
        .meta dd { margin: 0 0 3px; }
        section { margin-top: 24px; break-inside: avoid; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid #777; border-left: 1px solid #777; }
        .metric { padding: 10px; border-right: 1px solid #777; border-bottom: 1px solid #777; }
        .metric span { display: block; color: #444; }
        .metric strong { display: block; margin-top: 2px; font-size: 15px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th, td { padding: 6px; border: 1px solid #777; text-align: left; vertical-align: top; }
        th { background: #e8e8e8; font-weight: 700; }
        td.number, th.number { text-align: right; white-space: nowrap; }
        tr { break-inside: avoid; }
        .empty { padding: 16px; text-align: center; color: #444; }
        .status { text-transform: capitalize; }
        @page { size: landscape; margin: 12mm; }
        @media print {
            body { background: #fff; font-size: 10px; }
            .screen-only { display: none !important; }
            .report { width: auto; margin: 0; padding: 0; }
            section { margin-top: 16px; }
            thead { display: table-header-group; }
            .table-wrap { overflow: visible; }
        }
        @media (max-width: 700px) {
            .report { width: 100%; margin: 0; padding: 16px; }
            .header { display: block; }
            .meta { margin-top: 12px; text-align: left; }
            .summary { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
@php
    $sales = $report['sales_summary'];
    $commissions = $report['commission_summary'];
    $money = fn ($amount) => 'PHP '.number_format((float) $amount, 2);
@endphp
<main class="report">
    <div class="actions screen-only">
        <a class="button" href="{{ route('management.reports.index', $filters) }}">Back to reports</a>
        <button class="button" type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <header class="header">
        <div><h1>Casa Paraiso</h1><p class="subtitle">Sales and Commission Financial Report</p></div>
        <dl class="meta">
            <dt>Report period</dt><dd>{{ $rangeLabel }}</dd>
            <dt>Generated</dt><dd>{{ now()->format('M j, Y g:i A') }}</dd>
            <dt>Purpose</dt><dd>Prepared for management review / portfolio evidence</dd>
        </dl>
    </header>

    <section><h2>Sales Summary</h2><div class="summary">
        @foreach ([['Gross sales / subtotal', $money($sales['gross_sales'])], ['Total discounts', $money($sales['discounts'])], ['Net sales / total amount', $money($sales['net_sales'])], ['Paid transaction count', $sales['paid_count']], ['Pending transaction count', $sales['pending_count']], ['Void transaction count', $sales['void_count']]] as [$label, $value])
            <div class="metric"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
        @endforeach
    </div></section>

    <section><h2>Commission Summary</h2><div class="summary">
        @foreach ([['Total commissions generated', $money($commissions['total_generated'])], ['Pending commission total', $money($commissions['pending_total'])], ['Paid commission total', $money($commissions['paid_total'])], ['Void commission total', $money($commissions['void_total'])], ['Pending commission count', $commissions['pending_count']], ['Paid commission count', $commissions['paid_count']], ['Void commission count', $commissions['void_count']]] as [$label, $value])
            <div class="metric"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
        @endforeach
    </div></section>

    <section><h2>Service Performance</h2><div class="table-wrap"><table><thead><tr><th>Service</th><th class="number">Number of paid sales</th><th class="number">Gross sales</th><th class="number">Discounts</th><th class="number">Net sales</th><th class="number">Average sale value</th></tr></thead><tbody>
        @forelse ($report['service_performance'] as $service)<tr><td>{{ $service['service'] }}</td><td class="number">{{ $service['paid_sales_count'] }}</td><td class="number">{{ $money($service['gross_sales']) }}</td><td class="number">{{ $money($service['discounts']) }}</td><td class="number">{{ $money($service['net_sales']) }}</td><td class="number">{{ $money($service['average_sale']) }}</td></tr>
        @empty <tr><td class="empty" colspan="6">No paid service sales match this report period.</td></tr> @endforelse
    </tbody></table></div></section>

    <section><h2>Therapist Commission Summary</h2><div class="table-wrap"><table><thead><tr><th>Therapist</th><th class="number">Commission count</th><th class="number">Pending total</th><th class="number">Paid total</th><th class="number">Void total</th><th class="number">Total generated</th></tr></thead><tbody>
        @forelse ($report['therapist_summary'] as $therapist)<tr><td>{{ $therapist['therapist'] }}</td><td class="number">{{ $therapist['commission_count'] }}</td><td class="number">{{ $money($therapist['pending_total']) }}</td><td class="number">{{ $money($therapist['paid_total']) }}</td><td class="number">{{ $money($therapist['void_total']) }}</td><td class="number">{{ $money($therapist['total_generated']) }}</td></tr>
        @empty <tr><td class="empty" colspan="6">No therapist commissions match this report period.</td></tr> @endforelse
    </tbody></table></div></section>

    <section><h2>Detailed Sales Records</h2><div class="table-wrap"><table><thead><tr><th>Date</th><th>Transaction reference</th><th>Appointment / customer</th><th>Therapist</th><th>Service</th><th class="number">Subtotal</th><th class="number">Discount</th><th class="number">Total</th><th>Payment status</th></tr></thead><tbody>
        @forelse ($report['transactions'] as $transaction)
            @php $appointment = $transaction->appointment; $customer = $appointment?->customerProfile ?: $transaction->customerProfile; $customerName = $appointment?->customer_display_name ?? ($customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable'); $therapist = $appointment?->therapistProfile; @endphp
            <tr><td>{{ $transaction->transaction_date->format('M j, Y g:i A') }}</td><td>#{{ $transaction->id }}</td><td>{{ $appointment ? '#'.$appointment->id : 'Appointment unavailable' }} / {{ $customerName }}</td><td>{{ $therapist ? trim($therapist->first_name.' '.$therapist->last_name) : 'Therapist unavailable' }}</td><td>{{ $appointment?->service_name_snapshot ?: $appointment?->service?->name ?: 'Service unavailable' }}</td><td class="number">{{ $money($transaction->subtotal) }}</td><td class="number">{{ $money($transaction->discount_amount) }}</td><td class="number">{{ $money($transaction->total_amount) }}</td><td class="status">{{ $transaction->payment_status }}</td></tr>
        @empty <tr><td class="empty" colspan="9">No sales records match this report period.</td></tr> @endforelse
    </tbody></table></div></section>

    <section><h2>Detailed Commission Records</h2><div class="table-wrap"><table><thead><tr><th>Commission reference</th><th>Therapist</th><th>Transaction date</th><th>Appointment / customer</th><th class="number">Base amount</th><th class="number">Rate</th><th class="number">Commission amount</th><th>Status</th></tr></thead><tbody>
        @forelse ($report['commissions'] as $commission)
            @php $therapist = $commission->therapistProfile ? trim($commission->therapistProfile->first_name.' '.$commission->therapistProfile->last_name) : ($commission->therapistUser?->name ?: 'Therapist unavailable'); $customer = $commission->appointment?->customerProfile; $customerName = $commission->appointment?->customer_display_name ?? ($customer ? trim($customer->first_name.' '.$customer->last_name) : 'Customer unavailable'); @endphp
            <tr><td>#{{ $commission->id }}</td><td>{{ $therapist }}</td><td>{{ $commission->transaction?->transaction_date?->format('M j, Y g:i A') ?? 'Unavailable' }}</td><td>{{ $commission->appointment ? '#'.$commission->appointment->id : 'Appointment unavailable' }} / {{ $customerName }}</td><td class="number">{{ $money($commission->commission_base_amount) }}</td><td class="number">{{ number_format((float) $commission->commission_rate, 2) }}%</td><td class="number">{{ $money($commission->commission_amount) }}</td><td class="status">{{ $commission->status }}</td></tr>
        @empty <tr><td class="empty" colspan="8">No commission records match this report period.</td></tr> @endforelse
    </tbody></table></div></section>
</main>
</body>
</html>
