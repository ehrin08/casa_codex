@extends('layouts.app')

@section('title', 'Casa Paraiso Spa Management System')
@section('page_title', 'Casa Paraiso Spa Management System')
@section('page_description', 'A web-based service management and appointment booking system for Casa Paraiso - Body and Wellness Spa.')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-zinc-950">Foundation setup is ready</h2>
            <p class="mt-3 leading-7 text-zinc-600">
                This Sprint 1 foundation includes Laravel, Livewire, Tailwind CSS, Vite, MySQL migrations, seeders, shared routes, and placeholder pages for the main system areas.
            </p>
            <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                The system is currently in Sprint 1 foundation setup. Full authentication, dashboards, booking, transactions, promotions, analytics, and notifications will be added in later tasks.
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-zinc-950">Main areas</h2>
            <p class="mt-3 leading-7 text-zinc-600">
                Use these placeholder pages to confirm the base navigation and role-area structure.
            </p>
        </div>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
        <a href="{{ route('management.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-emerald-600 hover:shadow-md">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Management</p>
            <h3 class="mt-2 text-lg font-semibold text-zinc-950">Operations foundation</h3>
            <p class="mt-2 text-sm leading-6 text-zinc-600">Placeholder for services, staff, customers, appointments, reports, promotions, and reviews.</p>
        </a>

        <a href="{{ route('therapist.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-sky-600 hover:shadow-md">
            <p class="text-sm font-semibold uppercase tracking-wide text-sky-700">Therapist</p>
            <h3 class="mt-2 text-lg font-semibold text-zinc-950">Staff workspace</h3>
            <p class="mt-2 text-sm leading-6 text-zinc-600">Placeholder for schedules, assigned appointments, commissions, and customer notes.</p>
        </a>

        <a href="{{ route('customer.index') }}" class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-rose-600 hover:shadow-md">
            <p class="text-sm font-semibold uppercase tracking-wide text-rose-700">Customer</p>
            <h3 class="mt-2 text-lg font-semibold text-zinc-950">Customer portal</h3>
            <p class="mt-2 text-sm leading-6 text-zinc-600">Placeholder for appointment booking, personal appointments, services, promotions, and reviews.</p>
        </a>
    </div>
@endsection
