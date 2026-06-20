@props(['sections', 'user', 'roleLabel', 'unreadCount' => 0])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<header class="sticky top-0 z-40 flex min-h-16 items-center justify-between gap-4 border-b border-cream-200 bg-cream-50/95 px-4 py-2.5 shadow-sm backdrop-blur lg:hidden">
    <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 rounded-lg" aria-label="Casa Paraiso home">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-sage-700 text-white" aria-hidden="true">
            <svg viewBox="0 0 32 32" fill="none" class="size-6" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg>
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-black tracking-[0.1em] text-cocoa-900">CASA PARAISO</span>
            <span class="block truncate text-[0.58rem] font-bold uppercase tracking-[0.13em] text-sage-700">{{ $roleLabel }} workspace</span>
        </span>
    </a>
    <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-cream-300 bg-white text-cocoa-800 shadow-sm transition hover:bg-cream-100" data-mobile-sidebar-open aria-controls="mobile-account-navigation" aria-haspopup="dialog" aria-label="Open navigation menu">
        <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
    </button>
</header>

<dialog id="mobile-account-navigation" class="fixed inset-0 z-50 m-0 h-dvh max-h-none w-full max-w-none bg-transparent p-0 backdrop:bg-cocoa-950/60 lg:hidden">
    <div class="ml-auto flex h-full w-[min(22rem,calc(100%-2rem))] flex-col bg-cocoa-950 text-cream-50 shadow-2xl">
        <div class="flex items-center justify-between gap-4 border-b border-white/10 px-5 py-5">
            <div>
                <p class="text-sm font-black tracking-[0.11em]">CASA PARAISO</p>
                <p class="mt-1 text-[0.62rem] font-bold uppercase tracking-[0.15em] text-sage-200">Body and Wellness Spa</p>
            </div>
            <button type="button" class="flex size-10 items-center justify-center rounded-xl bg-white/10 text-cream-100 transition hover:bg-white/15" data-mobile-sidebar-close aria-label="Close navigation menu">
                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" stroke-linecap="round"/></svg>
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" aria-label="Mobile navigation">
            @foreach ($sections as $section)
                <x-sidebar-section :title="$section['title']">
                    @foreach ($section['links'] as $link)
                        <x-sidebar-link
                            :href="isset($link['route']) ? route($link['route']) : null"
                            :active="isset($link['active']) && request()->routeIs($link['active'])"
                            :icon="$link['icon']"
                            :disabled="$link['disabled'] ?? false"
                            :badge="($link['route'] ?? null) === 'notifications.index' ? $unreadCount : null"
                        >{{ $link['label'] }}</x-sidebar-link>
                    @endforeach
                </x-sidebar-section>
            @endforeach
        </nav>

        <div class="border-t border-white/10 bg-cocoa-900/55 p-4">
            <div class="flex items-center gap-3 rounded-xl bg-white/5 p-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-sage-600 text-xs font-black tracking-wide text-white">{{ $initials ?: 'CP' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-white">{{ $user->name }}</p>
                    <p class="truncate text-xs text-cream-300">{{ $user->email }}</p>
                    <p class="mt-0.5 text-[0.65rem] font-semibold text-sage-200">{{ $roleLabel }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-cream-200 transition hover:bg-white/10 hover:text-white">Log out</button>
            </form>
        </div>
    </div>
</dialog>
