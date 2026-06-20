<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#50362a">

        <title>@yield('title', 'Casa Paraiso | Body and Wellness Spa')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-cream-100 text-cocoa-900 antialiased">
        @php
            $navLinks = [['label' => 'Home', 'route' => 'home', 'active' => 'home']];
            $wideManagementNav = false;

            if (auth()->check()) {
                if (auth()->user()->isManagement()) {
                    $wideManagementNav = true;
                    $navLinks = array_merge($navLinks, [
                        ['label' => 'Dashboard', 'route' => 'management.index', 'active' => 'management.index'],
                        ['label' => 'Appointments', 'route' => 'management.appointments.index', 'active' => 'management.appointments.*'],
                        ['label' => 'Transactions', 'route' => 'management.transactions.index', 'active' => 'management.transactions.*'],
                        ['label' => 'Commissions', 'route' => 'management.commissions.index', 'active' => 'management.commissions.*'],
                        ['label' => 'Services', 'route' => 'management.services.index', 'active' => 'management.services.*'],
                        ['label' => 'Therapists', 'route' => 'management.therapists.index', 'active' => 'management.therapists.*'],
                        ['label' => 'Customers', 'route' => 'management.customers.index', 'active' => 'management.customers.*'],
                        ['label' => 'Availability', 'route' => 'management.availability.index', 'active' => 'management.availability.*'],
                    ]);
                } elseif (auth()->user()->isTherapist()) {
                    $navLinks = array_merge($navLinks, [
                        ['label' => 'Dashboard', 'route' => 'therapist.index', 'active' => 'therapist.index'],
                        ['label' => 'My schedule', 'route' => 'therapist.schedule.index', 'active' => ['therapist.schedule.*', 'therapist.appointments.*']],
                        ['label' => 'Commissions', 'route' => 'therapist.commissions.index', 'active' => 'therapist.commissions.*'],
                    ]);
                } elseif (auth()->user()->isCustomer()) {
                    $navLinks = array_merge($navLinks, [
                        ['label' => 'Dashboard', 'route' => 'customer.index', 'active' => 'customer.index'],
                        ['label' => 'Book', 'route' => 'customer.appointments.create', 'active' => 'customer.appointments.create'],
                        ['label' => 'My appointments', 'route' => 'customer.appointments.index', 'active' => ['customer.appointments.index', 'customer.appointments.show']],
                    ]);
                }

                $navLinks[] = ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => 'notifications.*'];
                $unreadNotificationCount = auth()->user()->systemNotifications()->where('is_read', false)->count();
                $roleLabel = auth()->user()->role?->name ?? 'Member';
            }
        @endphp

        <div class="flex min-h-screen flex-col">
            <div class="bg-cocoa-900 px-4 py-2 text-center text-xs font-medium tracking-wide text-cream-200">
                Your quiet place for wellness appointments and spa care
            </div>

            <header class="sticky top-0 z-40 border-b border-cream-200/80 bg-cream-50/95 shadow-sm backdrop-blur">
                <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-5 px-4 py-3 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3 rounded-lg" aria-label="Casa Paraiso home">
                        <span class="flex size-11 items-center justify-center rounded-full bg-sage-700 text-cream-50 shadow-sm transition group-hover:bg-sage-800" aria-hidden="true">
                            <svg viewBox="0 0 32 32" fill="none" class="size-7" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg>
                        </span>
                        <span>
                            <span class="block text-base font-black tracking-[0.12em] text-cocoa-800 sm:text-lg">CASA PARAISO</span>
                            <span class="block text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-sage-700">Body and Wellness Spa</span>
                        </span>
                    </a>

                    <nav class="{{ $wideManagementNav ? 'hidden items-center gap-1 2xl:flex' : 'hidden items-center gap-1 xl:flex' }}" aria-label="Primary navigation">
                        @foreach ($navLinks as $link)
                            @php
                                $isActive = request()->routeIs($link['active']);
                            @endphp
                            <a href="{{ route($link['route']) }}" @class([
                                'rounded-xl px-3 py-2 text-sm font-semibold transition',
                                'bg-cocoa-700 text-white shadow-sm' => $isActive,
                                'text-cocoa-600 hover:bg-cream-200 hover:text-cocoa-900' => ! $isActive,
                            ]) @if($isActive) aria-current="page" @endif>
                                {{ $link['label'] }}
                                @if (($link['route'] ?? null) === 'notifications.index' && ($unreadNotificationCount ?? 0))
                                    <span class="ml-1 rounded-full bg-gold-300 px-1.5 py-0.5 text-[0.65rem] font-black text-cocoa-900">{{ $unreadNotificationCount }}</span>
                                @endif
                            </a>
                        @endforeach

                        @guest
                            <x-button :href="route('login')" class="ml-2">Log in</x-button>
                        @else
                            <div class="ml-2 hidden border-l border-cream-300 pl-3 2xl:block">
                                <p class="max-w-28 truncate text-xs font-bold text-cocoa-800">{{ auth()->user()->name }}</p>
                                <p class="text-[0.65rem] font-semibold capitalize text-cocoa-500">{{ $roleLabel }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-button type="submit" variant="ghost" class="px-3">Log out</x-button>
                            </form>
                        @endguest
                    </nav>

                    <details class="group relative {{ $wideManagementNav ? '2xl:hidden' : 'xl:hidden' }}">
                        <summary class="flex min-h-10 cursor-pointer list-none items-center gap-2 rounded-xl border border-cream-300 bg-white px-3 py-2 text-sm font-bold text-cocoa-700 [&::-webkit-details-marker]:hidden">
                            <svg viewBox="0 0 24 24" fill="none" class="size-5 group-open:hidden" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                            <svg viewBox="0 0 24 24" fill="none" class="hidden size-5 group-open:block" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
                            Menu
                        </summary>
                        <div class="absolute right-0 top-12 w-[min(20rem,calc(100vw-2rem))] rounded-2xl border border-cream-200 bg-white p-3 shadow-xl">
                            @auth
                                <div class="mb-2 rounded-xl bg-cream-100 px-3 py-2">
                                    <p class="font-bold text-cocoa-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs font-semibold capitalize text-cocoa-500">{{ $roleLabel }} account</p>
                                </div>
                            @endauth
                            <nav class="grid gap-1" aria-label="Mobile navigation">
                                @foreach ($navLinks as $link)
                                    @php
                                        $isActive = request()->routeIs($link['active']);
                                    @endphp
                                    <a href="{{ route($link['route']) }}" @class([
                                        'flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold transition',
                                        'bg-cocoa-700 text-white' => $isActive,
                                        'text-cocoa-700 hover:bg-cream-100' => ! $isActive,
                                    ])>
                                        <span>{{ $link['label'] }}</span>
                                        @if (($link['route'] ?? null) === 'notifications.index' && ($unreadNotificationCount ?? 0))
                                            <span class="rounded-full bg-gold-300 px-2 py-0.5 text-xs text-cocoa-900">{{ $unreadNotificationCount }}</span>
                                        @endif
                                    </a>
                                @endforeach
                                @guest
                                    <a href="{{ route('login') }}" class="mt-1 rounded-xl bg-cocoa-700 px-3 py-2.5 text-center text-sm font-bold text-white">Log in</a>
                                @else
                                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-cream-200 pt-2">
                                        @csrf
                                        <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-cocoa-700 hover:bg-cream-100">Log out</button>
                                    </form>
                                @endguest
                            </nav>
                        </div>
                    </details>
                </div>
            </header>

            <main class="flex-1">
                @if (trim($__env->yieldContent('hide_page_header')) !== 'true')
                    @php
                        $pageTitle = trim($__env->yieldContent('page_title')) ?: 'Casa Paraiso Spa Management System';
                        $pageDescription = trim($__env->yieldContent('page_description')) ?: 'Thoughtful service, restorative care, and simpler spa operations.';
                    @endphp
                    <section class="relative overflow-hidden border-b border-cream-200 bg-cream-50">
                        <div class="absolute -right-16 -top-24 size-72 rounded-full bg-sage-100/70" aria-hidden="true"></div>
                        <div class="absolute right-28 top-10 size-24 rounded-full border border-gold-300/40" aria-hidden="true"></div>
                        <div class="relative mx-auto w-full max-w-7xl px-4 py-9 sm:px-6 sm:py-12 lg:px-8">
                            <x-page-header :title="$pageTitle" :description="$pageDescription" eyebrow="Body and Wellness Spa" />
                        </div>
                    </section>
                @endif

                <section class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
                    @if (session('success'))
                        <x-alert type="success" class="mb-6" title="All set">{{ session('success') }}</x-alert>
                    @endif

                    @if ($errors->any())
                        <x-alert type="error" class="mb-6" title="Please review the form">Correct the highlighted fields and try again.</x-alert>
                    @endif

                    @yield('content')
                </section>
            </main>

            <footer class="border-t border-cream-200 bg-cocoa-900 text-cream-200">
                <div class="mx-auto flex w-full max-w-7xl flex-col gap-5 px-4 py-8 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                    <div>
                        <p class="font-black tracking-[0.12em] text-cream-50">CASA PARAISO</p>
                        <p class="mt-1 text-xs uppercase tracking-[0.16em] text-cream-300">Body and Wellness Spa</p>
                    </div>
                    <p class="max-w-md text-sm leading-6 text-cream-300 md:text-right">Wellness appointment booking and thoughtful spa service management in one calm workspace.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
