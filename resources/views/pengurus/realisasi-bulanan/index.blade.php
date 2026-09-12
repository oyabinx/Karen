@php($rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.'))
<x-app-layout title="Realisasi Bulanan">
    <div class="mb-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                @if ($selectedVehicle)
                    <a href="{{ route('pengurus.realisasi-bulanan.index', ['bulan' => $bulan]) }}" class="text-sm text-gray-500 hover:text-gray-700">← Semua mobil</a>
                    <h1 class="text-2xl font-semibold mt-1">{{ $selectedVehicle['vehicle']->name }}</h1>
                    <p class="text-sm text-gray-500">{{ $selectedVehicle['vehicle']->plate_number }} · {{ \Illuminate\Support\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</p>
                @else
                    <h1 class="text-2xl font-semibold">Realisasi Bulanan</h1>
                    <p class="text-sm text-gray-500 mt-1">Pilih mobil untuk melihat rincian pemeliharaan</p>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <form method="GET" class="flex items-center gap-2">
                    @if ($vehicleId)<input type="hidden" name="vehicle" value="{{ $vehicleId }}">@endif
                    <select name="bulan" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm min-h-[44px]">
                        @foreach ($bulanPilihan as $nilai => $label)
                            <option value="{{ $nilai }}" {{ $bulan === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="?bulan={{ $bulan }}@if($vehicleId)&vehicle={{ $vehicleId }}@endif&export=1"
                   class="px-5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[48px] flex items-center">⬇ CSV</a>
                {{-- Generate bend26 BULANAN semua pos bernilai (UAT 04-B6) --}}
                @if ($perVehicle->isNotEmpty())
                    <form method="POST" action="{{ route('pengurus.documents.bend26') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $bulan }}">
                        <button class="px-5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px] whitespace-nowrap">Generate Bend26 {{ \Illuminate\Support\Carbon::parse($bulan.'-01')->translatedFormat('M Y') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Ringkasan 4 pos (horizontal scroll mobile) --}}
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6 sm:grid sm:grid-cols-4 sm:overflow-visible">
        @foreach ($ringkasan as $post => $r)
            <div class="bg-white rounded-xl border {{ $r['sisa'] < 0 ? 'border-red-300' : 'border-gray-200' }} p-4 min-w-[160px] sm:min-w-0 flex-shrink-0">
                <p class="text-xs text-gray-500 font-medium">{{ ucfirst(str_replace('_', ' ', $post)) }}</p>
                <p class="text-lg font-bold mt-1 {{ $r['sisa'] < 0 ? 'text-red-600' : 'text-gray-800' }}">{{ $rp($r['realisasi']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">dari {{ $rp($r['anggaran']) }}</p>
            </div>
        @endforeach
    </div>

    @if ($selectedVehicle)
        {{-- ═══ LEVEL 2: DETAIL PER POS untuk mobil terpilih ═══ --}}
        <div class="space-y-4">
            @foreach (\App\Models\VehicleBudget::POSTS as $post)
                @php($postData = $selectedVehicle['perPost'][$post])
                @if ($postData['raw'] > 0)
                    <section class="bg-white rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <h3 class="font-semibold">{{ ucfirst(str_replace('_', ' ', $post)) }}</h3>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="font-bold text-emerald-700">{{ $rp($postData['taxed']) }}</p>
                                    <p class="text-xs text-gray-400">{{ $rp($postData['raw']) }} (sebelum pajak)</p>
                                </div>
                                {{-- Generate bend26 bulanan POS INI saja (UAT 04-B6) --}}
                                <form method="POST" action="{{ route('pengurus.documents.bend26') }}">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $bulan }}">
                                    <input type="hidden" name="post" value="{{ $post }}">
                                    <button class="px-3 py-1.5 rounded-lg border border-indigo-300 text-indigo-700 text-xs font-medium hover:bg-indigo-50 min-h-[36px] whitespace-nowrap">Bend26 pos ini</button>
                                </form>
                            </div>
                        </div>

                        <div class="space-y-2">
                            @foreach ($postData['costs'] as $c)
                                @php($m = $c->maintenance)
                                <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                                    <div class="flex flex-wrap justify-between gap-2 text-sm">
                                        <div>
                                            <p class="font-medium">{{ $m->workshop_name ?? '—' }}</p>
                                            <p class="text-xs text-gray-400">{{ optional($m->nota_date ?? $m->start_date)->translatedFormat('d M Y') }}@if ($m->nota_number) · {{ $m->nota_number }}@endif</p>
                                        </div>
                                        <p class="font-semibold">{{ $rp($c->raw_amount) }}</p>
                                    </div>
                                    @if ($c->details->isNotEmpty())
                                        <details class="mt-2">
                                            <summary class="cursor-pointer text-xs text-indigo-600 font-medium">📋 {{ $c->details->count() }} rincian</summary>
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
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>
    @else
        {{-- ═══ LEVEL 1: LIST MOBIL yang maintenance bulan ini ═══ --}}
        <div class="space-y-3">
            @forelse ($perVehicle as $v)
                <a href="?bulan={{ $bulan }}&vehicle={{ $v['vehicle']->id }}"
                   class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 transition-colors">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $v['vehicle']->name }}</p>
                            <p class="text-xs text-gray-400">{{ $v['vehicle']->plate_number }} · {{ $v['maintenanceCount'] }} maintenance</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-bold text-emerald-700">{{ $rp($v['totalTaxed']) }}</p>
                            <p class="text-xs text-gray-400">total (termasuk pajak)</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach (\App\Models\VehicleBudget::POSTS as $post)
                            @if ($v['perPost'][$post]['raw'] > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                    {{ ucfirst(str_replace('_', ' ', $post)) }}: {{ $rp($v['perPost'][$post]['taxed']) }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                    Tidak ada mobil yang maintenance pada bulan ini.
                </div>
            @endforelse
        </div>
    @endif
</x-app-layout>
