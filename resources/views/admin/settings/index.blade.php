<x-app-layout title="Pengaturan Aplikasi">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Pengaturan Aplikasi</h1>
        <p class="text-sm text-gray-500 mt-1">Kebijakan operasional Karen — dapat diubah kapan pun tanpa mengubah kode.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-xl bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="max_booking_days" value="Durasi Maksimal Peminjaman (hari)" />
            <div class="flex items-center gap-3 mt-1">
                <input id="max_booking_days" name="max_booking_days" type="number"
                       value="{{ old('max_booking_days', $maxBookingDays) }}"
                       min="{{ $min }}" max="{{ $max }}" required
                       class="rounded-lg border-gray-300 text-sm w-28 min-h-[44px]">
                <span class="text-sm text-gray-500">hari (termasuk Sabtu–Minggu)</span>
            </div>
            <x-input-error :messages="$errors->get('max_booking_days')" class="mt-1" />
            <p class="text-xs text-gray-400 mt-2">
                Rentang: {{ $min }}–{{ $max }} hari. Nilai saat ini: <strong>{{ $maxBookingDays }} hari</strong>.
                Perubahan langsung berlaku pada pencarian, form booking, dan validasi — tanpa restart.
            </p>
        </div>

        <div class="flex justify-end">
            <x-primary-button>Simpan Pengaturan</x-primary-button>
        </div>

        <div class="border-t border-gray-100 pt-5 mt-5">
            <x-input-label for="koefisien_pajak" value="Koefisien Pajak (×)" />
            <div class="flex items-center gap-3 mt-1">
                <span class="text-lg font-semibold text-gray-400">×</span>
                <input id="koefisien_pajak" name="koefisien_pajak" type="number" step="0.01"
                       value="{{ old('koefisien_pajak', $koefisienPajak) }}"
                       min="{{ $koefMin }}" max="{{ $koefMax }}" required
                       class="rounded-lg border-gray-300 text-sm w-28 min-h-[44px]">
            </div>
            <x-input-error :messages="$errors->get('koefisien_pajak')" class="mt-1" />
            <p class="text-xs text-gray-400 mt-2">
                Nilai saat ini: <strong>×{{ number_format($koefisienPajak, 2, ',', '.') }}</strong>.
                Rentang: {{ number_format($koefMin, 2) }}–{{ number_format($koefMax, 2) }}.
                ⚠ Perubahan hanya berlaku untuk <strong>nota yang diinput SETELAH ini</strong> —
                nota lama tetap memakai koefisien saat input (jejak historis tersimpan di koefisien_used).
            </p>
        </div>

        <div class="flex justify-end">
            <x-primary-button>Simpan Semua</x-primary-button>
        </div>
    </form>

    <div class="max-w-xl mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold mb-1">⚠ Catatan</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            <li>Peminjaman yang sudah berjalan TIDAK terpengaruh — hanya booking baru yang tunduk pada durasi terbaru.</li>
            <li>Event armada tidak dibatasi durasi ini (tetap fleksibel sesuai docs).</li>
        </ul>
    </div>
</x-app-layout>
