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
        @auth
            @php
                $user = auth()->user();
                $roleLabel = $user->role?->display_name ?: ucfirst($user->role?->name ?? 'Member');
                $unreadNotificationCount = $user->systemNotifications()->where('is_read', false)->count();

                if ($user->isManagement()) {
                    $navigationSections = [
                        [
                            'title' => 'Workspace',
                            'links' => [
                                ['label' => 'Dashboard', 'route' => 'management.index', 'active' => 'management.index', 'icon' => 'dashboard'],
                            ],
                        ],
                        [
                            'title' => 'Daily Operations',
                            'links' => [
                                ['label' => 'Book Walk-in', 'route' => 'management.walk-ins.create', 'active' => 'management.walk-ins.*', 'icon' => 'booking'],
                                ['label' => 'Appointments', 'route' => 'management.appointments.index', 'active' => 'management.appointments.*', 'icon' => 'appointments'],
                                ['label' => 'Therapist Workload', 'route' => 'management.availability.index', 'active' => 'management.availability.*', 'icon' => 'availability'],
                            ],
                        ],
                        [
                            'title' => 'Payments & Reports',
                            'links' => [
                                ['label' => 'Transactions', 'route' => 'management.transactions.index', 'active' => 'management.transactions.*', 'icon' => 'transactions'],
                                ['label' => 'Commissions', 'route' => 'management.commissions.index', 'active' => 'management.commissions.*', 'icon' => 'commissions'],
                                ['label' => 'Reports', 'route' => 'management.reports.index', 'active' => 'management.reports.*', 'icon' => 'future'],
                            ],
                        ],
                        [
                            'title' => 'Insights',
                            'links' => [
                                ['label' => 'Analytics', 'route' => 'management.analytics.index', 'active' => 'management.analytics.*', 'icon' => 'future'],
                                ['label' => 'RFM Scores', 'route' => 'management.rfm.index', 'active' => 'management.rfm.*', 'icon' => 'future'],
                                ['label' => 'Reviews & Sentiment', 'route' => 'management.reviews.index', 'active' => 'management.reviews.*', 'icon' => 'future'],
                            ],
                        ],
                        [
                            'title' => 'Promotions',
                            'links' => [
                                ['label' => 'Promotions', 'route' => 'management.promotions.index', 'active' => 'management.promotions.*', 'icon' => 'future'],
                            ],
                        ],
                        [
                            'title' => 'Records',
                            'links' => [
                                ['label' => 'Services', 'route' => 'management.services.index', 'active' => 'management.services.*', 'icon' => 'services'],
                                ['label' => 'Therapists', 'route' => 'management.therapists.index', 'active' => 'management.therapists.*', 'icon' => 'people'],
                                ['label' => 'Customers', 'route' => 'management.customers.index', 'active' => 'management.customers.*', 'icon' => 'people'],
                            ],
                        ],
                        [
                            'title' => 'Updates',
                            'links' => [
                                ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => 'notifications.*', 'icon' => 'notifications'],
                            ],
                        ],
                    ];
                } elseif ($user->isTherapist()) {
                    $navigationSections = [
                        [
                            'title' => 'Workspace',
                            'links' => [
                                ['label' => 'Dashboard', 'route' => 'therapist.index', 'active' => 'therapist.index', 'icon' => 'dashboard'],
                                ['label' => 'My Schedule', 'route' => 'therapist.schedule.index', 'active' => ['therapist.schedule.*', 'therapist.appointments.*'], 'icon' => 'appointments'],
                            ],
                        ],
                        [
                            'title' => 'Earnings',
                            'links' => [
                                ['label' => 'My Commissions', 'route' => 'therapist.commissions.index', 'active' => 'therapist.commissions.*', 'icon' => 'commissions'],
                            ],
                        ],
                        [
                            'title' => 'Updates',
                            'links' => [
                                ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => 'notifications.*', 'icon' => 'notifications'],
                            ],
                        ],
                    ];
                } else {
                    $navigationSections = [
                        [
                            'title' => 'My Wellness',
                            'links' => [
                                ['label' => 'Dashboard', 'route' => 'customer.index', 'active' => 'customer.index', 'icon' => 'dashboard'],
                                ['label' => 'Book Appointment', 'route' => 'customer.appointments.create', 'active' => 'customer.appointments.create', 'icon' => 'booking'],
                                ['label' => 'My Appointments', 'route' => 'customer.appointments.index', 'active' => ['customer.appointments.index', 'customer.appointments.show'], 'icon' => 'appointments'],
                            ],
                        ],
                        [
                            'title' => 'Updates',
                            'links' => [
                                ['label' => 'Notifications', 'route' => 'notifications.index', 'active' => 'notifications.*', 'icon' => 'notifications'],
                                ['label' => 'Promotions', 'icon' => 'future', 'disabled' => true],
                            ],
                        ],
                    ];
                }
            @endphp
        @endauth

        <div @class(['min-h-screen', 'lg:pl-72' => auth()->check()])>
            @auth
                <x-sidebar :sections="$navigationSections" :user="$user" :role-label="$roleLabel" :unread-count="$unreadNotificationCount" />
                <x-mobile-sidebar :sections="$navigationSections" :user="$user" :role-label="$roleLabel" :unread-count="$unreadNotificationCount" />
            @else
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
                        <x-button :href="route('login')">Log in</x-button>
                    </div>
                </header>
            @endauth

            <div class="flex min-h-screen flex-col">
                <main class="min-w-0 flex-1">
                    @if (trim($__env->yieldContent('hide_page_header')) !== 'true')
                        @php
                            $pageTitle = trim($__env->yieldContent('page_title')) ?: 'Casa Paraiso Spa Management System';
                            $pageDescription = trim($__env->yieldContent('page_description')) ?: 'Thoughtful service, restorative care, and simpler spa operations.';
                        @endphp
                        <section class="relative overflow-hidden border-b border-cream-200 bg-cream-50">
                            <div class="absolute -right-16 -top-24 size-72 rounded-full bg-sage-100/70" aria-hidden="true"></div>
                            <div class="absolute right-28 top-10 size-24 rounded-full border border-gold-300/40" aria-hidden="true"></div>
                            <div @class([
                                'relative mx-auto w-full px-4 py-8 sm:px-6 sm:py-10 xl:px-10',
                                'max-w-7xl' => auth()->guest(),
                                'max-w-[100rem]' => auth()->check(),
                            ])>
                                <x-page-header :title="$pageTitle" :description="$pageDescription" eyebrow="Body and Wellness Spa" />
                            </div>
                        </section>
                    @endif

                    <section @class([
                        'mx-auto w-full min-w-0 px-4 py-8 sm:px-6 sm:py-10 xl:px-10 xl:py-12',
                        'max-w-7xl' => auth()->guest(),
                        'max-w-[100rem]' => auth()->check(),
                    ])>
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
                    <div @class([
                        'mx-auto flex w-full flex-col gap-5 px-4 py-7 sm:px-6 md:flex-row md:items-center md:justify-between xl:px-10',
                        'max-w-7xl' => auth()->guest(),
                        'max-w-[100rem]' => auth()->check(),
                    ])>
                        <div>
                            <p class="font-black tracking-[0.12em] text-cream-50">CASA PARAISO</p>
                            <p class="mt-1 text-xs uppercase tracking-[0.16em] text-cream-300">Body and Wellness Spa</p>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-cream-300 md:text-right">Wellness appointment booking and thoughtful spa service management in one calm workspace.</p>
                    </div>
                </footer>
            </div>
        </div>
    </body>
</html>
