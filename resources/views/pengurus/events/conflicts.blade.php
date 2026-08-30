<x-app-layout title="Konflik Event — {{ $event->name }}">
    <div class="mb-6">
        <a href="{{ route('pengurus.events.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke daftar event</a>
        <h1 class="text-2xl font-semibold mt-2">Konflik & Pengganti — {{ $event->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $event->bidang->name }} · {{ $event->start_date->translatedFormat('d M Y') }} — {{ $event->end_date->translatedFormat('d M Y') }} ·
            {{ $event->vehicles->count() }} armada: {{ $event->vehicles->pluck('name')->implode(', ') }}
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if ($bookings->isEmpty())
        <section class="bg-green-50 rounded-xl border border-green-200 p-6 mb-6">
            <p class="font-semibold text-green-700 mb-3">✓ Semua konflik telah terselesaikan.</p>
            <form method="POST" action="{{ route('pengurus.events.confirm', $event) }}">
                @csrf @method('PATCH')
                <button class="px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium hover:bg-green-700 min-h-[44px]">Konfirmasi Event</button>
            </form>
        </section>
    @else
        <p class="text-sm text-gray-500 mb-4">
            Pilih mobil pengganti untuk setiap peminjaman yang ditabrak — kandidat otomatis mengecualikan seluruh armada event ini. Bila tidak ada yang cocok, batalkan peminjamannya.
        </p>
    @endif

    <div class="space-y-4">
        @foreach ($bookings as $b)
            <section class="bg-white rounded-xl border-2 border-amber-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif ·
                            {{ $b->start_date->translatedFormat('d M') }} — {{ $b->end_date->translatedFormat('d M Y') }}
                        </p>
                        <p class="text-sm text-gray-400">{{ $b->address }} — {{ $b->purpose }}</p>
                    </div>
                    <form method="POST" action="{{ route('pengurus.events.conflicts.cancel', [$event, $b]) }}" onsubmit="return confirm('Batalkan peminjaman ini tanpa pengganti?')">
                        @csrf @method('PATCH')
                        <button class="px-3 py-1.5 rounded-lg border border-red-300 text-red-600 text-sm font-medium hover:bg-red-50 min-h-[44px]">Batalkan Peminjaman</button>
                    </form>
                </div>

                @if ($b->candidates->isNotEmpty())
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Kandidat pengganti ({{ $b->candidates->count() }})</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($b->candidates as $v)
                            <form method="POST" action="{{ route('pengurus.events.conflicts.assign', [$event, $b]) }}" class="rounded-xl border border-gray-200 p-3 flex items-center justify-between gap-3">
                                @csrf @method('PATCH')
                                <input type="hidden" name="vehicle_id" value="{{ $v->id }}">
                                <div class="min-w-0">
                                    <p class="font-medium text-sm truncate">{{ $v->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $v->plate_number }} · {{ $v->capacity }} kursi</p>
                                </div>
                                <button class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 min-h-[44px] shrink-0">Pilih</button>
                            </form>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">
                        Tidak ada kandidat pengganti untuk rentang tanggal ini — batalkan peminjaman, atau ubah pilihan armada event.
                    </p>
                @endif
            </section>
        @endforeach
    </div>
</x-app-layout>
