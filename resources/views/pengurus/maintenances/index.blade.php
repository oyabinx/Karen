<x-app-layout title="Jadwal Maintenance">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Jadwal Maintenance</h1>
        <p class="text-sm text-gray-500 mt-1">Kendaraan dalam masa maintenance tidak tersedia untuk dipinjam. Booking yang menabrak jadwal akan menunggu mobil pengganti.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-4 rounded-lg bg-amber-100 border-2 border-amber-400 px-4 py-3 text-sm text-amber-900 font-medium">{{ session('warning') }}</div>
    @endif

    {{-- Banner penggantian menunggu keputusan (UAT F2 — tetap terlihat) --}}
    @php($menunggu = App\Models\Booking::where('status', App\Models\Booking::STATUS_MENUNGGU_PENGGANTIAN)->count())
    @if ($menunggu > 0)
        <a href="{{ route('pengurus.replacements.index') }}"
           class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border-2 border-amber-400 bg-amber-50 px-4 py-3 hover:bg-amber-100 transition-colors">
            <span class="text-sm font-semibold text-amber-900">⚠ {{ $menunggu }} peminjaman menunggu mobil pengganti (karena maintenance/event)</span>
            <span class="text-sm font-medium text-amber-700 underline">Atur pengganti sekarang →</span>
        </a>
    @endif

    <div class="mb-6">
        <a href="{{ route('pengurus.replacements.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-amber-500 text-white text-sm font-medium hover:bg-amber-600 min-h-[44px]">
            ⚠ Penggantian Mobil Menunggu Konfirmasi
        </a>
    </div>

    {{-- Tambah jadwal --}}
    <section class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Tambah Jadwal</p>
        <form method="POST" action="{{ route('pengurus.maintenances.store') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            @csrf
            <div class="lg:col-span-1">
                <x-input-label for="vehicle_id" value="Kendaraan" />
                <select id="vehicle_id" name="vehicle_id" required class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <option value="">— pilih —</option>
                    @foreach ($vehicles as $v)
                        <option value="{{ $v->id }}">{{ $v->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('vehicle_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="start_date" value="Mulai" />
                <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_date" value="Selesai" />
                <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="note" value="Catatan (opsional)" />
                <x-text-input id="note" name="note" type="text" class="block mt-1 w-full" placeholder="mis. servis rutin 40.000 km" />
            </div>
            <x-primary-button class="justify-center min-h-[44px]">Simpan</x-primary-button>
        </form>
    </section>

    {{-- Tab status --}}
    <div class="flex gap-2 mb-4 text-sm">
        @foreach (['terjadwal' => 'Terjadwal', 'selesai' => 'Selesai'] as $key => $label)
            <a href="{{ route('pengurus.maintenances.index', ['status' => $key]) }}"
               class="px-3.5 py-2 rounded-full font-medium min-h-[44px] flex items-center {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- Daftar: kartu mobile & desktop --}}
    <div class="space-y-3">
        @forelse ($maintenances as $m)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $m->vehicle->name }} <span class="text-gray-400 font-normal">({{ $m->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $m->start_date->translatedFormat('d M Y') }} — {{ $m->end_date->translatedFormat('d M Y') }}
                            @if ($m->note) · {{ $m->note }} @endif
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $m->status === 'terjadwal' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700' }}">{{ $m->status === 'terjadwal' ? 'Terjadwal' : 'Selesai' }}</span>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    @if ($m->status === 'terjadwal')
                        <form method="POST" action="{{ route('pengurus.maintenances.finish', $m) }}">
                            @csrf @method('PATCH')
                            <button class="text-green-600 hover:underline">Tandai selesai</button>
                        </form>
                        {{-- Ubah jadwal: form inline tanggal --}}
                        <details class="inline">
                            <summary class="text-indigo-600 hover:underline cursor-pointer select-none">Ubah tanggal</summary>
                            <form method="POST" action="{{ route('pengurus.maintenances.update', $m) }}" class="mt-2 flex flex-wrap gap-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="vehicle_id" value="{{ $m->vehicle_id }}">
                                <input type="date" name="start_date" value="{{ $m->start_date->format('Y-m-d') }}" required class="rounded-lg border-gray-300 text-sm">
                                <input type="date" name="end_date" value="{{ $m->end_date->format('Y-m-d') }}" required class="rounded-lg border-gray-300 text-sm">
                                <input type="text" name="note" value="{{ $m->note }}" placeholder="catatan" class="rounded-lg border-gray-300 text-sm">
                                <button class="text-gray-800 font-medium hover:underline">Simpan</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('pengurus.maintenances.destroy', $m) }}" onsubmit="return confirm('Hapus jadwal ini? Booking yang belum diganti akan dikembalikan ke mobil semula.')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada jadwal maintenance {{ $status }}.</div>
        @endforelse
    </div>
</x-app-layout>
