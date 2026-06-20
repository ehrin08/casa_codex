@props(['sections', 'user', 'roleLabel', 'unreadCount' => 0])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<aside class="fixed inset-y-0 left-0 z-40 hidden w-72 flex-col overflow-hidden bg-cocoa-950 text-cream-50 shadow-[16px_0_50px_-36px_rgba(33,23,19,0.8)] lg:flex" aria-label="Account navigation">
    <div class="border-b border-white/10 px-6 py-6">
        <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-xl" aria-label="Casa Paraiso home">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-sage-600 text-cream-50 shadow-sm transition group-hover:bg-sage-500" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none" class="size-7" stroke="currentColor" stroke-width="1.7"><path d="M25.5 5.5C17 6 10.8 10.8 10.8 19.2M6.5 26c2.1-7.9 8.1-12.8 18.1-15.2" stroke-linecap="round"/><path d="M25.5 5.5c.6 8.7-3.2 14-10.1 14.4-2.8.2-4.7-.9-4.7-.9s-1.3-2-.9-4.4C10.7 8.4 17.1 5.2 25.5 5.5Z" stroke-linejoin="round"/></svg>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-base font-black tracking-[0.12em]">CASA PARAISO</span>
                <span class="block text-[0.61rem] font-semibold uppercase tracking-[0.15em] text-sage-200">Body and Wellness Spa</span>
            </span>
        </a>
        <span class="mt-4 inline-flex rounded-full border border-gold-300/20 bg-gold-300/10 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-[0.14em] text-gold-300">{{ $roleLabel }}</span>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5" aria-label="Primary navigation">
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
            </div>
        </div>
        <div class="mt-2 flex items-center justify-between gap-3 px-2">
            <span class="flex items-center gap-2 text-xs font-semibold text-cream-300"><span class="size-1.5 rounded-full bg-sage-200"></span>{{ $roleLabel }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-lg px-2 py-1.5 text-xs font-bold text-cream-200 transition hover:bg-white/10 hover:text-white">Log out</button>
            </form>
        </div>
    </div>
</aside>
