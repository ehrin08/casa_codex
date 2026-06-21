@extends('layouts.app')

@section('title', 'Appointment #'.$appointment->id.' | Casa Paraiso')
@section('page_title', 'Appointment #'.$appointment->id)
@section('page_description', 'Review booking details, update progress, and follow the complete status history.')

@section('content')
    <div class="mb-6"><a href="{{ route('management.appointments.index') }}" class="spa-back-link"><span aria-hidden="true">&larr;</span> Back to appointments</a></div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(300px,2fr)] lg:items-start">
        <div class="space-y-6">
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-cream-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Booking record</p><h2 class="mt-1 text-lg font-semibold text-cocoa-950">Appointment details</h2></div><x-status-badge :status="$appointment->status" class="px-3 py-1.5" /></div>
                <dl class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2">
                    <div><dt class="spa-detail-label">Customer</dt><dd class="spa-detail-value">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Customer unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Therapist</dt><dd class="spa-detail-value">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Therapist unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Service</dt><dd class="spa-detail-value">{{ $appointment->service_name_snapshot ?: 'Service unavailable' }}</dd></div>
                    <div><dt class="spa-detail-label">Price snapshot</dt><dd class="spa-detail-value">PHP {{ number_format((float) $appointment->service_price_snapshot, 2) }}</dd></div>
                    <div><dt class="spa-detail-label">Date</dt><dd class="spa-detail-value">{{ $appointment->appointment_date->format('F j, Y') }}</dd></div>
                    <div><dt class="spa-detail-label">Time</dt><dd class="spa-detail-value">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</dd></div>
                    <div><dt class="spa-detail-label">Duration</dt><dd class="spa-detail-value">{{ $appointment->service_duration_minutes_snapshot }} minutes</dd></div>
                    @if ($appointment->notes)<div class="sm:col-span-2"><dt class="spa-detail-label">Customer notes</dt><dd class="mt-1.5 whitespace-pre-line leading-6 text-cocoa-800">{{ $appointment->notes }}</dd></div>@endif
                </dl>
            </x-card>

            <x-card>
                <div class="flex flex-col gap-3 border-b border-cream-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Assignment support</p>
                        <h2 class="mt-1 text-lg font-semibold text-cocoa-950">Therapist recommendations</h2>
                        <p class="mt-1 text-sm leading-6 text-cocoa-500">Ranked using full-window availability, schedule conflicts, and blocking same-day appointments.</p>
                    </div>
                    <div class="shrink-0 rounded-xl bg-cream-100 px-4 py-3 sm:text-right">
                        <p class="text-xs font-bold uppercase tracking-wide text-cocoa-500">Current therapist</p>
                        <p class="mt-1 font-semibold text-cocoa-900">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Not assigned' }}</p>
                    </div>
                </div>

                <div class="spa-table-wrap mt-6">
                    <table class="spa-table">
                        <thead><tr><th>Rank</th><th>Therapist</th><th>Availability</th><th>Conflict</th><th>Workload</th><th>Recommendation</th></tr></thead>
                        <tbody>
                            @forelse ($therapistRecommendations as $index => $recommendation)
                                @php
                                    $labelClasses = match ($recommendation['label']) {
                                        'Best match' => 'bg-sage-100 text-sage-800 ring-sage-600/20',
                                        'Available', 'Current therapist' => 'bg-cream-200 text-cocoa-800 ring-cocoa-500/20',
                                        'Has conflict' => 'bg-amber-100 text-amber-900 ring-amber-600/20',
                                        default => 'bg-rose-100 text-rose-800 ring-rose-600/20',
                                    };
                                @endphp
                                <tr>
                                    <td class="font-bold text-cocoa-500">{{ $index + 1 }}</td>
                                    <td>
                                        <p class="font-semibold text-cocoa-950">{{ trim($recommendation['therapist']->first_name.' '.$recommendation['therapist']->last_name) }}</p>
                                        @if ($recommendation['is_current'])<span class="mt-1 inline-flex rounded-full bg-sage-50 px-2 py-0.5 text-[0.7rem] font-bold uppercase tracking-wide text-sage-700 ring-1 ring-inset ring-sage-600/20">Current</span>@endif
                                    </td>
                                    <td><span class="font-semibold {{ $recommendation['is_available'] ? 'text-sage-700' : 'text-rose-700' }}">{{ $recommendation['is_available'] ? 'Available' : 'Unavailable' }}</span><p class="mt-1 text-xs text-cocoa-500">Full appointment window</p></td>
                                    <td><span class="font-semibold {{ $recommendation['has_conflict'] ? 'text-amber-800' : 'text-sage-700' }}">{{ $recommendation['has_conflict'] ? 'Has conflict' : 'No conflict' }}</span></td>
                                    <td><span class="font-semibold text-cocoa-900">{{ $recommendation['workload_count'] }}</span><p class="mt-1 text-xs text-cocoa-500">blocking {{ Str::plural('appointment', $recommendation['workload_count']) }}</p></td>
                                    <td class="min-w-64"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset {{ $labelClasses }}">{{ $recommendation['label'] }}</span><p class="mt-2 text-xs leading-5 text-cocoa-600">{{ $recommendation['reason'] }}</p></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state title="No active therapists" description="Activate a therapist profile before reviewing assignment options." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card>
                <h2 class="spa-section-title">Status history</h2>
                <p class="mt-1 text-sm text-cocoa-500">A chronological record of every appointment change.</p>
                <div class="relative mt-6 space-y-0 before:absolute before:bottom-3 before:left-[0.45rem] before:top-3 before:w-px before:bg-cream-300">
                    @forelse ($appointment->statusHistories as $history)
                        <article class="relative pb-7 pl-8 last:pb-0">
                            <span class="absolute left-0 top-1 size-4 rounded-full border-4 border-white bg-sage-600 ring-1 ring-sage-200"></span>
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <p class="font-semibold text-cocoa-900">{{ $history->from_status ? ucfirst(str_replace('_', ' ', $history->from_status)) : 'Initial status' }} to {{ ucfirst(str_replace('_', ' ', $history->to_status)) }}</p>
                                <time class="shrink-0 text-xs font-medium text-cocoa-500">{{ $history->changed_at->format('M j, Y g:i A') }}</time>
                            </div>
                            <p class="mt-1 text-sm text-cocoa-500">Changed by {{ $history->changedBy?->name ?? 'System or deleted user' }}</p>
                            @if ($history->note)<p class="mt-2 rounded-xl bg-cream-100 p-3 whitespace-pre-line text-sm leading-6 text-cocoa-700">{{ $history->note }}</p>@endif
                        </article>
                    @empty
                        <x-empty-state title="No status changes" description="The appointment is still on its initial status." />
                    @endforelse
                </div>
            </x-card>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-28">
            @if ($appointment->transaction)
                <x-card>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Cash transaction</p>
                    <h2 class="mt-1 text-lg font-semibold text-cocoa-950">Receipt recorded</h2>
                    <p class="mt-2 text-sm leading-6 text-cocoa-500">A {{ $appointment->transaction->payment_status }} cash transaction for PHP {{ number_format((float) $appointment->transaction->total_amount, 2) }} is linked to this appointment.</p>
                    <x-button :href="route('management.transactions.show', $appointment->transaction)" variant="secondary" class="mt-5 w-full">View receipt</x-button>
                </x-card>
            @elseif ($appointment->status === \App\Models\Appointment::STATUS_COMPLETED)
                <x-card class="border-sage-200 bg-sage-50">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Ready for payment</p>
                    <h2 class="mt-1 text-lg font-semibold text-cocoa-950">Record cash transaction</h2>
                    <p class="mt-2 text-sm leading-6 text-cocoa-600">This completed appointment is eligible for one over-the-counter cash transaction.</p>
                    <x-button :href="route('management.transactions.create', ['appointment_id' => $appointment->id])" class="mt-5 w-full">Record transaction</x-button>
                </x-card>
            @else
                <x-alert title="Transaction not available">Set this appointment to completed before recording its cash transaction.</x-alert>
            @endif

            <form method="POST" action="{{ route('management.appointments.update-status', $appointment) }}" class="spa-panel p-6">
                @csrf
                @method('PATCH')
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-sage-700">Appointment action</p>
                <h2 class="mt-1 text-lg font-semibold text-cocoa-950">Update status</h2>
                <div class="mt-6 space-y-5">
                    <x-form.select name="status" label="Status" required>@foreach (\App\Models\Appointment::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $appointment->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</x-form.select>
                    <x-form.textarea name="status_notes" label="Status notes (optional)" rows="4" maxlength="2000" />
                    <x-button type="submit" class="w-full">Save status</x-button>
                </div>
            </form>
        </aside>
    </div>
@endsection
