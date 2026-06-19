@extends('layouts.app')

@section('title', 'Therapist | Casa Paraiso Spa Management System')
@section('page_title', 'Therapist Area')
@section('page_description', 'Review your assigned Casa Paraiso appointments and upcoming work schedule.')

@section('content')
    <div class="grid gap-4 md:grid-cols-2">
        <a href="{{ route('therapist.schedule.index') }}" class="rounded-lg border border-emerald-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md"><h2 class="text-lg font-semibold text-zinc-950">My Schedule</h2><p class="mt-2 text-sm leading-6 text-zinc-600">View today's customers and upcoming assigned appointments.</p><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700">View schedule</p></a>
        <a href="{{ route('notifications.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md"><h2 class="text-lg font-semibold text-zinc-950">Notifications</h2><p class="mt-2 text-sm leading-6 text-zinc-600">Review new assignments and appointment status changes.</p><p class="mt-4 text-xs font-semibold uppercase tracking-wide text-emerald-700">View notifications</p></a>
    </div>
@endsection
