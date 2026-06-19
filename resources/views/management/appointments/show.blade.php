@extends('layouts.app')

@section('title', 'Appointment #'.$appointment->id.' | Casa Paraiso Spa Management System')
@section('page_title', 'Appointment #'.$appointment->id)
@section('page_description', 'Review booking details, update status, and inspect the status history.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('management.appointments.index') }}" class="text-sm font-medium text-zinc-600 hover:text-zinc-950">Back to appointments</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(300px,2fr)]">
        <div class="space-y-6">
            <section class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-5">
                    <h2 class="text-lg font-semibold text-zinc-950">Booking details</h2>
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-semibold text-zinc-700">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                </div>

                <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-sm font-medium text-zinc-500">Customer</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->customerProfile ? trim($appointment->customerProfile->first_name.' '.$appointment->customerProfile->last_name) : 'Customer unavailable' }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Therapist</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->therapistProfile ? trim($appointment->therapistProfile->first_name.' '.$appointment->therapistProfile->last_name) : 'Therapist unavailable' }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Service</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->service_name_snapshot ?: 'Service unavailable' }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Price snapshot</dt><dd class="mt-1 font-semibold text-zinc-900">PHP {{ number_format((float) $appointment->service_price_snapshot, 2) }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Date</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->appointment_date->format('F j, Y') }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Time</dt><dd class="mt-1 font-semibold text-zinc-900">{{ date('g:i A', strtotime($appointment->start_time)) }} to {{ date('g:i A', strtotime($appointment->end_time)) }}</dd></div>
                    <div><dt class="text-sm font-medium text-zinc-500">Duration</dt><dd class="mt-1 font-semibold text-zinc-900">{{ $appointment->service_duration_minutes_snapshot }} minutes</dd></div>
                    @if ($appointment->notes)
                        <div class="sm:col-span-2"><dt class="text-sm font-medium text-zinc-500">Customer notes</dt><dd class="mt-1 whitespace-pre-line text-zinc-900">{{ $appointment->notes }}</dd></div>
                    @endif
                </dl>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-zinc-950">Status history</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($appointment->statusHistories as $history)
                        <article class="rounded-md border border-zinc-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-zinc-900">{{ $history->from_status ? ucfirst(str_replace('_', ' ', $history->from_status)) : 'Initial status' }} to {{ ucfirst(str_replace('_', ' ', $history->to_status)) }}</p>
                                <time class="text-xs text-zinc-500">{{ $history->changed_at->format('M j, Y g:i A') }}</time>
                            </div>
                            <p class="mt-2 text-sm text-zinc-600">Changed by {{ $history->changedBy?->name ?? 'System or deleted user' }}</p>
                            @if ($history->note)
                                <p class="mt-2 whitespace-pre-line text-sm text-zinc-700">{{ $history->note }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-zinc-500">No status changes have been recorded.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside>
            <form method="POST" action="{{ route('management.appointments.update-status', $appointment) }}" class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
                @csrf
                @method('PATCH')
                <h2 class="text-lg font-semibold text-zinc-950">Update status</h2>

                <div class="mt-5">
                    <label for="status" class="block text-sm font-medium text-zinc-700">Status</label>
                    <select id="status" name="status" required class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">
                        @foreach (\App\Models\Appointment::STATUSES as $status)
                            <option value="{{ $status }}" @selected(old('status', $appointment->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="mt-5">
                    <label for="status_notes" class="block text-sm font-medium text-zinc-700">Status notes <span class="font-normal text-zinc-500">(optional)</span></label>
                    <textarea id="status_notes" name="status_notes" rows="4" maxlength="2000" class="mt-2 block w-full rounded-md border border-zinc-300 px-3 py-2 focus:border-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-600/20">{{ old('status_notes') }}</textarea>
                    @error('status_notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="mt-5 w-full rounded-md bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Save status</button>
            </form>
        </aside>
    </div>
@endsection
