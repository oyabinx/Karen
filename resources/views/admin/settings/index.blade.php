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

        <div class="border-t border-gray-100 pt-5">
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

        {{-- Identitas dokumen bend26 (UAT 04 rev-2) — dipakai template BKPN bulanan --}}
        <div class="border-t border-gray-100 pt-5">
            <details>
                <summary class="cursor-pointer font-medium text-gray-700 select-none">Identitas Dokumen Bend26 (opsional — lanjutan)</summary>
                <div class="mt-4 space-y-4">
                    <p class="text-xs text-gray-400">Dipakai pada bend26 bulanan (BUKTI KAS PENGELUARAN). Nilai bawaan mengikuti file contoh kantor — ubah bila pejabat/rekening berganti.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="b26_terima_dari" value="Terima Dari (instansi)" />
                            <input id="b26_terima_dari" name="b26[terima_dari]" type="text" value="{{ old('b26.terima_dari', $bend26['terima_dari']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <x-input-label for="b26_kota" value="Kota Penandatanganan" />
                            <input id="b26_kota" name="b26[kota]" type="text" value="{{ old('b26.kota', $bend26['kota']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>

                    @foreach ([
                        'pengguna_anggaran' => 'Pengguna Anggaran',
                        'bendahara' => 'Bendahara Pengeluaran',
                        'penerima' => 'Yang Menerima',
                        'pptk' => 'PPTK (Barang Diterima)',
                    ] as $key => $label)
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_180px] gap-3 items-end">
                            <div>
                                <x-input-label for="b26_{{ $key }}_nama" value="{{ $label }} — Nama" />
                                <input id="b26_{{ $key }}_nama" name="b26[pejabat][{{ $key }}][nama]" type="text" value="{{ old('b26.pejabat.'.$key.'.nama', $bend26['pejabat'][$key]['nama']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <x-input-label for="b26_{{ $key }}_nip" value="NIP" />
                                <input id="b26_{{ $key }}_nip" name="b26[pejabat][{{ $key }}][nip]" type="text" value="{{ old('b26.pejabat.'.$key.'.nip', $bend26['pejabat'][$key]['nip']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                    @endforeach

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="b26_kode_kegiatan" value="Kode Kegiatan" />
                            <input id="b26_kode_kegiatan" name="b26[kode_kegiatan]" type="text" value="{{ old('b26.kode_kegiatan', $bend26['kode_kegiatan']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <x-input-label for="b26_ppn" value="Tarif PPN (%)" />
                            <input id="b26_ppn" name="b26[ppn_percent]" type="number" step="0.01" min="0" max="100" value="{{ old('b26.ppn_percent', $bend26['ppn_percent']) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>

                    <p class="text-xs font-medium text-gray-500">No. Rek & tarif PPh per pos</p>
                    @foreach (\App\Models\VehicleBudget::POSTS as $post)
                        <div class="grid grid-cols-1 sm:grid-cols-[1fr_120px] gap-3 items-end">
                            <div>
                                <x-input-label for="b26_rek_{{ $post }}" value="No. Rek — {{ ucfirst(str_replace('_', ' ', $post)) }}" />
                                <input id="b26_rek_{{ $post }}" name="b26[no_rek][{{ $post }}]" type="text" value="{{ old('b26.no_rek.'.$post, $bend26['no_rek'][$post]) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                            </div>
                            <div>
                                <x-input-label for="b26_pph_{{ $post }}" value="PPh (%)" />
                                <input id="b26_pph_{{ $post }}" name="b26[pph_percent][{{ $post }}]" type="number" step="0.01" min="0" max="100" value="{{ old('b26.pph_percent.'.$post, $bend26['pph_percent'][$post]) }}" class="block mt-1 w-full rounded-lg border-gray-300 text-sm">
                            </div>
                        </div>
                    @endforeach
                </div>
            </details>
        </div>

        {{-- SATU tombol simpan untuk seluruh pengaturan (UAT 04-E2) --}}
        <div class="flex justify-end border-t border-gray-100 pt-5">
            <x-primary-button>Simpan Pengaturan</x-primary-button>
        </div>
    </form>

    <div class="max-w-xl mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <p class="font-semibold mb-1">⚠ Catatan</p>
        <ul class="list-disc list-inside space-y-0.5 text-xs">
            <li>Peminjaman yang sudah berjalan TIDAK terpengaruh — hanya booking baru yang tunduk pada durasi terbaru.</li>
            <li>Event armada tidak dibatasi durasi ini (tetap fleksibel sesuai docs).</li>
            <li>Tombol <strong>Simpan Pengaturan</strong> menyimpan sekaligus durasi, koefisien, dan identitas bend26.</li>
        </ul>
    </div>
</x-app-layout>
