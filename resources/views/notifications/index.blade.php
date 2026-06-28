@extends('layouts.app')

@section('title', 'Notifications | Casa Paraiso')
@section('page_title', 'Notifications')
@section('page_description', 'Stay current on appointment requests, assignments, and status updates.')

@section('content')
    <div class="space-y-4">
        @forelse ($notifications as $notification)
            @php
                $appointmentId = $notification->data['appointment_id'] ?? null;
            @endphp
            <article @class([
                'rounded-2xl border p-5 shadow-[0_16px_40px_-32px_rgba(48,33,28,0.5)] transition sm:p-6',
                'border-cream-200 bg-white' => $notification->is_read,
                'border-sage-200 bg-sage-50 ring-1 ring-sage-100' => ! $notification->is_read,
            ])>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex gap-4">
                        <span @class(['mt-1 size-2.5 shrink-0 rounded-full', 'bg-cream-300' => $notification->is_read, 'bg-sage-600 ring-4 ring-sage-100' => ! $notification->is_read]) aria-hidden="true"></span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2"><h2 class="font-semibold text-cocoa-950">{{ $notification->title }}</h2>@unless ($notification->is_read)<x-status-badge status="unread" />@endunless</div>
                            <p class="mt-2 text-sm leading-6 text-cocoa-700">{{ $notification->message }}</p>
                            <p class="mt-2 text-xs font-medium text-cocoa-500">{{ $notification->created_at->format('M j, Y g:i A') }}</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2 pl-6 sm:pl-0">
                        @if ($appointmentId && $appointmentRoute)<x-button :href="route($appointmentRoute, ['appointment' => $appointmentId])" variant="secondary" class="min-h-9 px-3 py-1.5">View appointment</x-button>@endif
                        @unless ($notification->is_read)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf @method('PATCH')<x-button type="submit" class="min-h-9 px-3 py-1.5">Mark as read</x-button></form>@endunless
                    </div>
                </div>
            </article>
        @empty
            <x-empty-state title="You're all caught up" description="New appointment activity and status updates will appear here." />
        @endforelse
    </div>
    <div class="mt-6">{{ $notifications->links() }}</div>
@endsection
