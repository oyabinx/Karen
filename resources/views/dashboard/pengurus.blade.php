<x-app-layout title="Dashboard Pengurus">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">Dashboard <span class="font-medium">Pengurus</span> — kendaraan, maintenance, monitoring & anggaran.</p>

    {{-- Notifikasi pajak ≤3 minggu / lewat tempo (UAT 03-A11) --}}
    @if ($data['pajakNotifs']->isNotEmpty())
        @php($adaLewat = $data['pajakNotifs']->firstWhere('lewat', true))
        <div class="mb-5 rounded-xl border-2 {{ $adaLewat ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50' }} p-4">
            <p class="text-sm font-bold {{ $adaLewat ? 'text-red-700' : 'text-amber-800' }} mb-2">⚠ Peringatan Pajak Kendaraan{{ $data['pajakNotifs']->count() > 1 ? ' ('.$data['pajakNotifs']->count().')' : '' }}</p>
            <ul class="space-y-1">
                @foreach ($data['pajakNotifs']->take(4) as $n)
                    <li class="text-sm {{ $n['lewat'] ? 'text-red-700' : 'text-amber-800' }}">
                        <strong>{{ $n['v']->name }}</strong> — {{ $n['jenis'] }} {{ $n['tanggal']->translatedFormat('d M') }}:
                        @if ($n['lewat']) LEWAT {{ $n['hari'] }} hari @else {{ $n['hari'] }} hari lagi @endif
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('pengurus.vehicles.index') }}" class="text-xs font-medium text-gray-600 hover:underline mt-2 inline-block">Lihat semua unit →</a>
        </div>
    @endif

    {{-- Kuota peminjaman pribadi — pengurus juga peminjam (docs kuota_bidang.md) --}}
    <div class="mb-6 rounded-xl border {{ $data['kuota']['remaining'] > 0 ? 'border-indigo-200 bg-indigo-50/50' : 'border-red-200 bg-red-50' }} px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm {{ $data['kuota']['remaining'] > 0 ? 'text-indigo-900' : 'text-red-700' }}">
            <strong>Kuota Peminjaman {{ $data['kuota']['bidang'] }} Anda</strong> — terpakai {{ $data['kuota']['used'] }} dari {{ $data['kuota']['limit'] }} mobil
        </p>
        <a href="{{ route('pegawai.search.index') }}" class="text-sm font-medium {{ $data['kuota']['remaining'] > 0 ? 'text-indigo-700 hover:underline' : 'text-red-700' }}">Cari mobil tersedia →</a>
    </div>

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
        {{-- Armada hari ini: dipakai siapa / bengkel / event (panel bersama semua role) --}}
        @include('dashboard.partials.armada-hari-ini', ['tampilkanLinkSemua' => true])

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
