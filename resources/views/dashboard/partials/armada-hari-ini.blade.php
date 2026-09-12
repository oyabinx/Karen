{{-- Panel bersama semua role — status armada hari ini (docs/feature/dashboard.md).
     Menjawab "mobil X hari ini siapa yang pakai?": dipakai siapa (lengkap
     dengan kontak), di bengkel, atau menjadi armada event. --}}
@php
    $dipakai = $armadaHariIni['dipakai'];
    $bengkel = $armadaHariIni['bengkel'];
    $eventArmada = $armadaHariIni['eventArmada'];
    $kosong = $dipakai->isEmpty() && $bengkel->isEmpty() && $eventArmada->isEmpty();
    $tampilkanLinkSemua = $tampilkanLinkSemua ?? false;
@endphp

<section class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        <h2 class="font-semibold">Armada Hari Ini</h2>
        <div class="flex items-center gap-3">
            @unless ($kosong)
                <p class="text-xs text-gray-400">
                    {{ $dipakai->count() }} dipakai
                    @if ($bengkel->isNotEmpty()) · {{ $bengkel->count() }} di bengkel @endif
                    @if ($eventArmada->isNotEmpty()) · {{ $eventArmada->count() }} armada event @endif
                </p>
            @endunless
            @if ($tampilkanLinkSemua)
                <a href="{{ route('pengurus.bookings.index') }}" class="text-sm text-indigo-600 hover:underline">Semua peminjaman →</a>
            @endif
        </div>
    </div>

    <div class="space-y-2">
        {{-- Sedang dipakai: peminjam + bidang + kontak langsung --}}
        @foreach ($dipakai as $b)
            <div class="rounded-lg border border-gray-100 border-l-4 border-l-indigo-300 bg-gray-50/50 px-3 py-2">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium min-w-0">
                        {{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span>
                    </p>
                    @if ($b->status === \App\Models\Booking::STATUS_MENUNGGU_PENGGANTIAN)
                        <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">menunggu pengganti</span>
                    @endif
                </div>
                <p class="text-xs text-gray-600 mt-0.5">
                    Dipakai <span class="font-medium">{{ $b->user->name }}</span>@if ($b->user->seksi) ({{ $b->user->seksi->bidang->name }})@endif
                    @if ($b->user->phone)
                        · <a href="tel:{{ $b->user->phone }}" class="text-indigo-600 hover:underline">📞 {{ $b->user->phone }}</a>
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-0.5 truncate">s.d. {{ $b->end_date->translatedFormat('d M') }} · {{ $b->address }}</p>
            </div>
        @endforeach

        {{-- Di bengkel (maintenance mencakup hari ini) --}}
        @foreach ($bengkel as $m)
            <div class="rounded-lg border border-gray-100 border-l-4 border-l-amber-300 bg-gray-50/50 px-3 py-2">
                <p class="text-sm font-medium">{{ $m->vehicle->name }} <span class="text-gray-400 font-normal">({{ $m->vehicle->plate_number }})</span></p>
                <p class="text-xs text-gray-500 mt-0.5">🔧 Di bengkel s.d. {{ $m->end_date->translatedFormat('d M') }}@if ($m->note) — {{ $m->note }}@endif</p>
            </div>
        @endforeach

        {{-- Menjadi armada event yang mencakup hari ini --}}
        @foreach ($eventArmada as $row)
            <div class="rounded-lg border border-gray-100 border-l-4 border-l-gray-300 bg-gray-50/50 px-3 py-2">
                <p class="text-sm font-medium">{{ $row->vehicle->name }} <span class="text-gray-400 font-normal">({{ $row->vehicle->plate_number }})</span></p>
                <p class="text-xs text-gray-500 mt-0.5">📅 Armada event &quot;{{ $row->event->name }}&quot; ({{ $row->event->bidang->name }}) s.d. {{ $row->event->end_date->translatedFormat('d M') }}</p>
            </div>
        @endforeach

        @if ($kosong)
            <div class="rounded-lg border border-green-200 bg-green-50/60 px-3 py-3 text-sm text-green-700">
                Tidak ada armada yang dipakai hari ini — semua unit tersedia. ✅
            </div>
        @endif
    </div>
</section>
