<x-app-layout title="Buat Event Armada">
    <div class="mb-6">
        <a href="{{ route('pengurus.events.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke daftar event</a>
        <h1 class="text-2xl font-semibold mt-2">Buat Event Armada Bidang</h1>
        <p class="text-sm text-gray-500 mt-1">Langkah 1: isi informasi event & tanggal → Langkah 2: pilih armada → Langkah 3: atur pengganti bila menabrak.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('pengurus.events.create') }}" class="bg-white rounded-xl border border-gray-200 p-6 mb-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
        <p class="sm:col-span-2 lg:col-span-4 text-xs font-semibold uppercase tracking-wider text-gray-400 -mb-2">Langkah 1 — Informasi event</p>
        <div>
            <x-input-label for="p_start" value="Tanggal mulai" />
            <x-text-input id="p_start" name="start_date" type="date" class="block mt-1 w-full" value="{{ $input['start_date'] ?? '' }}" />
        </div>
        <div>
            <x-input-label for="p_end" value="Tanggal selesai" />
            <x-text-input id="p_end" name="end_date" type="date" class="block mt-1 w-full" value="{{ $input['end_date'] ?? '' }}" />
        </div>
        <div class="lg:col-span-2">
            <button class="w-full px-4 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px]">Lihat Armada Tersedia</button>
        </div>
    </form>

    <form method="POST" action="{{ route('pengurus.events.store') }}" class="space-y-5">
        @csrf

        {{-- Formulir utama (disembunyikan ringkas, terlihat bila armada sudah dipratinjau) --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama Event" />
                <x-text-input id="name" name="name" type="text" class="block mt-1 w-full" value="{{ old('name', $input['name'] ?? '') }}" required placeholder="mis. Rapat Koordinasi Daerah" />
            </div>
            <div>
                <x-input-label for="bidang_id" value="Bidang Penyelenggara" />
                <select id="bidang_id" name="bidang_id" required class="block mt-1 w-full rounded-lg border-gray-300">
                    <option value="">— pilih —</option>
                    @foreach ($bidangList as $b)
                        <option value="{{ $b->id }}" {{ (string) old('bidang_id', $input['bidang_id'] ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="start_date" value="Tanggal mulai" />
                <x-text-input id="start_date" name="start_date" type="date" class="block mt-1 w-full" value="{{ old('start_date', $input['start_date'] ?? '') }}" required />
            </div>
            <div>
                <x-input-label for="end_date" value="Tanggal selesai (boleh >3 hari)" />
                <x-text-input id="end_date" name="end_date" type="date" class="block mt-1 w-full" value="{{ old('end_date', $input['end_date'] ?? '') }}" required />
            </div>
            <div>
                <x-input-label for="jumlah_mobil" value="Jumlah mobil dibutuhkan (N)" />
                <x-text-input id="jumlah_mobil" name="jumlah_mobil" type="number" min="1" class="block mt-1 w-full" value="{{ old('jumlah_mobil', $input['jumlah_mobil'] ?? 1) }}" required />
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <x-input-label for="note" value="Catatan (opsional)" />
                <x-text-input id="note" name="note" type="text" class="block mt-1 w-full" value="{{ old('note', $input['note'] ?? '') }}" />
            </div>
        </section>

        {{-- Langkah 2: armada --}}
        @if ($armada)
            <section class="bg-white rounded-xl border border-gray-200 p-6">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Langkah 2 — Pilih tepat N armada</p>
                <p class="text-sm text-gray-500 mb-4">
                    ✅ <strong>Bebas</strong> ({{ $armada['bebas']->count() }}): tanpa peminjaman bertabrakan.
                    ⚠ <strong>Menabrak</strong> ({{ $armada['menabrak']->count() }}): ada peminjaman — akan digeser dengan mobil pengganti pada langkah berikutnya.
                    🔧 <strong>Maintenance</strong> ({{ $armada['maintenance']->count() }}): sedang terjadwal perawatan — <em>tidak dapat dipilih</em>.
                </p>

                @foreach (['bebas' => 'Bebas', 'menabrak' => 'Menabrak peminjaman', 'maintenance' => 'Sedang Maintenance (tidak dapat dipilih)'] as $key => $label)
                    @if ($armada[$key]->isNotEmpty())
                        <p class="text-sm font-semibold mt-4 mb-2 {{ $key === 'menabrak' ? 'text-amber-600' : ($key === 'maintenance' ? 'text-gray-500' : 'text-green-700') }}">{{ $key === 'bebas' ? '✅' : ($key === 'menabrak' ? '⚠' : '🔧') }} {{ $label }}</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($armada[$key] as $v)
                                @if ($key === 'maintenance')
                                    {{-- UAT 03-D2: tampil tapi nonaktif dengan rentang maintenance --}}
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 opacity-70 flex items-start gap-3 cursor-not-allowed">
                                        <input type="checkbox" disabled class="mt-0.5 rounded border-gray-300 text-gray-400 min-h-[44px] min-w-[44px]">
                                        <span>
                                            <span class="font-medium block text-gray-500">{{ $v->name }}</span>
                                            <span class="text-xs text-gray-400">{{ $v->plate_number }} · {{ $v->capacity }} kursi</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">🔧 Maintenance: {{ $v->blocking_start->translatedFormat('d M') }} – {{ $v->blocking_end->translatedFormat('d M Y') }}</span>
                                        </span>
                                    </div>
                                @else
                                    <label class="flex items-start gap-3 rounded-xl border {{ in_array($v->id, old('vehicles', $selected ?? [])) ? 'border-indigo-400 bg-indigo-50/50' : 'border-gray-200' }} p-4 cursor-pointer hover:border-indigo-300">
                                        <input type="checkbox" name="vehicles[]" value="{{ $v->id }}"
                                               class="mt-0.5 rounded border-gray-300 text-indigo-600 min-h-[44px] min-w-[44px]"
                                               {{ in_array($v->id, old('vehicles', $selected ?? [])) ? 'checked' : '' }}>
                                        <span>
                                            <span class="font-medium block">{{ $v->name }}</span>
                                            <span class="text-xs text-gray-500">{{ $v->plate_number }} · {{ $v->year }} · {{ $v->capacity }} kursi</span>
                                            @if ($key === 'menabrak')
                                                <span class="block text-xs text-amber-600 mt-0.5">{{ $v->conflict_count }} peminjaman ditabrak</span>
                                            @endif
                                        </span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </section>
        @else
            <section class="bg-white rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-400">
                Isi tanggal pada form di atas lalu klik <strong>“Lihat Armada Tersedia”</strong> untuk menampilkan pilihan armada.
            </section>
        @endif

        <div class="flex justify-end">
            <x-primary-button>{{ $armada ? 'Buat Event' : 'Simpan & Lanjut' }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
