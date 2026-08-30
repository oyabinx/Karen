<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Karen') }} — {{ $title ?? 'Dashboard' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-100" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
        @php($menu = \App\Support\KarenMenu::forUser(auth()->user()))

        <div class="min-h-screen lg:flex">
            {{-- SIDEBAR DESKTOP (≥lg) — docs/feature/ui_responsive.md --}}
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-e border-gray-200">
                <div class="flex items-center gap-2 h-16 px-6 border-b border-gray-200">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold">K</div>
                    <span class="font-semibold text-lg">Karen</span>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
                    @foreach ($menu as $section => $items)
                        <div>
                            <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $section }}</p>
                            <div class="space-y-1">
                                @foreach ($items as $item)
                                    <a href="{{ $item['url'] }}"
                                       class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                                        <span class="w-5 h-5 shrink-0">{!! $item['icon'] !!}</span>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="border-t border-gray-200 p-4">
                    <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ auth()->user()->role }}</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mt-3 w-full text-sm text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg px-3 py-2 min-h-[44px]">Keluar</button>
                    </form>
                </div>
            </aside>

            {{-- AREA KONTEN --}}
            <div class="flex-1 lg:ms-64 flex flex-col min-h-screen">
                {{-- TOPBAR MOBILE (<lg): hamburger --}}
                <header class="lg:hidden sticky top-0 z-40 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between h-14 px-4">
                        <button @click="drawer = true" class="p-2 -ms-2 min-h-[44px] min-w-[44px] text-gray-600" aria-label="Buka menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold text-sm">K</div>
                            <span class="font-semibold">Karen</span>
                        </div>
                        <div class="w-10"></div>
                    </div>
                </header>

                {{-- DRAWER MOBILE --}}
                <div x-show="drawer" x-cloak @click="drawer = false" class="lg:hidden fixed inset-0 z-50 bg-black/40" x-transition.opacity></div>
                <aside x-show="drawer" x-cloak x-transition class="lg:hidden fixed inset-y-0 start-0 z-50 w-72 max-w-[85%] bg-white shadow-xl overflow-y-auto">
                    <div class="flex items-center justify-between h-14 px-4 border-b border-gray-200">
                        <span class="font-semibold">Menu</span>
                        <button @click="drawer = false" class="p-2 min-h-[44px] min-w-[44px] text-gray-500" aria-label="Tutup menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <nav class="px-3 py-4 space-y-6">
                        @foreach ($menu as $section => $items)
                            <div>
                                <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $section }}</p>
                                <div class="space-y-1">
                                    @foreach ($items as $item)
                                        <a href="{{ $item['url'] }}" @click="drawer = false"
                                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs($item['active']) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                                            <span class="w-5 h-5 shrink-0">{!! $item['icon'] !!}</span>
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </nav>
                    <div class="border-t border-gray-200 p-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-sm text-gray-600 hover:text-red-600 rounded-lg px-3 py-2.5 min-h-[44px]">Keluar ({{ auth()->user()->name }})</button>
                        </form>
                    </div>
                </aside>

                <main class="flex-1 p-4 sm:p-6 lg:p-8 pb-28 lg:pb-8">
                    @if (session('status'))
                        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                            {{ __('Tersimpan.') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- BOTTOM NAVIGATION MOBILE — aksi utama (docs/feature/ui_responsive.md) --}}
        <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200 grid grid-cols-3">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center py-2 min-h-[56px] text-xs {{ request()->routeIs('dashboard') ? 'text-indigo-600 font-semibold' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/></svg>
                Beranda
            </a>
            {{-- Slot "Cari Mobil" & "Peminjaman Aktif" untuk pegawai/pengurus ditambahkan di Fase 5 --}}
            <a href="{{ route('profile.edit') }}" class="flex flex-col items-center justify-center py-2 min-h-[56px] text-xs {{ request()->routeIs('profile.edit') ? 'text-indigo-600 font-semibold' : 'text-gray-500' }}">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profil
            </a>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-karen').requestSubmit();" class="flex flex-col items-center justify-center py-2 min-h-[56px] text-xs text-gray-500">
                <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Keluar
            </a>
        </nav>

        {{-- Form logout global (dipakai bottom-nav) --}}
        <form id="logout-karen" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>
    </body>
</html>
