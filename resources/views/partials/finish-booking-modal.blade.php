{{-- Modal aksi peminjaman (docs/feature/pengembalian.md + peminjaman.md).
     TOMBOL ADAPTIF (usulan user pasca-UAT 03):
     • hari ini >= tanggal mulai → "Selesai — Kembalikan Mobil" + keluhan opsional
     • hari ini <  tanggal mulai → "Batalkan Peminjaman" (konfirmasi, tanpa keluhan)
     Pemakaian: @include('partials.finish-booking-modal', ['booking' => $booking]) --}}
@php($belumMulai = $booking->belumMulai())
<div x-data="{ open: false }" @keydown.escape.window="open = false">
    @if ($belumMulai)
        <button @click="open = true"
                class="px-5 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 min-h-[48px] {{ $class ?? '' }}">
            Batalkan Peminjaman
        </button>
    @else
        <button @click="open = true"
                class="px-5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[48px] {{ $class ?? '' }}">
            Selesai — Kembalikan Mobil
        </button>
    @endif

    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-50 bg-black/50" x-transition.opacity></div>

    <div x-show="open" x-cloak x-transition
         class="fixed inset-x-4 top-1/2 -translate-y-1/2 sm:inset-x-0 sm:mx-auto sm:max-w-lg z-50 bg-white rounded-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
        @if ($belumMulai)
            {{-- Konfirmasi pembatalan (belum meminjam) --}}
            <h3 class="text-lg font-semibold text-red-700">Batalkan Peminjaman?</h3>

            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                <p class="font-medium text-gray-800">{{ $booking->vehicle->name }} ({{ $booking->vehicle->plate_number }})</p>
                <p>{{ $booking->start_date->translatedFormat('d M') }} — {{ $booking->end_date->translatedFormat('d M Y') }} · {{ $booking->address }}</p>
            </div>

            <p class="mt-3 text-sm text-gray-600">
                Peminjaman ini <strong>belum dimulai</strong> (mulai {{ $booking->start_date->translatedFormat('d M Y') }}).
                Pembatalan mencatat status <strong>Dibatalkan</strong> beserta waktunya, melepas kuota bidang Anda,
                dan mobil langsung tersedia untuk yang lain.
            </p>

            <form method="POST" action="{{ route('pegawai.returns.cancel', $booking) }}" class="mt-5">
                @csrf
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button type="button" @click="open = false"
                            class="w-full sm:w-auto px-4 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 text-center min-h-[40px]">Batal</button>
                    <button type="submit"
                            class="w-full sm:w-auto px-5 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 min-h-[48px]">
                        Ya, Batalkan Peminjaman
                    </button>
                </div>
            </form>
        @else
            {{-- Pengembalian (sudah/tengah meminjam) + keluhan opsional --}}
            <h3 class="text-lg font-semibold">Selesaikan Peminjaman</h3>

            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                <p class="font-medium text-gray-800">{{ $booking->vehicle->name }} ({{ $booking->vehicle->plate_number }})</p>
                <p>{{ $booking->start_date->translatedFormat('d M') }} — {{ $booking->end_date->translatedFormat('d M Y') }} · {{ $booking->address }}</p>
            </div>

            <form method="POST" action="{{ route('pegawai.returns.store', $booking) }}" class="mt-4">
                @csrf

                <label for="complaint-{{ $booking->id }}" class="block text-sm font-medium text-gray-700">
                    Apakah ada keluhan terkait unit yang dipinjam?
                </label>
                <textarea id="complaint-{{ $booking->id }}" name="complaint" rows="3" maxlength="1000"
                          class="mt-2 block w-full rounded-lg border-gray-300 text-sm"
                          placeholder="Boleh dikosongkan bila tidak ada keluhan…"></textarea>
                <p class="text-xs text-gray-400 mt-1">Keluhan tercatat untuk ditinjau pengurus — mobil tetap langsung tersedia dipinjam.</p>

                <div class="mt-5 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button type="button" @click="open = false"
                            class="w-full sm:w-auto px-4 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 text-center min-h-[40px]">Batal</button>
                    <button type="submit"
                            class="w-full sm:w-auto px-5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[48px]">
                        Selesai — Kembalikan Mobil
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
