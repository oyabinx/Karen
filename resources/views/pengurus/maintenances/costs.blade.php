<x-app-layout title="Input Nota — Maintenance #{{ $maintenance->id }}">
    <div class="mb-6">
        <a href="{{ route('pengurus.maintenances.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke jadwal</a>
        <h1 class="text-2xl font-semibold mt-2">Input Nota Bengkel</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $maintenance->vehicle->name }} ({{ $maintenance->vehicle->plate_number }}) ·
            {{ $maintenance->start_date->translatedFormat('d M') }}–{{ $maintenance->end_date->translatedFormat('d M Y') }}
            @if ($maintenance->status === 'selesai') · <span class="text-green-600 font-medium">sudah selesai</span> @endif
        </p>
        <p class="text-sm text-gray-500">Pilah nilai nota ke 4 pos — sistem mengalikan tiap pos dengan koefisien <strong>1,13</strong> lalu menggenerate bend26 + draft nota per pos.</p>
    </div>

    <form method="POST" action="{{ route('pengurus.maintenances.nota', $maintenance) }}" class="max-w-2xl space-y-6">
        @csrf @method('PUT')

        {{-- Identitas nota --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-4">Identitas Nota</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-3">
                    <x-input-label for="workshop_name" value="Nama Bengkel" />
                    <x-text-input id="workshop_name" name="workshop_name" type="text" class="block mt-1 w-full" :value="old('workshop_name', $maintenance->workshop_name)" required />
                    <x-input-error :messages="$errors->get('workshop_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="nota_number" value="Nomor Nota" />
                    <x-text-input id="nota_number" name="nota_number" type="text" class="block mt-1 w-full" :value="old('nota_number', $maintenance->nota_number)" placeholder="opsional" />
                    <x-input-error :messages="$errors->get('nota_number')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="nota_date" value="Tanggal Nota" />
                    <x-text-input id="nota_date" name="nota_date" type="date" class="block mt-1 w-full" :value="old('nota_date', $maintenance->nota_date?->format('Y-m-d'))" />
                    <x-input-error :messages="$errors->get('nota_date')" class="mt-1" />
                </div>
            </div>
        </section>

        {{-- Rincian 4 pos --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-1">Rincian Nilai Nota per Pos</h2>
            <p class="text-sm text-gray-500 mb-4">Pos bernilai 0 tidak akan dibuatkan draft nota.</p>

            <div class="space-y-4">
                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <label class="text-sm font-medium">{{ ucfirst(str_replace('_', ' ', $post)) }}</label>
                        <div class="flex items-center">
                            <span class="px-3 py-2 rounded-s-lg bg-gray-100 border border-e-0 border-gray-300 text-sm text-gray-500">Rp</span>
                            <input type="number" step="0.01" min="0" name="costs[{{ $post }}]"
                                   value="{{ old('costs.'.$post, $costs[$post]->raw_amount ?? 0) }}" required
                                   class="flex-1 rounded-none rounded-e-lg border-gray-300 text-sm">
                        </div>
                        <p class="text-sm text-gray-400">× 1,13 = <span class="js-taxed font-medium text-gray-600">—</span></p>
                        <x-input-error :messages="$errors->get('costs.'.$post)" class="sm:col-span-3 -mt-3" />
                    </div>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('costs')" class="mt-3" />
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('pengurus.maintenances.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-center hover:bg-gray-50 min-h-[44px] leading-[44px]">Batal</a>
            <x-primary-button>Simpan Nota & Generate Dokumen</x-primary-button>
        </div>
    </form>

    {{-- Hitung ×1,13 langsung (Alpine) --}}
    <script>
        document.querySelectorAll('input[name^="costs["]').forEach(function (input) {
            input.addEventListener('input', function () {
                var out = input.closest('.grid').querySelector('.js-taxed');
                var val = parseFloat(input.value || 0);
                out.textContent = 'Rp ' + (val * 1.13).toLocaleString('id-ID', { maximumFractionDigits: 0 });
            });
            input.dispatchEvent(new Event('input'));
        });
    </script>
</x-app-layout>
