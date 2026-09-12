<x-app-layout title="Dokumen">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Dokumen</h1>
        <p class="text-sm text-gray-500 mt-1">Hasil generate: draft nota (otomatis per nota), bend26 bulanan per pos (dari Realisasi Bulanan), kartu pemeliharaan (dari Laporan).</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-4 rounded-lg bg-amber-50 border border-amber-300 px-4 py-3 text-sm text-amber-800">{{ session('warning') }}</div>
    @endif

    {{-- Filter kendaraan --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
        <div class="flex-1">
            <x-input-label for="vehicle" value="Filter kendaraan" />
            <select id="vehicle" name="vehicle" class="block mt-1 w-full rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                <option value="">Semua kendaraan</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" {{ $vehicle?->id === $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- Daftar dokumen --}}
    <div class="space-y-3">
        @forelse ($documents as $d)
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $d->type === 'bend26' ? 'bg-indigo-100 text-indigo-700' : ($d->type === 'kartu_pemeliharaan' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                            @if ($d->type === 'bend26')
                                BEND26{{ $d->post ? ' — '.ucfirst(str_replace('_', ' ', $d->post)) : '' }}
                            @elseif ($d->type === 'kartu_pemeliharaan')
                                Kartu Pemeliharaan
                            @else
                                Draft Nota — {{ ucfirst(str_replace('_', ' ', $d->post)) }}
                            @endif
                        </span>
                        @if ($d->period)
                            <span class="text-xs text-gray-500">· {{ \Illuminate\Support\Carbon::parse($d->period.'-01')->translatedFormat('F Y') }}</span>
                        @endif
                        {{ $d->vehicle?->name ?? '—' }}
                        @if ($d->maintenance) <span class="text-gray-400">· maintenance #{{ $d->maintenance->id }} {{ $d->maintenance->workshop_name ? '('.$d->maintenance->workshop_name.')' : '' }}</span> @endif
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        digenerate {{ $d->created_at->translatedFormat('d M Y H:i') }}
                        @if ($d->regenerated_at)
                            · <span class="text-amber-600 font-medium">Diperbarui {{ $d->regenerated_at->translatedFormat('d M Y H:i') }}</span>
                        @endif
                    </p>
                </div>
                <a href="{{ route('pengurus.documents.download', $d) }}" class="px-4 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 min-h-[40px] flex items-center">⬇ Unduh PDF</a>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Belum ada dokumen. Input nota pada maintenance yang selesai (draft nota otomatis), generate bend26 bulanan dari Realisasi Bulanan, atau kartu pemeliharaan dari Laporan.
            </div>
        @endforelse
    </div>
</x-app-layout>
