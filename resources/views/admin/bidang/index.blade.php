<x-app-layout title="Bidang & Seksi">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Bidang & Seksi</h1>
        <p class="text-sm text-gray-500 mt-1">Struktur organisasi kantor — 5 bidang beserta seksi dan kuota peminjaman armada.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="space-y-4">
        @foreach ($bidangList as $b)
            {{-- Default COLLAPSE semua (UAT C1) — daftar ringkas 5 bidang tanpa scroll panjang --}}
            <details class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <summary class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 cursor-pointer select-none hover:bg-gray-50">
                    <div class="flex items-center gap-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                        <span class="font-semibold">{{ $b->name }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium">Kuota: {{ $b->max_active_bookings }} mobil</span>
                        <span class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">{{ $b->seksi_count }} seksi</span>
                    </div>
                </summary>

                <div class="border-t border-gray-100 px-5 py-4">
                    {{-- Ubah bidang + kuota --}}
                    <form method="POST" action="{{ route('admin.bidang.update', $b) }}" class="flex flex-col sm:flex-row gap-2 mb-5">
                        @csrf @method('PUT')
                        <input type="text" name="name" value="{{ $b->name }}" required class="rounded-lg border-gray-300 text-sm flex-1 min-h-[44px]">
                        <label class="flex items-center gap-2 text-sm text-gray-500">
                            Kuota
                            <input type="number" name="max_active_bookings" value="{{ $b->max_active_bookings }}" min="1" max="9" required class="rounded-lg border-gray-300 w-20 min-h-[44px]">
                        </label>
                        <button class="px-4 rounded-lg bg-gray-800 text-white text-sm font-medium min-h-[44px]">Simpan</button>
                    </form>

                    {{-- Daftar seksi --}}
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Seksi ({{ $b->seksi_count }})</p>
                    <div class="space-y-2 mb-4">
                        @forelse ($b->seksi as $s)
                            <div class="flex flex-col sm:flex-row sm:items-center gap-2 rounded-lg border border-gray-200 px-3 py-2">
                                <form method="POST" action="{{ route('admin.seksi.update', $s) }}" class="flex flex-1 gap-2">
                                    @csrf @method('PUT')
                                    <input type="text" name="name" value="{{ $s->name }}" required class="flex-1 rounded-lg border-gray-300 text-sm min-h-[44px]">
                                    <button class="text-indigo-600 text-sm font-medium px-2 min-h-[44px]">Ubah</button>
                                </form>
                                <div class="flex items-center gap-3 justify-between sm:justify-end">
                                    <span class="text-xs text-gray-400">{{ $s->users_count }} anggota</span>
                                    <form method="POST" action="{{ route('admin.seksi.destroy', $s) }}" onsubmit="return confirm('Hapus seksi {{ $s->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 text-sm font-medium min-h-[44px]">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400">Belum ada seksi.</p>
                        @endforelse
                    </div>

                    {{-- Tambah seksi --}}
                    <form method="POST" action="{{ route('admin.seksi.store', $b) }}" class="flex gap-2">
                        @csrf
                        <input type="text" name="name" placeholder="Nama seksi baru…" required class="flex-1 rounded-lg border-gray-300 text-sm min-h-[44px]">
                        <button class="px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px]">+ Seksi</button>
                    </form>

                    {{-- Hapus bidang --}}
                    <form method="POST" action="{{ route('admin.bidang.destroy', $b) }}" class="mt-4 pt-4 border-t border-gray-100"
                          onsubmit="return confirm('Hapus bidang {{ $b->name }}? Hanya bisa bila tidak memiliki seksi.')">
                        @csrf @method('DELETE')
                        <button class="text-red-600 text-sm font-medium hover:underline min-h-[44px]">Hapus Bidang</button>
                    </form>
                </div>
            </details>
        @endforeach
    </div>

    {{-- Tambah bidang baru --}}
    <section class="mt-6 bg-white rounded-xl border border-gray-200 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Tambah Bidang Baru</p>
        <form method="POST" action="{{ route('admin.bidang.store') }}" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="text" name="name" placeholder="Nama bidang…" required class="flex-1 rounded-lg border-gray-300 text-sm min-h-[44px]">
            <label class="flex items-center gap-2 text-sm text-gray-500">
                Kuota
                <input type="number" name="max_active_bookings" value="2" min="1" max="9" required class="rounded-lg border-gray-300 w-20 min-h-[44px]">
            </label>
            <button class="px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px]">+ Bidang</button>
        </form>
    </section>
</x-app-layout>
