@extends('layouts.app')

@section('title', 'Notifications | Casa Paraiso Spa Management System')
@section('page_title', 'Notifications')
@section('page_description', 'Review appointment updates sent to your account.')

@section('content')
    <div class="space-y-4">
        @forelse ($notifications as $notification)
            @php
                $appointmentId = $notification->data['appointment_id'] ?? null;
                $appointmentRoute = match (true) {
                    auth()->user()->isManagement() => 'management.appointments.show',
                    auth()->user()->isTherapist() => 'therapist.appointments.show',
                    auth()->user()->isCustomer() => 'customer.appointments.show',
                    default => null,
                };
            @endphp
            <article class="rounded-lg border p-5 shadow-sm {{ $notification->is_read ? 'border-zinc-200 bg-white' : 'border-emerald-200 bg-emerald-50' }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-semibold text-zinc-950">{{ $notification->title }}</h2>
                            @unless ($notification->is_read)<span class="rounded-full bg-emerald-700 px-2 py-0.5 text-xs font-semibold text-white">Unread</span>@endunless
                        </div>
                        <p class="mt-2 text-sm leading-6 text-zinc-700">{{ $notification->message }}</p>
                        <p class="mt-2 text-xs text-zinc-500">{{ $notification->created_at->format('M j, Y g:i A') }}</p>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if ($appointmentId && $appointmentRoute)
                            <a href="{{ route($appointmentRoute, ['appointment' => $appointmentId]) }}" class="rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50">View appointment</a>
                        @endif
                        @unless ($notification->is_read)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-md bg-emerald-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-800">Mark as read</button>
                            </form>
                        @endunless
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">You have no notifications.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
