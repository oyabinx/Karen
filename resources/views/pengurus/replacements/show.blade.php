<x-app-layout title="Pilih Mobil Pengganti">
    <div class="mb-6">
        <a href="{{ route('pengurus.replacements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke daftar</a>
        <h1 class="text-2xl font-semibold mt-2">Pilih Mobil Pengganti</h1>
        <p class="text-sm text-gray-500 mt-1">Rentang tanggal peminjaman tidak berubah — hanya unit mobil yang diganti.</p>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Detail booking terdampak --}}
    <section class="bg-white rounded-xl border-2 border-amber-200 p-5 mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-amber-600 mb-2">Peminjaman terdampak maintenance</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div><span class="text-gray-500">Peminjam:</span> <strong>{{ $booking->user->name }}</strong>@if ($booking->user->seksi) — {{ $booking->user->seksi->name }}, {{ $booking->user->seksi->bidang->name }}@endif</div>
            <div><span class="text-gray-500">Mobil saat ini:</span> <strong>{{ $booking->vehicle->name }}</strong> ({{ $booking->vehicle->plate_number }})</div>
            <div><span class="text-gray-500">Tanggal:</span> {{ $booking->start_date->translatedFormat('d M Y') }} — {{ $booking->end_date->translatedFormat('d M Y') }}</div>
            <div><span class="text-gray-500">Tujuan:</span> {{ $booking->address }} — {{ $booking->purpose }}</div>
        </div>
    </section>

    {{-- ★ PENGGANTIAN PARSIAL — DIREKOMENDASIKAN, ditampilkan PERTAMA (UAT 03-B7b) --}}
    @if ($partial)
        <section class="bg-white rounded-xl border-2 border-sky-300 p-5 mb-5">
            <div class="flex items-start gap-2 mb-1">
                <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 text-xs font-bold shrink-0 mt-0.5">★ DIREKOMENDASIKAN</span>
            </div>
            <h2 class="font-semibold text-sky-800 mb-1">Pengganti sebagian — hanya tanggal yang menabrak</h2>
            <p class="text-sm text-sky-700 mb-4">
                {{ $partial['os']->translatedFormat('d M Y') }} s.d. {{ $partial['oe']->translatedFormat('d M Y') }} pakai mobil pengganti —
                <strong>sisa tanggal tetap {{ $booking->vehicle->name }}</strong> (peminjaman dipecah otomatis).
            </p>

            @if ($partialCandidates->isEmpty())
                <p class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-3">Tidak ada kandidat untuk rentang parsial ini — gunakan penggantian penuh di bawah.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($partialCandidates as $v)
                        <form method="POST" action="{{ route('pengurus.replacements.assignPartial', $booking) }}"
                              class="rounded-xl border-2 border-sky-300 bg-sky-50 p-3 flex items-center justify-between gap-3">
                            @csrf @method('PATCH')
                            <input type="hidden" name="vehicle_id" value="{{ $v->id }}">
                            <div class="min-w-0">
                                <p class="font-medium text-sm truncate">{{ $v->name }}</p>
                                <p class="text-xs text-gray-500">{{ $v->plate_number }} · {{ $v->capacity }} kursi</p>
                            </div>
                            <button class="px-3 py-1.5 rounded-lg bg-sky-600 text-white text-xs font-semibold hover:bg-sky-700 min-h-[44px] shrink-0">
                                Pakai {{ $partial['os']->translatedFormat('d M') }}–{{ $partial['oe']->translatedFormat('d M') }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    {{-- Penggantian PENUH --}}
    <h2 class="font-semibold mb-3 {{ $partial ? 'text-gray-500' : '' }}">{{ $partial ? 'Atau penggantian penuh (seluruh rentang)' : 'Mobil pengganti yang tersedia' }} ({{ $candidates->count() }})</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($candidates as $v)
            <div class="bg-white rounded-xl border {{ $partial ? 'border-gray-200' : 'border-gray-200' }} p-4 flex flex-col">
                <p class="font-semibold">{{ $v->name }}</p>
                <p class="text-sm text-gray-500">{{ $v->plate_number }} · {{ $v->year }} · {{ $v->capacity }} kursi</p>

                <form method="POST" action="{{ route('pengurus.replacements.assign', $booking) }}" class="mt-3 pt-3 border-t border-gray-100 mt-auto">
                    @csrf @method('PATCH')
                    <input type="hidden" name="vehicle_id" value="{{ $v->id }}">
                    <button class="w-full justify-center px-4 py-2 rounded-lg border border-indigo-300 text-indigo-700 text-sm font-medium hover:bg-indigo-50 min-h-[44px]">
                        {{ $partial ? 'Ganti Seluruh Rentang' : 'Jadikan Pengganti' }}
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Tidak ada mobil pengganti yang tersedia pada rentang tanggal ini.
            </div>
        @endforelse
    </div>

    {{-- Batalkan bila tidak ada pengganti --}}
    <section class="bg-red-50 rounded-xl border border-red-200 p-5">
        <p class="font-semibold text-red-700 mb-1">Tidak ada pengganti yang cocok?</p>
        <p class="text-sm text-red-600 mb-3">Batalkan peminjaman ini — jatah kuota bidang peminjam akan dilepas.</p>
        <form method="POST" action="{{ route('pengurus.replacements.cancel', $booking) }}" onsubmit="return confirm('Batalkan peminjaman ini secara permanen?')">
            @csrf @method('PATCH')
            <button class="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium hover:bg-red-700 min-h-[44px]">Batalkan Peminjaman</button>
        </form>
    </section>
</x-app-layout>
