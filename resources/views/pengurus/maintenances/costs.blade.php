@php($koefisien = \App\Services\BudgetService::koefisienPajak())
<x-app-layout title="Input Nota — Maintenance #{{ $maintenance->id }}">
    <div class="mb-4">
        <a href="{{ route('pengurus.maintenances.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke jadwal</a>
        <h1 class="text-2xl font-semibold mt-2">Input Nota Bengkel</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $maintenance->vehicle->name }} ({{ $maintenance->vehicle->plate_number }}) ·
            {{ $maintenance->start_date->translatedFormat('d M') }}–{{ $maintenance->end_date->translatedFormat('d M Y') }}
        </p>
        <p class="text-sm text-gray-500">Pilah nilai nota ke 4 pos — sistem mengalikan dengan koefisien <strong>×{{ number_format($koefisien, 2, ',', '.') }}</strong>.</p>
    </div>

    <form method="POST" action="{{ route('pengurus.maintenances.nota', $maintenance) }}" id="nota-form"
          data-koefisien="{{ $koefisien }}">
        @csrf @method('PUT')

        {{-- Identitas nota --}}
        <section class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 mb-4">
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
                </div>
                <div>
                    <x-input-label for="nota_date" value="Tanggal Nota" />
                    <x-text-input id="nota_date" name="nota_date" type="date" class="block mt-1 w-full" :value="old('nota_date', $maintenance->nota_date?->format('Y-m-d'))" />
                </div>
            </div>
        </section>

        {{-- 4 Pos — mobile-first: stack vertikal, desktop: grid 2 kolom --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            @foreach (\App\Models\VehicleBudget::POSTS as $postIndex => $post)
                @php($existing = $costs[$post] ?? null)
                <section class="bg-white rounded-xl border {{ $existing && $existing->raw_amount > 0 ? 'border-emerald-200' : 'border-gray-200' }} p-4" data-post="{{ $post }}">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-sm">{{ ucfirst(str_replace('_', ' ', $post)) }}</h3>
                        <span class="text-xs text-gray-400">opsional</span>
                    </div>

                    {{-- Nominal pos: besar, full-width, dengan hasil × koefisien --}}
                    <div class="flex items-center">
                        <span class="px-3 py-2.5 rounded-s-lg bg-gray-100 border border-e-0 border-gray-300 text-sm text-gray-500 font-medium">Rp</span>
                        <input type="text" inputmode="numeric" name="costs[{{ $post }}]"
                               value="{{ old('costs.'.$post, $existing ? number_format((float)$existing->raw_amount, 0, ',', '.') : '0') }}"
                               class="flex-1 rounded-none rounded-e-lg border-gray-300 text-lg font-semibold min-h-[48px] js-pos-input" placeholder="0">
                    </div>
                    <p class="text-right text-sm mt-1.5">
                        <span class="text-gray-400">× {{ number_format($koefisien, 2, ',', '.') }} =</span>
                        <span class="js-taxed font-bold text-emerald-700 text-base">Rp 0</span>
                    </p>

                    {{-- Rincian baris (opsional) --}}
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Rincian (opsional)</p>
                        <div class="js-details space-y-2">
                            @if ($existing && $existing->details->isNotEmpty())
                                @foreach ($existing->details as $detail)
                                    <div class="js-detail-row flex gap-2 items-center">
                                        <input type="text" name="details[{{ $post }}][]" value="{{ $detail->description }}"
                                               placeholder="mis. Ganti oli mesin" maxlength="255"
                                               class="flex-1 rounded-lg border-gray-300 text-sm min-h-[44px]">
                                        <div class="flex items-center w-32 shrink-0">
                                            <input type="text" inputmode="numeric" name="detail_amounts[{{ $post }}][]" value="{{ number_format((float)$detail->amount, 0, ',', '.') }}"
                                                   placeholder="0" class="w-full rounded-s-lg border-gray-300 border-e-0 text-sm min-h-[44px] js-detail-amount">
                                            <span class="px-2 py-2 rounded-e-lg bg-gray-100 border border-s-0 border-gray-300 text-xs text-gray-400">rb</span>
                                        </div>
                                        <button type="button" class="js-remove-detail text-red-400 hover:text-red-600 p-2 min-h-[44px] min-w-[44px] shrink-0" aria-label="Hapus baris">✕</button>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <button type="button" class="js-add-detail mt-2 w-full px-3 py-2 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-500 hover:border-indigo-300 hover:text-indigo-600 min-h-[44px] transition-colors">
                            + Tambah Rincian
                        </button>
                    </div>
                </section>
            @endforeach
        </div>

        <x-input-error :messages="$errors->get('costs')" class="mb-4" />

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-4">
            <a href="{{ route('pengurus.maintenances.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-center hover:bg-gray-50 min-h-[44px] leading-[44px]">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 min-h-[48px]">
                Simpan Nota & Generate Dokumen
            </button>
        </div>
    </form>

    <script>
    (function() {
        var koefisien = parseFloat(document.getElementById('nota-form').dataset.koefisien);

        // Format ribuan live
        function formatRibuan(input) {
            var digits = input.value.replace(/\D/g, '');
            input.value = digits ? parseInt(digits).toLocaleString('id-ID') : '';
        }

        function parseAngka(str) {
            var d = (str || '').replace(/\D/g, '');
            return d ? parseInt(d) : 0;
        }

        // Update hasil × koefisien untuk setiap pos
        function updateTaxed(section) {
            var input = section.querySelector('.js-pos-input');
            var out = section.querySelector('.js-taxed');
            var val = parseAngka(input.value);
            out.textContent = 'Rp ' + Math.round(val * koefisien).toLocaleString('id-ID');
        }

        document.querySelectorAll('[data-post]').forEach(function(section) {
            var input = section.querySelector('.js-pos-input');

            input.addEventListener('input', function() {
                formatRibuan(this);
                updateTaxed(section);
            });

            // Trigger initial
            formatRibuan(input);
            updateTaxed(section);

            // Rincian: tambah baris
            section.querySelector('.js-add-detail').addEventListener('click', function() {
                var row = document.createElement('div');
                row.className = 'js-detail-row flex gap-2 items-center';
                var post = section.dataset.post;
                row.innerHTML =
                    '<input type="text" name="details[' + post + '][]" value="" placeholder="mis. Ganti oli mesin" maxlength="255" class="flex-1 rounded-lg border-gray-300 text-sm min-h-[44px]">' +
                    '<div class="flex items-center w-32 shrink-0">' +
                    '<input type="text" inputmode="numeric" name="detail_amounts[' + post + '][]" value="" placeholder="0" class="w-full rounded-s-lg border-gray-300 border-e-0 text-sm min-h-[44px] js-detail-amount">' +
                    '<span class="px-2 py-2 rounded-e-lg bg-gray-100 border border-s-0 border-gray-300 text-xs text-gray-400">rb</span>' +
                    '</div>' +
                    '<button type="button" class="js-remove-detail text-red-400 hover:text-red-600 p-2 min-h-[44px] min-w-[44px] shrink-0" aria-label="Hapus baris">✕</button>';
                section.querySelector('.js-details').appendChild(row);
                bindRow(row);
                row.querySelector('input[type="text"]').focus();
            });

            // Bind existing rows
            section.querySelectorAll('.js-detail-row').forEach(bindRow);
        });

        function bindRow(row) {
            // Format ribuan pada nominal detail
            var amountInput = row.querySelector('.js-detail-amount');
            if (amountInput) {
                amountInput.addEventListener('input', function() { formatRibuan(this); });
                formatRibuan(amountInput);
            }

            // Hapus baris
            row.querySelector('.js-remove-detail').addEventListener('click', function() {
                row.remove();
            });
        }
    })();
    </script>
</x-app-layout>
