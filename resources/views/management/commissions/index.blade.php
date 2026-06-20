@extends('layouts.app')

@section('title', 'Therapist Commissions | Casa Paraiso')
@section('page_title', 'Therapist Commissions')
@section('page_description', 'Monitor calculated therapist earnings and settle pending commissions.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Management dashboard</a></div>

    <form method="GET" action="{{ route('management.commissions.index') }}" class="spa-panel mb-7 p-5 sm:p-6">
        <div class="mb-5"><h2 class="font-semibold text-cocoa-950">Filter commissions</h2><p class="mt-1 text-xs text-cocoa-500">Narrow records by therapist, status, or transaction date.</p></div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-form.select name="therapist_profile_id" label="Therapist"><option value="">All therapists</option>@foreach ($therapists as $therapist)<option value="{{ $therapist->id }}" @selected((string) ($filters['therapist_profile_id'] ?? '') === (string) $therapist->id)>{{ trim($therapist->first_name.' '.$therapist->last_name) }}</option>@endforeach</x-form.select>
            <x-form.select name="status" label="Status"><option value="">All statuses</option>@foreach (\App\Models\TherapistCommission::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</x-form.select>
            <x-form.input name="date_from" label="From date" type="date" :value="$filters['date_from'] ?? ''" />
            <x-form.input name="date_to" label="To date" type="date" :value="$filters['date_to'] ?? ''" />
        </div>
        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><x-button :href="route('management.commissions.index')" variant="secondary">Clear filters</x-button><x-button type="submit">Apply filters</x-button></div>
    </form>

    <div class="spa-table-wrap">
        <table class="spa-table">
            <thead><tr><th>Reference</th><th>Therapist</th><th>Transaction date</th><th>Base</th><th>Rate</th><th>Commission</th><th>Status</th><th class="text-right">Action</th></tr></thead>
            <tbody>
                @forelse ($commissions as $commission)
                    <tr>
                        <td class="font-bold text-cocoa-950">#{{ $commission->id }}</td>
                        <td class="text-cocoa-700">{{ $commission->therapistProfile ? trim($commission->therapistProfile->first_name.' '.$commission->therapistProfile->last_name) : ($commission->therapistUser?->name ?? 'Therapist unavailable') }}</td>
                        <td class="whitespace-nowrap text-cocoa-600">{{ $commission->transaction?->transaction_date?->format('M j, Y') ?? 'Unavailable' }}</td>
                        <td class="whitespace-nowrap text-cocoa-700">PHP {{ number_format((float) $commission->commission_base_amount, 2) }}</td>
                        <td class="whitespace-nowrap text-cocoa-700">{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                        <td class="whitespace-nowrap font-semibold text-cocoa-950">PHP {{ number_format((float) $commission->commission_amount, 2) }}</td>
                        <td><x-status-badge :status="$commission->status" /></td>
                        <td class="text-right"><x-button :href="route('management.commissions.show', $commission)" variant="secondary" class="min-h-9 px-3 py-1.5">View</x-button></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state title="No commissions found" description="Paid cash transactions with assigned therapists will appear here automatically." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $commissions->links() }}</div>
@endsection
