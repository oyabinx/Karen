<x-app-layout title="Data Kendaraan">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Data Kendaraan</h1>
            <p class="text-sm text-gray-500">Kelola armada: status peminjaman, kondisi unit, dan foto.</p>
        </div>
        <a href="{{ route('pengurus.vehicles.create') }}" class="inline-flex items-center px-5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px]">+ Tambah Kendaraan</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Notifikasi pajak ≤3 minggu / lewat tempo (UAT 03-A11) --}}
    @if ($pajakNotifs->isNotEmpty())
        <div class="mb-5 rounded-xl border-2 {{ $pajakNotifs->firstWhere('lewat', true) ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50' }} p-4">
            <p class="text-sm font-bold {{ $pajakNotifs->firstWhere('lewat', true) ? 'text-red-700' : 'text-amber-800' }} mb-2">⚠ Peringatan Pajak Kendaraan</p>
            <ul class="space-y-1">
                @foreach ($pajakNotifs as $n)
                    <li class="text-sm {{ $n['lewat'] ? 'text-red-700' : 'text-amber-800' }}">
                        <strong>{{ $n['v']->name }}</strong> ({{ $n['v']->plate_number }}) — {{ $n['jenis'] }}:
                        {{ $n['tanggal']->translatedFormat('d M Y') }} ·
                        @if ($n['lewat'])
                            <span class="font-semibold">LEWAT {{ $n['hari'] }} hari</span>
                        @else
                            {{ $n['hari'] }} hari lagi
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tab filter --}}
    <div class="flex flex-wrap gap-2 mb-5 text-sm">
        @foreach (['semua' => 'Semua', 'tersedia' => 'Tersedia', 'tidak_bisa_dipinjam' => 'Tidak Bisa Dipinjam', 'perlu_diperiksa' => 'Perlu Diperiksa'] as $key => $label)
            <a href="{{ route('pengurus.vehicles.index', ['tab' => $key]) }}"
               class="px-3.5 py-2 rounded-full font-medium min-h-[44px] flex items-center {{ $tab === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Grid kartu (desktop 3-4 kolom, mobile 1-2) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse ($vehicles as $v)
            {{-- x-data di KONTAINER KARTU: tombol Detail & modal satu lingkup
                 (perbaikan: sebelumnya tombol di luar scope → klik tidak bereaksi) --}}
            <div x-data="{ detail{{ $v->id }}: false }" @keydown.escape.window="detail{{ $v->id }} = false"
                 class="bg-white rounded-xl border border-gray-200 overflow-hidden flex flex-col {{ $v->trashed() ? 'opacity-60' : '' }}">
                <div class="aspect-video bg-gray-100 flex items-center justify-center">
                    @if ($v->photo_path)
                        <img src="{{ Storage::url($v->photo_path) }}" alt="{{ $v->name }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8l2 5H6l2-5zM4 12h16v5h-2a2 2 0 11-4 0h-4a2 2 0 11-4 0H4v-5z"/></svg>
                    @endif
                </div>

                <div class="p-4 flex-1 flex flex-col">
                    <p class="font-semibold">{{ $v->name }}</p>
                    <p class="text-sm text-gray-500">{{ $v->plate_number }} · {{ $v->year }} · {{ $v->capacity }} kursi</p>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $v->status === 'bisa_dipinjam' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600' }}">
                            {{ $v->status === 'bisa_dipinjam' ? 'Bisa dipinjam' : 'Tidak bisa dipinjam' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $v->condition === 'baik' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $v->condition === 'baik' ? 'Kondisi baik' : 'Perlu diperiksa' }}
                        </span>
                        @if ($v->trashed())
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-500">Nonaktif</span>
                        @endif
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        {{-- Detail kendaraan: data sekunder (UAT 03-A11) --}}
                        <button @click="detail{{ $v->id }} = true" class="text-gray-700 hover:underline font-medium">Detail</button>
                        <a href="{{ route('pengurus.vehicles.edit', $v) }}" class="text-indigo-600 hover:underline">Ubah</a>
                        <a href="{{ route('pengurus.budgets.edit', $v) }}" class="text-indigo-600 hover:underline">Anggaran</a>
                        @if (! $v->trashed())
                            <form method="POST" action="{{ route('pengurus.vehicles.status', $v) }}">
                                @csrf @method('PATCH')
                                <button class="text-gray-600 hover:underline">{{ $v->status === 'bisa_dipinjam' ? 'Blokir peminjaman' : 'Izinkan dipinjam' }}</button>
                            </form>
                        @endif
                        @if ($v->condition === 'perlu_diperiksa')
                            <form method="POST" action="{{ route('pengurus.vehicles.condition', $v) }}">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:underline">Set kondisi baik</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('pengurus.vehicles.needsInspection', $v) }}" onsubmit="return confirm('Tandai unit perlu diperiksa? Unit tidak bisa dipinjam sampai dikembalikan ke baik.')">
                                @csrf @method('PATCH')
                                <button class="text-amber-600 hover:underline">Tandai perlu diperiksa</button>
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Modal Detail Kendaraan — dalam lingkup x-data kartu --}}
                <div>
                    <div x-show="detail{{ $v->id }}" x-cloak @click="detail{{ $v->id }} = false" class="fixed inset-0 z-50 bg-black/50" x-transition.opacity></div>
                    <div x-show="detail{{ $v->id }}" x-cloak x-transition
                         class="fixed inset-x-4 top-1/2 -translate-y-1/2 sm:inset-x-0 sm:mx-auto sm:max-w-md z-50 bg-white rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-lg font-semibold">Detail Kendaraan</h3>
                                <p class="text-sm text-gray-500">{{ $v->name }} · {{ $v->plate_number }}</p>
                            </div>
                            <button @click="detail{{ $v->id }} = false" class="p-2 min-h-[44px] min-w-[44px] text-gray-400 hover:text-gray-600" aria-label="Tutup">✕</button>
                        </div>
                        <dl class="text-sm divide-y">
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Nama / Unit</dt><dd class="font-medium">{{ $v->name }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Plat Nomor</dt><dd class="font-medium">{{ $v->plate_number }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Tahun Pembuatan</dt><dd class="font-medium">{{ $v->year }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Kapasitas</dt><dd class="font-medium">{{ $v->capacity }} kursi</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Nomor Rangka</dt><dd class="font-medium">{{ $v->nomor_rangka ?? '—' }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Nomor Mesin</dt><dd class="font-medium">{{ $v->nomor_mesin ?? '—' }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Pajak Tahunan</dt><dd class="font-medium {{ $v->pajakWarnings() ? 'text-red-600' : '' }}">{{ $v->pajak_tahunan?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                            <div class="flex justify-between py-2"><dt class="text-gray-500">Pajak 5 Tahunan</dt><dd class="font-medium {{ $v->pajakWarnings() ? 'text-red-600' : '' }}">{{ $v->pajak_lima_tahunan?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Belum ada kendaraan pada kategori ini.</div>
        @endforelse
    </div>
</x-app-layout>
