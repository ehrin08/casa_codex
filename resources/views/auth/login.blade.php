@extends('layouts.app')

@section('title', 'Log In | Casa Paraiso')
@section('hide_page_header', 'true')

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-cream-200 bg-white shadow-[0_30px_70px_-40px_rgba(48,33,28,0.7)] lg:grid-cols-[0.85fr_1.15fr]">
        <aside class="relative hidden overflow-hidden bg-cocoa-900 p-10 text-cream-50 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-20 -top-20 size-64 rounded-full border-[32px] border-sage-600/30" aria-hidden="true"></div>
            <div class="relative">
                <div class="flex size-12 items-center justify-center rounded-full bg-sage-700 text-cream-50">
                    <svg viewBox="0 0 32 32" fill="none" class="size-8" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg>
                </div>
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-sage-200">Body and Wellness Spa</p>
                <h1 class="mt-3 text-3xl font-black tracking-[0.1em]">CASA PARAISO</h1>
                <p class="mt-5 text-base leading-7 text-cream-200">Step into your dedicated space for caring service, considered schedules, and restorative moments.</p>
            </div>
            <p class="relative text-sm italic leading-6 text-cream-300">&ldquo;Wellness begins with a moment made just for you.&rdquo;</p>
        </aside>

        <div class="p-6 sm:p-10 lg:p-12">
            <div class="mx-auto max-w-md">
                <div class="lg:hidden">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-sage-700">Casa Paraiso</p>
                </div>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-cocoa-950">Welcome back</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">Sign in to continue to your Casa Paraiso workspace.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <x-form.input name="email" label="Email address" type="email" :value="old('email')" required autofocus autocomplete="email" />
                    <x-form.input name="password" label="Password" type="password" required autocomplete="current-password" />

                    <x-button type="submit" class="mt-2 w-full">Log in securely</x-button>
                </form>

                <p class="mt-8 border-t border-cream-200 pt-5 text-center text-xs leading-5 text-cocoa-500">Access is limited to authorized management, therapist, and customer accounts.</p>
            </div>
        </div>
    </div>
@endsection
