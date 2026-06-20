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

        <aside class="lg:sticky lg:top-28">
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
