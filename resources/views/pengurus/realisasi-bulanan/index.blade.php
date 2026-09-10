@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<x-app-layout title="Realisasi Bulanan">
    <div class="mb-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Realisasi Bulanan</h1>
                <p class="text-sm text-gray-500 mt-1">Semua nota & rincian per bulan · koefisien ×{{ number_format($koefisien, 2, ',', '.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    <select name="bulan" onchange="this.form.submit()"
                            class="rounded-lg border-gray-300 text-sm min-h-[44px]">
                        @foreach ($bulanPilihan as $nilai => $label)
                            <option value="{{ $nilai }}" {{ $bulan === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="?bulan={{ $bulan }}&export=1"
                   class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 min-h-[44px] flex items-center">⬇ CSV</a>
            </div>
        </div>
    </div>

    {{-- Ringkasan 4 pos (horizontal scroll mobile) --}}
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6 sm:grid sm:grid-cols-4 sm:overflow-visible">
        @foreach ($ringkasan as $post => $r)
            <div class="bg-white rounded-xl border {{ $r['sisa'] < 0 ? 'border-red-300' : 'border-gray-200' }} p-4 min-w-[160px] sm:min-w-0 flex-shrink-0">
                <p class="text-xs text-gray-500 font-medium">{{ ucfirst(str_replace('_', ' ', $post)) }}</p>
                <p class="text-lg font-bold mt-1 {{ $r['sisa'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $rp($r['realisasi']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">dari {{ $rp($r['anggaran']) }} · sisa {{ $rp($r['sisa']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- DESKTOP: tabel detail dengan expandable rincian --}}
    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Mobil</th>
                    <th class="px-4 py-3">Bengkel / Nota</th>
                    <th class="px-4 py-3">Pos</th>
                    <th class="px-4 py-3">Rincian</th>
                    <th class="px-4 py-3 text-right">Nilai</th>
                    <th class="px-4 py-3 text-right">× Koef.</th>
                    <th class="px-4 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($costs as $c)
                    @php($m = $c->maintenance)
                    <tr>
                        <td class="px-4 py-3 text-nowrap">{{ optional($m->nota_date ?? $m->start_date)->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $m->vehicle->name }}</p>
                            <p class="text-xs text-gray-400">{{ $m->vehicle->plate_number }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ $m->workshop_name ?? '—' }}</p>
                            @if ($m->nota_number)<p class="text-xs text-gray-400">{{ $m->nota_number }}</p>@endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">{{ ucfirst(str_replace('_', ' ', $c->post)) }}</span>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            @if ($c->details->isNotEmpty())
                                <details>
                                    <summary class="cursor-pointer text-indigo-600 hover:underline text-xs">{{ $c->details->count() }} rincian</summary>
                                    <ul class="mt-1 space-y-0.5">
                                        @foreach ($c->details as $d)
                                            <li class="text-xs text-gray-600">
                                                • {{ $d->description }}
                                                @if ($d->amount > 0)<span class="text-gray-400">({{ $rp($d->amount) }})</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $rp($c->raw_amount) }}</td>
                        <td class="px-4 py-3 text-right text-xs text-gray-400">{{ number_format((float)($c->koefisien_used ?? $koefisien), 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $rp($c->taxed_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Tidak ada realisasi pada bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- MOBILE: kartu per nota dengan expandable rincian --}}
    <div class="md:hidden space-y-3">
        @forelse ($costs as $c)
            @php($m = $c->maintenance)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-sm truncate">{{ $m->vehicle->name }}</p>
                        <p class="text-xs text-gray-400">{{ $m->vehicle->plate_number }} · {{ optional($m->nota_date ?? $m->start_date)->translatedFormat('d M Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $m->workshop_name ?? '—' }}@if ($m->nota_number) · {{ $m->nota_number }}@endif</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">{{ ucfirst(str_replace('_', ' ', $c->post)) }}</span>
                        <p class="font-bold text-emerald-700 mt-1">{{ $rp($c->taxed_amount) }}</p>
                        <p class="text-xs text-gray-400">{{ $rp($c->raw_amount) }} × {{ number_format((float)($c->koefisien_used ?? $koefisien), 2, ',', '.') }}</p>
                    </div>
                </div>

                @if ($c->details->isNotEmpty())
                    <details class="mt-2 pt-2 border-t border-gray-100">
                        <summary class="cursor-pointer text-xs text-indigo-600 font-medium">📋 {{ $c->details->count() }} rincian — klik untuk lihat</summary>
                        <ul class="mt-1 space-y-1">
                            @foreach ($c->details as $d)
                                <li class="text-xs text-gray-600 flex justify-between">
                                    <span>• {{ $d->description }}</span>
                                    @if ($d->amount > 0)<span class="text-gray-400">{{ $rp($d->amount) }}</span>@endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada realisasi pada bulan ini.</div>
        @endforelse
    </div>
</x-app-layout>
