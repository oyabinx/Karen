<x-app-layout title="Dashboard Pengurus">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">Dashboard <span class="font-medium">Pengurus</span> — kendaraan, maintenance, monitoring & anggaran.</p>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Armada</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['cards']['armada'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Siap Dipinjam</p>
            <p class="text-3xl font-semibold mt-1 text-green-700">{{ $data['cards']['bisaDipinjam'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Maintenance Aktif</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['cards']['maintenanceAktif'] }}</p>
        </div>
        <div class="bg-white rounded-xl border {{ $data['cards']['menungguPengganti'] > 0 ? 'border-amber-300' : 'border-gray-200' }} p-5">
            <p class="text-sm text-gray-500">Menunggu Pengganti</p>
            <p class="text-3xl font-semibold mt-1 {{ $data['cards']['menungguPengganti'] > 0 ? 'text-amber-600' : '' }}">{{ $data['cards']['menungguPengganti'] }}</p>
            @if ($data['cards']['menungguPengganti'] > 0)
                <a href="{{ route('pengurus.replacements.index') }}" class="text-xs text-amber-700 hover:underline">atur pengganti →</a>
            @endif
        </div>
        <div class="bg-white rounded-xl border {{ $data['cards']['keluhanBelum'] > 0 ? 'border-amber-300' : 'border-gray-200' }} p-5">
            <p class="text-sm text-gray-500">Keluhan Belum Selesai</p>
            <p class="text-3xl font-semibold mt-1 {{ $data['cards']['keluhanBelum'] > 0 ? 'text-amber-600' : '' }}">{{ $data['cards']['keluhanBelum'] }}</p>
            <a href="{{ route('pengurus.complaints.index') }}" class="text-xs text-indigo-600 hover:underline">lihat keluhan →</a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Peminjaman berjalan hari ini --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Peminjaman Berjalan Hari Ini</h2>
                <a href="{{ route('pengurus.bookings.index') }}" class="text-sm text-indigo-600 hover:underline">Semua peminjaman →</a>
            </div>

            <div class="space-y-2">
                @forelse ($data['peminjamanHariIni'] as $b)
                    <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50/50 px-3 py-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ $b->vehicle->name }} <span class="text-gray-400">· {{ $b->user->name }}@if ($b->user->seksi) ({{ $b->user->seksi->bidang->name }})@endif</span></p>
                            <p class="text-xs text-gray-500 truncate">s.d. {{ $b->end_date->translatedFormat('d M') }} · {{ $b->address }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Tidak ada peminjaman berjalan hari ini.</p>
                @endforelse
            </div>
        </section>

        <div class="space-y-6">
            {{-- Ringkasan anggaran per pos --}}
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="font-semibold mb-1">Anggaran Maintenance {{ $data['tahunAnggaran'] }} (semua unit)</h2>
                <p class="text-xs text-gray-400 mb-3">Realisasi = nilai nota × 1,13. Rincian per unit ada di halaman Anggaran tiap kendaraan.</p>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b">
                            <th class="py-2">Pos</th>
                            <th class="py-2 text-right">Anggaran</th>
                            <th class="py-2 text-right">Realisasi</th>
                            <th class="py-2 text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($data['anggaran'] as $post => $a)
                            @php($sisa = $a['anggaran'] - $a['realisasi'])
                            <tr>
                                <td class="py-2">{{ ucfirst(str_replace('_', ' ', $post)) }}</td>
                                <td class="py-2 text-right">Rp {{ number_format($a['anggaran'], 0, ',', '.') }}</td>
                                <td class="py-2 text-right">Rp {{ number_format($a['realisasi'], 0, ',', '.') }}</td>
                                <td class="py-2 text-right font-medium {{ $sisa < 0 ? 'text-red-600' : 'text-green-700' }}">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            {{-- Event berjalan/mendatang --}}
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold">Event Armada Terjadwal</h2>
                    <a href="{{ route('pengurus.events.index') }}" class="text-sm text-indigo-600 hover:underline">Semua event →</a>
                </div>
                <div class="space-y-2">
                    @forelse ($data['eventBerjalan'] as $e)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate">{{ $e->name }} <span class="text-gray-400">· {{ $e->bidang->name }}</span></span>
                            <span class="text-xs text-gray-500 shrink-0">{{ $e->start_date->translatedFormat('d M') }} · {{ $e->armada_count }} unit</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Tidak ada event terjadwal.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
