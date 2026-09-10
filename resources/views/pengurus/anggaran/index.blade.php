@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<x-app-layout title="Anggaran — {{ $year }}">
    <div class="mb-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Anggaran Maintenance</h1>
                <p class="text-sm text-gray-500 mt-1">Seluruh armada · tahun {{ $year }} · koefisien ×{{ number_format($koefisien, 2, ',', '.') }}</p>
            </div>
            {{-- Pemilih tahun --}}
            <div class="flex items-center gap-1 bg-white rounded-lg border border-gray-300 p-1">
                <a href="?year={{ $year - 1 }}" class="px-3 py-1.5 rounded-md hover:bg-gray-100 text-gray-600 min-h-[44px] flex items-center">◀</a>
                <span class="px-3 font-semibold">{{ $year }}</span>
                <a href="?year={{ $year + 1 }}" class="px-3 py-1.5 rounded-md hover:bg-gray-100 text-gray-600 min-h-[44px] flex items-center">▶</a>
            </div>
        </div>
    </div>

    {{-- Ringkasan total per pos (4 kartu horizontal scroll di mobile) --}}
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6 sm:grid sm:grid-cols-4 sm:overflow-visible">
        @foreach ($totals as $post => $t)
            <div class="bg-white rounded-xl border {{ $t['sisa'] < 0 ? 'border-red-300' : 'border-gray-200' }} p-4 min-w-[160px] sm:min-w-0 flex-shrink-0">
                <p class="text-xs text-gray-500 font-medium">{{ ucfirst(str_replace('_', ' ', $post)) }}</p>
                <p class="text-xl font-bold mt-1 {{ $t['sisa'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $rp($t['sisa']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">dari {{ $rp($t['anggaran']) }}</p>
                <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                    @php($pct = $t['anggaran'] > 0 ? min(100, ($t['realisasi'] / $t['anggaran']) * 100) : 0)
                    <div class="h-full rounded-full {{ $t['sisa'] < 0 ? 'bg-red-500' : ($pct > 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- DESKTOP: tabel semua kendaraan --}}
    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Kendaraan</th>
                    @foreach (\App\Models\VehicleBudget::POSTS as $post)
                        <th class="px-4 py-3 text-center">{{ ucfirst(str_replace('_', ' ', $post)) }}<br><span class="font-normal normal-case">sisa / anggaran</span></th>
                    @endforeach
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($summaries as $vId => $data)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $data['vehicle']->name }}</p>
                            <p class="text-xs text-gray-400">{{ $data['vehicle']->plate_number }} · {{ $data['vehicle']->year }}</p>
                        </td>
                        @foreach (\App\Models\VehicleBudget::POSTS as $post)
                            @php($s = $data['summary'][$post])
                            <td class="px-4 py-3 text-center">
                                <span class="{{ $s['sisa'] < 0 ? 'text-red-600 font-bold' : 'text-gray-700' }}">{{ $rp($s['sisa']) }}</span>
                                <span class="text-xs text-gray-400"> / {{ $rp($s['anggaran']) }}</span>
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('pengurus.budgets.edit', $data['vehicle']) }}" class="text-indigo-600 hover:underline text-sm">Detail →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada kendaraan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MOBILE: kartu per kendaraan --}}
    <div class="md:hidden space-y-3">
        @forelse ($summaries as $vId => $data)
            <a href="{{ route('pengurus.budgets.edit', $data['vehicle']) }}"
               class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $data['vehicle']->name }}</p>
                        <p class="text-xs text-gray-400">{{ $data['vehicle']->plate_number }}</p>
                    </div>
                    <span class="text-indigo-500 text-sm">→</span>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach (\App\Models\VehicleBudget::POSTS as $post)
                        @php($s = $data['summary'][$post])
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $s['sisa'] < 0 ? 'bg-red-100 text-red-700' : ($s['anggaran'] > 0 ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400') }}">
                            {{ ucfirst(str_replace('_', ' ', $post)) }}: {{ $rp($s['sisa']) }}
                        </span>
                    @endforeach
                </div>
            </a>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Belum ada kendaraan.</div>
        @endforelse
    </div>
</x-app-layout>
