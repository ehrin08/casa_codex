@extends('layouts.app')

@section('title', 'Verify Email | Casa Paraiso')
@section('hide_page_header', 'true')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card class="bg-white">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-sage-700">Customer account security</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-cocoa-950">Verify your email address</h1>
            <div class="mt-4 space-y-3 text-sm leading-6 text-cocoa-600">
                <p>We sent a verification link to your email.</p>
                <p>Please verify your email before booking an appointment.</p>
                <p>Check your inbox for the verification link.</p>
            </div>

            @if (session('status') === 'verification-link-sent')
                <x-alert type="success" class="mt-6" title="Verification email sent">
                    A new verification link has been sent to your email address.
                </x-alert>
            @endif

            @if ($errors->has('verification'))
                <x-alert type="error" class="mt-6" title="Verification link unavailable">
                    {{ $errors->first('verification') }}
                </x-alert>
            @endif

            @if (auth()->user()?->hasVerifiedEmail())
                <x-alert type="success" class="mt-6" title="Email already verified">
                    Your email is already verified. You can continue to your dashboard.
                </x-alert>
            @endif

            <div class="mt-8 rounded-2xl border border-cream-200 bg-cream-50 p-5">
                <p class="text-sm font-semibold text-cocoa-900">Didn&rsquo;t receive the email?</p>
                <p class="mt-1 text-sm leading-6 text-cocoa-600">Send a new verification link.</p>

                <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                    @csrf
                    <x-button type="submit">Resend verification email</x-button>
                </form>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:items-center">
                @if (auth()->user()?->hasVerifiedEmail())
                    <x-button :href="route(auth()->user()->dashboardRouteName())" variant="secondary">Back to dashboard</x-button>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="login">
                    <x-button type="submit" variant="ghost">Back to login</x-button>
                </form>
            </div>
        </x-card>
    </div>
@endsection
