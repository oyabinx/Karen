<x-app-layout title="Integrasi Google">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Integrasi Google</h1>
        <p class="text-sm text-gray-500 mt-1">Sinkronisasi anggaran via Google Sheets API (outbound) — seluruh konfigurasi tersimpan di database, tidak ada file kredensial di server.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.integrasi.google.update') }}" enctype="multipart/form-data" class="max-w-3xl space-y-6">
        @csrf

        {{-- Status & interval --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-4">Jalankan</h2>
            <div class="flex flex-wrap items-center gap-6">
                <label class="flex items-center gap-2 min-h-[44px]">
                    <input type="checkbox" name="enabled" value="1" {{ $settings['enabled'] ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                    <span class="text-sm font-medium">Integrasi aktif</span>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    Interval polling
                    <select name="poll_minutes" class="rounded-lg border-gray-300">
                        @foreach ([5, 15, 30, 60] as $m)
                            <option value="{{ $m }}" {{ (int) $settings['pollMinutes'] === $m ? 'selected' : '' }}>{{ $m }} menit</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        {{-- Kredensial --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-1">Kredensial (Service Account)</h2>
            <p class="text-sm text-gray-500 mb-4">
                Unggah kunci JSON dari Google Cloud Console. Kunci disimpan <strong>terenkripsi di database</strong> dan tidak pernah ditampilkan ulang.
            </p>

            @if ($settings['keyEmail'])
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex flex-wrap items-center justify-between gap-3">
                    <span>Kunci aktif — service account: <strong>{{ $settings['keyEmail'] }}</strong></span>
                    <button type="submit" form="hapus-kunci" class="text-red-600 hover:underline text-sm font-medium">Hapus kunci</button>
                </div>
            @endif

            <x-input-label for="key_file" value="Unggah / ganti kunci JSON (maks 50 KB)" />
            {{-- Tombol pilih file mencolok + nama file terpilih tampil jelas (UAT B1) --}}
            <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center mt-1">
                <label class="cursor-pointer inline-flex items-center justify-center gap-2 px-5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px] shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Pilih File Kunci
                    <input id="key_file" name="key_file" type="file" accept=".json,application/json" class="hidden"
                           onchange="document.getElementById('file-kunci-terpilih').textContent = this.files[0] ? this.files[0].name : ''; document.getElementById('file-kunci-terpilih').classList.toggle('text-gray-800', !!this.files[0]);">
                </label>
                <span id="file-kunci-terpilih" class="flex-1 px-4 py-2 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-400 min-h-[44px] flex items-center truncate">belum memilih file…</span>
            </div>
            <x-input-error :messages="$errors->get('key_file')" class="mt-2" />
        </section>

        {{-- Google Sheets --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-1">Google Sheets (anggaran)</h2>
            <p class="text-sm text-gray-500 mb-4">Bagikan spreadsheet ke email service account di atas dengan akses <strong>Editor</strong>.</p>

            <div class="space-y-4">
                <div>
                    <x-input-label for="spreadsheet" value="URL Spreadsheet" />
                    <x-text-input id="spreadsheet" name="spreadsheet" type="text" class="block mt-1 w-full"
                                  value="{{ old('spreadsheet', $settings['spreadsheetUrl']) }}" placeholder="https://docs.google.com/spreadsheets/d/..." />
                    <x-input-error :messages="$errors->get('spreadsheet')" class="mt-1" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sheet_anggaran" value="Nama tab anggaran" />
                        <x-text-input id="sheet_anggaran" name="sheet_anggaran" type="text" class="block mt-1 w-full" value="{{ old('sheet_anggaran', $settings['sheetAnggaran']) }}" required />
                    </div>
                    <div>
                        <x-input-label for="sheet_realisasi" value="Nama tab realisasi" />
                        <x-text-input id="sheet_realisasi" name="sheet_realisasi" type="text" class="block mt-1 w-full" value="{{ old('sheet_realisasi', $settings['sheetRealisasi']) }}" required />
                    </div>
                </div>
            </div>
        </section>

        {{-- Google Drive (opsional) --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-1">Google Drive — arsip dokumen (opsional)</h2>
            <p class="text-sm text-gray-500 mb-4">Salinan PDF (bend26, draft nota, kartu inventaris) diunggah ke folder ini sebagai arsip sekunder.</p>

            <label class="flex items-center gap-2 min-h-[44px] mb-3">
                <input type="checkbox" name="drive_enabled" value="1" {{ $settings['driveEnabled'] ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                <span class="text-sm font-medium">Aktifkan arsip ke Drive</span>
            </label>

            <x-input-label for="drive_folder" value="URL Folder Drive" />
            <x-text-input id="drive_folder" name="drive_folder" type="text" class="block mt-1 w-full"
                          value="{{ old('drive_folder', $settings['driveFolderUrl']) }}" placeholder="https://drive.google.com/drive/folders/..." />
        </section>

        <div class="flex justify-end">
            <x-primary-button>Simpan Konfigurasi</x-primary-button>
        </div>
    </form>

    {{-- Aksi --}}
    <div class="max-w-3xl flex flex-col sm:flex-row gap-3 mt-6">
        <form method="POST" action="{{ route('admin.integrasi.google.test') }}">
            @csrf
            <button class="w-full sm:w-auto px-4 rounded-lg border border-indigo-300 text-indigo-700 text-sm hover:bg-indigo-50 min-h-[40px]">🔌 Test Koneksi</button>
        </form>
        <form method="POST" action="{{ route('admin.integrasi.google.sync') }}">
            @csrf
            <button class="w-full sm:w-auto px-4 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px]">🔄 Sinkron Sekarang</button>
        </form>
    </div>

    {{-- Hasil test --}}
    @if ($testSteps)
        <section class="max-w-3xl bg-white rounded-xl border border-gray-200 p-6 mt-6">
            <h2 class="font-semibold mb-3">Hasil Test Koneksi</h2>
            <div class="space-y-2">
                @foreach ($testSteps as $step)
                    <div class="flex items-start gap-3 text-sm {{ $step['ok'] ? 'text-green-700' : 'text-red-700' }}">
                        <span class="font-bold">{{ $step['ok'] ? '✓' : '✗' }}</span>
                        <div>
                            <p class="font-medium">{{ $step['langkah'] }}</p>
                            <p class="text-xs opacity-75">{{ $step['pesan'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Log --}}
    <section class="max-w-3xl bg-white rounded-xl border border-gray-200 p-6 mt-6">
        <h2 class="font-semibold mb-3">Log Sinkronisasi (20 terakhir)</h2>
        <div class="space-y-2">
            @forelse ($logs as $log)
                <div class="flex flex-wrap items-center gap-2 text-sm border-b border-gray-100 pb-2">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $log->status === 'sukses' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $log->status }}</span>
                    <span class="text-gray-500">{{ $log->ran_at->translatedFormat('d M Y H:i') }}</span>
                    <span class="text-gray-400">· {{ $log->task }} · {{ $log->duration_ms }} ms</span>
                    @if ($log->message)
                        <span class="w-full text-xs text-gray-500 truncate" title="{{ $log->message }}">{{ Str::limit($log->message, 110) }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-400">Belum ada eksekusi tercatat.</p>
            @endforelse
        </div>
    </section>

    {{-- Form terpisah hapus kunci --}}
    <form id="hapus-kunci" method="POST" action="{{ route('admin.integrasi.google.key.destroy') }}" onsubmit="return confirm('Hapus kunci service account dari database?')">
        @csrf @method('DELETE')
    </form>
</x-app-layout>
