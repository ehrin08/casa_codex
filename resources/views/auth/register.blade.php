@extends('layouts.app')

@section('title', 'Create Customer Account | Casa Paraiso')
@section('hide_page_header', 'true')

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-cream-200 bg-white shadow-[0_30px_70px_-40px_rgba(48,33,28,0.7)] lg:grid-cols-[0.85fr_1.15fr]">
        <aside class="relative hidden overflow-hidden bg-cocoa-900 p-10 text-cream-50 lg:flex lg:flex-col lg:justify-between">
            <div class="absolute -right-20 -top-20 size-64 rounded-full border-[32px] border-sage-600/30" aria-hidden="true"></div>
            <div class="relative">
                <div class="flex size-12 items-center justify-center rounded-full bg-sage-700 text-cream-50">
                    <svg viewBox="0 0 32 32" fill="none" class="size-8" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg>
                </div>
                <p class="mt-8 text-xs font-bold uppercase tracking-[0.2em] text-sage-200">Customer booking access</p>
                <h1 class="mt-3 text-3xl font-black tracking-[0.1em]">CASA PARAISO</h1>
                <p class="mt-5 text-base leading-7 text-cream-200">Use this account to book appointments and review your visits.</p>
            </div>
            <p class="relative text-sm italic leading-6 text-cream-300">&ldquo;Your next restorative visit starts with a clear, simple account.&rdquo;</p>
        </aside>

        <div class="p-6 sm:p-10 lg:p-12">
            <div class="mx-auto max-w-md">
                <div class="lg:hidden">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-sage-700">Casa Paraiso</p>
                </div>
                <h2 class="mt-2 text-3xl font-semibold tracking-tight text-cocoa-950">Create your customer account</h2>
                <p class="mt-2 text-sm leading-6 text-cocoa-500">Register as a customer to book appointments and keep your Casa Paraiso visits organized.</p>

                <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                    @csrf

                    <x-form.input name="name" label="Full name" :value="old('name')" required autofocus autocomplete="name" />
                    <x-form.input name="email" label="Email address" type="email" :value="old('email')" required autocomplete="email" />
                    <x-form.input name="phone" label="Phone number" :value="old('phone')" autocomplete="tel" hint="Optional. Add a contact number if you would like booking updates by phone." />
                    <x-form.input name="password" label="Password" type="password" required autocomplete="new-password" />
                    <x-form.input name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />

                    <x-button type="submit" class="mt-2 w-full">Create customer account</x-button>
                </form>

                <p class="mt-8 border-t border-cream-200 pt-5 text-center text-sm leading-6 text-cocoa-500">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-sage-700 transition hover:text-sage-800">Log in</a>
                </p>
            </div>
        </div>
    </div>
@endsection
