<x-app-layout title="Dashboard Pegawai">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">
        Dashboard <span class="font-medium">Pegawai</span>
        @if ($user->seksi)
            — {{ $user->seksi->name }}, {{ $user->seksi->bidang->name }}
        @endif
    </p>

    @php($activeBooking = $user->bookings()->whereIn('status', ['dipinjam', 'menunggu_penggantian'])->latest('start_date')->first())

    @if ($activeBooking)
        <div class="bg-white rounded-xl border-2 border-indigo-200 p-6 mb-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 mb-1">Peminjaman Aktif</p>
                    <p class="text-lg font-semibold">{{ $activeBooking->vehicle->name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $activeBooking->start_date->translatedFormat('d M Y') }} — {{ $activeBooking->end_date->translatedFormat('d M Y') }}
                        @if ($activeBooking->status === 'menunggu_penggantian')
                            · <span class="text-amber-600 font-medium">mobil sedang diproses penggantian</span>
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Tujuan: {{ $activeBooking->address }}</p>
                </div>
                {{-- Tombol "Kembalikan" diaktifkan di Fase 6 (alur keluhan) --}}
                <span class="px-4 py-2 rounded-lg bg-gray-100 text-gray-400 text-sm font-medium">Tombol "Kembalikan" — Fase 6</span>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold mb-3">Peminjaman Kendaraan</h2>
        <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
            <li>Cari mobil tersedia pada rentang tanggal lalu booking dengan alamat & keperluan — <span class="text-gray-400">segera hadir (Fase 5)</span></li>
            <li>Kembalikan mobil sendiri lewat aplikasi (dengan form keluhan opsional) — <span class="text-gray-400">segera hadir (Fase 6)</span></li>
            <li>Maksimal 3 hari termasuk Sabtu–Minggu; maksimal sesuai kuota bidang Anda.</li>
        </ul>
    </div>
</x-app-layout>
