<x-app-layout title="Keluhan Unit">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Keluhan Unit</h1>
        <p class="text-sm text-gray-500 mt-1">Keluhan dikelompokkan per kendaraan — jadwalkan maintenance langsung dari kartu; catatan terisi gabungan seluruh keluhan aktif.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="mb-4 rounded-lg bg-amber-100 border-2 border-amber-400 px-4 py-3 text-sm text-amber-900 font-medium">{{ session('warning') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold mb-1">Jadwal tidak tersimpan:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex gap-2 mb-4 text-sm">
        @foreach (['belum' => 'Belum Selesai', 'selesai' => 'Selesai'] as $key => $label)
            <a href="{{ route('pengurus.complaints.index', ['status' => $key]) }}"
               class="px-3.5 py-2 rounded-full font-medium min-h-[44px] flex items-center {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($status !== 'selesai')
        {{-- ═══ BELUM SELESAI: kartu per KENDARAAN + modal jadwalkan (rev user) ═══ --}}
        <div class="space-y-4">
            @forelse ($perVehicle as $v)
                @php($vehicle = $v['vehicle'])
                <div class="bg-white rounded-xl border-2 border-amber-200 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $vehicle->name }} <span class="text-gray-400 font-normal">({{ $vehicle->plate_number }})</span></p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $v['complaints']->count() }} keluhan aktif</p>
                        </div>
                        <button type="button" onclick="document.getElementById('jadwal-modal-{{ $vehicle->id }}').showModal()"
                                class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[44px] shrink-0">
                            🔧 Jadwalkan Maintenance
                        </button>
                    </div>

                    {{-- Daftar keluhan unit ini --}}
                    <ul class="mt-3 space-y-2">
                        @foreach ($v['complaints'] as $c)
                            @php($b = $c->booking)
                            <li class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2.5">
                                <p class="text-sm">“{{ $c->message }}”</p>
                                <div class="flex flex-wrap items-center justify-between gap-2 mt-1.5">
                                    <p class="text-xs text-gray-400">
                                        {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif ·
                                        dikembalikan {{ optional($b->returned_at)->translatedFormat('d M Y H:i') ?? $c->created_at->translatedFormat('d M Y') }}
                                    </p>
                                    <form method="POST" action="{{ route('pengurus.complaints.resolve', $c) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-xs text-green-600 hover:underline font-medium">Tandai selesai</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- MODAL jadwalkan maintenance: tanggal saja, kendaraan + catatan otomatis --}}
                <dialog id="jadwal-modal-{{ $vehicle->id }}" class="w-[calc(100%-2rem)] sm:w-auto sm:max-w-md rounded-xl p-0 backdrop:bg-gray-900/50">
                    <form method="POST" action="{{ route('pengurus.maintenances.store') }}" class="p-5">
                        @csrf
                        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

                        <h3 class="font-semibold">Jadwalkan Maintenance</h3>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $vehicle->name }} ({{ $vehicle->plate_number }})</p>

                        <div class="mt-4">
                            <x-input-label for="start-{{ $vehicle->id }}" value="Tanggal Mulai" />
                            <input type="date" id="start-{{ $vehicle->id }}" name="start_date" required
                                   min="{{ now()->toDateString() }}"
                                   onchange="document.getElementById('end-{{ $vehicle->id }}').min = this.value"
                                   class="block mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]">
                            <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                        </div>
                        <div class="mt-3">
                            <x-input-label for="end-{{ $vehicle->id }}" value="Tanggal Selesai" />
                            <input type="date" id="end-{{ $vehicle->id }}" name="end_date" required
                                   min="{{ now()->toDateString() }}"
                                   class="block mt-1 w-full rounded-lg border-gray-300 text-sm min-h-[44px]">
                            <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                        </div>
                        <div class="mt-3">
                            <x-input-label for="note-{{ $vehicle->id }}" value="Catatan (gabungan keluhan — boleh disunting)" />
                            <textarea id="note-{{ $vehicle->id }}" name="note" rows="3" maxlength="255"
                                      class="block mt-1 w-full rounded-lg border-gray-300 text-sm">{{ $v['catatanGabungan'] }}</textarea>
                            <x-input-error :messages="$errors->get('note')" class="mt-1" />
                        </div>

                        <p class="text-xs text-gray-400 mt-3">Menyimpan akan memeriksa tabrakan dengan peminjaman aktif — bila menabrak, Anda diarahkan ke halaman Penggantian Mobil seperti biasa.</p>

                        <div class="mt-4 flex justify-end gap-3">
                            <button type="button" onclick="this.closest('dialog').close()"
                                    class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium hover:bg-gray-50 min-h-[44px]">Batal</button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[44px]">Simpan Jadwal</button>
                        </div>
                    </form>
                </dialog>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada keluhan belum selesai. 🎉</div>
            @endforelse
        </div>
    @else
        {{-- ═══ SELESAI: daftar datar riwayat ═══ --}}
        <div class="space-y-3">
            @forelse ($complaints as $c)
                @php($b = $c->booking)
                <div class="bg-white rounded-xl border border-gray-200 opacity-70 p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                            <p class="text-sm text-gray-500">
                                {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif ·
                                peminjaman {{ $b->start_date->translatedFormat('d M Y') }} (dikembalikan {{ optional($b->returned_at)->translatedFormat('d M Y H:i') ?? '-' }})
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 bg-green-100 text-green-700">Selesai</span>
                    </div>

                    <p class="mt-3 text-sm bg-gray-50 border border-gray-100 rounded-lg px-4 py-3">“{{ $c->message }}”</p>

                    <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                        <form method="POST" action="{{ route('pengurus.complaints.reopen', $c) }}">
                            @csrf @method('PATCH')
                            <button class="text-gray-600 hover:underline">Buka kembali</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada keluhan selesai.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $complaints->links() }}</div>
    @endif
</x-app-layout>
