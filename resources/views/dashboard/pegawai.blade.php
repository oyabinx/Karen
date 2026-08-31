<x-app-layout title="Dashboard Pegawai">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">
        Dashboard <span class="font-medium">Pegawai</span>
        @if ($user->seksi)
            — {{ $user->seksi->name }}, {{ $user->seksi->bidang->name }}
        @endif
    </p>

    @php($quota = app(\App\Services\BookingService::class)->quotaInfo($user))
    @php($activeBooking = $user->bookings()->whereIn('status', ['dipinjam', 'menunggu_penggantian'])->latest('start_date')->first())

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="rounded-xl border {{ $quota['remaining'] > 0 ? 'border-indigo-200 bg-indigo-50/50' : 'border-red-200 bg-red-50' }} p-5">
            <p class="text-sm {{ $quota['remaining'] > 0 ? 'text-indigo-900' : 'text-red-700' }}">Kuota Peminjaman {{ $quota['bidang'] }}</p>
            <p class="text-3xl font-semibold mt-1 {{ $quota['remaining'] > 0 ? 'text-indigo-700' : 'text-red-700' }}">{{ $quota['remaining'] }} <span class="text-base font-normal text-gray-500">dari {{ $quota['limit'] }} mobil tersisa</span></p>
            <a href="{{ route('pegawai.search.index') }}" class="inline-block mt-2 text-sm font-medium {{ $quota['remaining'] > 0 ? 'text-indigo-700 hover:underline' : 'text-red-700' }}">Cari mobil tersedia →</a>
        </div>
        @if ($activeBooking)
            <div class="rounded-xl border border-indigo-200 bg-white p-5">
                <p class="text-sm text-gray-500">Peminjaman Aktif</p>
                <p class="text-xl font-semibold mt-1">{{ $activeBooking->vehicle->name }}</p>
                <p class="text-sm text-gray-500">{{ $activeBooking->start_date->translatedFormat('d M') }} — {{ $activeBooking->end_date->translatedFormat('d M Y') }} · {{ $activeBooking->address }}</p>
            </div>
        @endif
    </div>

    @if ($activeBooking)
        <div class="bg-white rounded-xl border-2 border-emerald-200 p-6 mb-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-1">Peminjaman Aktif</p>
                    <p class="text-lg font-semibold">{{ $activeBooking->vehicle->name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $activeBooking->start_date->translatedFormat('d M Y') }} — {{ $activeBooking->end_date->translatedFormat('d M Y') }}
                        @if ($activeBooking->status === 'menunggu_penggantian')
                            · <span class="text-amber-600 font-medium">mobil sedang diproses penggantian</span>
                        @endif
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Tujuan: {{ $activeBooking->address }}</p>
                </div>
                @if ($activeBooking->status === 'dipinjam')
                    @include('partials.finish-booking-modal', ['booking' => $activeBooking])
                @else
                    <span class="px-4 py-2 rounded-lg bg-amber-50 text-amber-700 text-sm font-medium">Menunggu mobil pengganti…</span>
                @endif
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
