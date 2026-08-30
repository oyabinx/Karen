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

    {{-- Kandidat pengganti --}}
    <h2 class="font-semibold mb-3">Mobil pengganti yang tersedia ({{ $candidates->count() }})</h2>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @forelse ($candidates as $v)
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-col">
                <p class="font-semibold">{{ $v->name }}</p>
                <p class="text-sm text-gray-500">{{ $v->plate_number }} · {{ $v->year }} · {{ $v->capacity }} kursi</p>

                <form method="POST" action="{{ route('pengurus.replacements.assign', $booking) }}" class="mt-3 pt-3 border-t border-gray-100 mt-auto">
                    @csrf @method('PATCH')
                    <input type="hidden" name="vehicle_id" value="{{ $v->id }}">
                    <x-primary-button class="w-full justify-center">Jadikan Pengganti</x-primary-button>
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
