<x-app-layout title="Cari Mobil Tersedia">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Cari Mobil Tersedia</h1>
        <p class="text-sm text-gray-500 mt-1">Maksimal {{ \App\Services\BookingService::maxDurasiHari() }} hari termasuk Sabtu–Minggu · peminjaman hari penuh (00:00–24:00).</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Kartu kuota bidang (docs/feature/kuota_bidang.md) --}}
    <div class="mb-4 rounded-xl border {{ $quota['remaining'] > 0 ? 'border-indigo-200 bg-indigo-50/50' : 'border-red-200 bg-red-50' }} px-4 py-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm {{ $quota['remaining'] > 0 ? 'text-indigo-900' : 'text-red-700' }}">
            <strong>Kuota {{ $quota['bidang'] }}</strong> — terpakai {{ $quota['used'] }} dari {{ $quota['limit'] }} mobil
        </p>
        <span class="text-sm font-semibold {{ $quota['remaining'] > 0 ? 'text-indigo-700' : 'text-red-700' }}">
            Sisa: {{ $quota['remaining'] }} mobil
        </span>
    </div>

    @if ($hasActive)
        <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
            Anda masih memiliki peminjaman aktif — selesaikan/kembalikan dahulu sebelum meminjam lagi.
        </div>
    @endif

    {{-- Form pencarian (sticky di mobile — docs/feature/ui_responsive.md) --}}
    <form method="GET" class="sticky top-0 z-30 bg-gray-100 py-3 -mx-4 px-4 sm:mx-0 sm:px-0 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
        <div class="flex-1">
            <x-input-label for="start_date" value="Tanggal mulai" />
            <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full" value="{{ $start }}" min="{{ today()->toDateString() }}" />
        </div>
        <div class="flex-1">
            <x-input-label for="end_date" value="Tanggal selesai" />
            <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" value="{{ $end }}" min="{{ today()->toDateString() }}" />
        </div>
        <x-primary-button class="justify-center min-h-[44px]">Cari</x-primary-button>
    </form>

    @foreach ($rangeErrors as $error)
        <div class="mt-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $error }}</div>
    @endforeach

    @if ($validRange)
        <p class="text-sm text-gray-500 mt-4 mb-3">{{ $vehicles->count() }} mobil tersedia pada {{ \Illuminate\Support\Carbon::parse($start)->translatedFormat('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($end)->translatedFormat('d M Y') }}:</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($vehicles as $v)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center">
                        @if ($v->photo_path)
                            <img src="{{ Storage::url($v->photo_path) }}" alt="{{ $v->name }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8l2 5H6l2-5zM4 12h16v5h-2a2 2 0 11-4 0h-4a2 2 0 11-4 0H4v-5z"/></svg>
                        @endif
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <p class="font-semibold">{{ $v->name }}</p>
                        <p class="text-sm text-gray-500">{{ $v->plate_number }} · {{ $v->year }} · {{ $v->capacity }} kursi</p>

                        <div class="mt-3 pt-3 border-t border-gray-100 mt-auto">
                            @if ($hasActive || $quota['remaining'] < 1)
                                <span class="block text-center px-4 py-2 rounded-lg bg-gray-100 text-gray-400 text-sm font-medium cursor-not-allowed">
                                    {{ $quota['remaining'] < 1 ? 'Kuota bidang penuh' : 'Masih ada peminjaman aktif' }}
                                </span>
                            @else
                                <a href="{{ route('pegawai.bookings.create', ['vehicle_id' => $v->id, 'start_date' => $start, 'end_date' => $end]) }}"
                                   class="block text-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px] leading-[44px]">
                                    Pinjam Mobil Ini
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                    Tidak ada mobil tersedia pada rentang tanggal ini. Coba tanggal lain.
                </div>
            @endforelse
        </div>
    @endif

    {{-- Mobil sedang maintenance — tampil NONAKTIF dengan keterangan (UAT 03-A12) --}}
    @if ($validRange && $maintenanceBlocked->isNotEmpty())
        <p class="text-sm text-gray-500 mt-8 mb-3">Sedang Maintenance — tidak dapat dipinjam pada rentang ini:</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($maintenanceBlocked as $v)
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col opacity-60">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center">
                        @if ($v->photo_path)
                            <img src="{{ Storage::url($v->photo_path) }}" alt="{{ $v->name }}" class="w-full h-full object-cover grayscale">
                        @else
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8l2 5H6l2-5zM4 12h16v5h-2a2 2 0 11-4 0h-4a2 2 0 11-4 0H4v-5z"/></svg>
                        @endif
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <p class="font-semibold text-gray-500">{{ $v->name }}</p>
                        <p class="text-sm text-gray-400">{{ $v->plate_number }} · {{ $v->capacity }} kursi</p>
                        <span class="mt-2 px-2.5 py-1 rounded-full bg-gray-200 text-gray-600 text-xs font-semibold w-fit">
                            🔧 Sedang Maintenance: {{ $v->blocking_start->translatedFormat('d M') }} – {{ $v->blocking_end->translatedFormat('d M Y') }}
                        </span>
                        <div class="mt-3 pt-3 border-t border-gray-100 mt-auto">
                            <span class="block text-center px-4 py-2 rounded-lg bg-gray-100 text-gray-400 text-sm font-medium cursor-not-allowed">Tidak dapat dipilih</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-app-layout>
