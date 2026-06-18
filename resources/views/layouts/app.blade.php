<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title', 'Casa Paraiso Spa Management System')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
        <div class="flex min-h-screen flex-col">
            <header class="border-b border-zinc-200 bg-white">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 px-4 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                    <a href="{{ route('home') }}" class="text-lg font-semibold text-zinc-950">
                        Casa Paraiso Spa Management System
                    </a>

                    <nav class="flex flex-wrap items-center gap-2 text-sm font-medium">
                        <a
                            href="{{ route('home') }}"
                            class="rounded-md px-3 py-2 transition {{ request()->routeIs('home') ? 'bg-emerald-700 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' }}"
                        >
                            Home
                        </a>

                        @guest
                            <a
                                href="{{ route('login') }}"
                                class="rounded-md px-3 py-2 transition {{ request()->routeIs('login') ? 'bg-emerald-700 text-white' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' }}"
                            >
                                Log in
                            </a>
                        @endguest

                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="rounded-md px-3 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950"
                            >
                                My area
                            </a>
                            <span class="px-2 text-zinc-500">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-md px-3 py-2 text-zinc-600 transition hover:bg-zinc-100 hover:text-zinc-950">
                                    Log out
                                </button>
                            </form>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="flex-1">
                <section class="border-b border-zinc-200 bg-white">
                    <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Casa Paraiso - Body and Wellness Spa</p>
                        <h1 class="mt-3 max-w-3xl text-3xl font-semibold text-zinc-950 sm:text-4xl">
                            @yield('page_title', 'Casa Paraiso Spa Management System')
                        </h1>
                        <p class="mt-4 max-w-3xl text-base leading-7 text-zinc-600">
                            @yield('page_description', 'Base UI foundation for Casa Paraiso - Body and Wellness Spa.')
                        </p>
                    </div>
                </section>

                <section class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                    @yield('content')
                </section>
            </main>

            <footer class="border-t border-zinc-200 bg-white">
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-6 text-sm text-zinc-500 sm:px-6 md:flex-row md:items-center md:justify-between lg:px-8">
                    <span>Casa Paraiso Spa Management System</span>
                    <span>Spa service management and appointment booking</span>
                </div>
            </footer>
        </div>
    </body>
</html>
