<x-app-layout title="Laporan">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Laporan Penggunaan Kendaraan</h1>
        <p class="text-sm text-gray-500 mt-1">Rekap peminjaman, keluhan, keterlambatan, dan realisasi anggaran per pos ({{ $tahun }}).</p>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
        <div>
            <x-input-label for="from" value="Dari" />
            <x-text-input id="from" name="from" type="date" class="block mt-1 w-full" value="{{ $from }}" />
        </div>
        <div>
            <x-input-label for="to" value="Sampai" />
            <x-text-input id="to" name="to" type="date" class="block mt-1 w-full" value="{{ $to }}" />
        </div>
        <div>
            <x-input-label for="bidang" value="Bidang" />
            <select id="bidang" name="bidang" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                <option value="">Semua bidang</option>
                @foreach ($bidangList as $b)
                    <option value="{{ $b->id }}" {{ (string) $bidangId === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold min-h-[48px]">Terapkan</button>
        <a href="{{ route('pengurus.reports.index', array_filter(['from' => $from, 'to' => $to, 'bidang' => $bidangId]) + ['export' => 1, 'with_budget' => 1]) }}"
           class="px-4 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[48px] flex items-center justify-center">⬇ Export CSV</a>
    </form>

    {{-- Kartu Pemeliharaan Kendaraan — dipindah ke Laporan (UAT 04-B10) --}}
    <form method="POST" action="{{ route('pengurus.documents.kartu', $vehicles->first()?->id ?? 0) }}"
          class="bg-white rounded-xl border border-gray-200 p-4 mb-5 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end"
          data-kartu-form>
        @csrf
        <div class="flex-1">
            <x-input-label for="kartu-vehicle" value="Kartu Pemeliharaan Kendaraan (PDF)" />
            <select id="kartu-vehicle" name="vehicle_dummy" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}">{{ $v->name }} — {{ $v->plate_number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="kartu-year" value="Tahun" />
            <input id="kartu-year" name="year" type="number" min="2000" max="2100" value="{{ $tahun }}"
                   class="block mt-1 w-full rounded-lg border-gray-300 text-sm" />
        </div>
        <button type="submit" class="px-5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px] whitespace-nowrap">Generate Kartu Pemeliharaan</button>
    </form>
    {{-- Arahkan form ke mobil terpilih (route memakai {vehicle}) --}}
    <script>
        document.querySelector('[data-kartu-form] select').addEventListener('change', function () {
            document.querySelector('[data-kartu-form]').action =
                '{{ route('pengurus.documents.kartu', ['vehicle' => ':ID:']) }}'.replace(':ID:', this.value);
        });
        document.querySelector('[data-kartu-form]').action =
            '{{ route('pengurus.documents.kartu', ['vehicle' => $vehicles->first()?->id ?? 0]) }}';
    </script>

    {{-- Ringkasan totals --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Peminjaman</p>
            <p class="text-2xl font-semibold mt-1">{{ $totals['peminjaman'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Total Hari Pakai</p>
            <p class="text-2xl font-semibold mt-1">{{ $totals['hariPakai'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Keluhan (rentang)</p>
            <p class="text-2xl font-semibold mt-1">{{ $totals['keluhan'] }}</p>
            <p class="text-xs {{ $totals['keluhanBelum'] > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $totals['keluhanBelum'] }} belum selesai total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Tertutup Otomatis</p>
            <p class="text-2xl font-semibold mt-1">{{ $totals['terlambatOtomatis'] }}</p>
            <p class="text-xs text-gray-400">lewat tempo tanpa tombol Selesai</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs text-gray-500">Dibatalkan Sistem</p>
            <p class="text-2xl font-semibold mt-1">{{ $totals['dibatalkanSistem'] }}</p>
            <p class="text-xs text-gray-400">menunggu pengganti lewat tempo</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Rekap per mobil --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-4">Rekap per Mobil</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b">
                            <th class="py-2">Mobil</th>
                            <th class="py-2 text-center">Peminjaman</th>
                            <th class="py-2 text-right">Hari Pakai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($rekap as $r)
                            <tr>
                                <td class="py-2.5">{{ $r['vehicle']->name }}</td>
                                <td class="py-2.5 text-center">{{ $r['jumlah'] }}</td>
                                <td class="py-2.5 text-right font-medium">{{ $r['hariPakai'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-gray-400">Tidak ada data pada rentang ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Realisasi anggaran per pos --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-1">Realisasi Anggaran per Pos — {{ $tahun }}</h2>
            <p class="text-xs text-gray-400 mb-4">Seluruh unit; nilai realisasi sudah dikalikan koefisien pajak. Rincian per unit: halaman Anggaran kendaraan / Kartu Pemeliharaan.</p>
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
                    @foreach ($anggaran as $post => $a)
                        <tr>
                            <td class="py-2.5">{{ ucfirst(str_replace('_', ' ', $post)) }}</td>
                            <td class="py-2.5 text-right">Rp {{ number_format($a['anggaran'], 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right">Rp {{ number_format($a['realisasi'], 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right font-medium {{ $a['sisa'] < 0 ? 'text-red-600' : 'text-green-700' }}">Rp {{ number_format($a['sisa'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
</x-app-layout>
