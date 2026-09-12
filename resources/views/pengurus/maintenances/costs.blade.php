@php($hasNota = $costs->max('raw_amount') > 0)
<x-app-layout title="{{ $hasNota ? 'Edit Nota' : 'Input Nota' }} — Maintenance #{{ $maintenance->id }}">
    <div class="mb-4">
        <a href="{{ route('pengurus.maintenances.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke jadwal</a>
        <h1 class="text-2xl font-semibold mt-2">{{ $hasNota ? 'Edit Nota Bengkel' : 'Input Nota Bengkel' }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $maintenance->vehicle->name }} ({{ $maintenance->vehicle->plate_number }}) ·
            {{ $maintenance->start_date->translatedFormat('d M') }}–{{ $maintenance->end_date->translatedFormat('d M Y') }}
        </p>
        <p class="text-sm text-gray-500">Isi rincian per pos — total otomatis dihitung dan dikalikan dengan koefisien pajak.</p>
    </div>

    <form method="POST" action="{{ route('pengurus.maintenances.nota', $maintenance) }}" id="nota-form"
          data-koefisien="{{ \App\Services\BudgetService::koefisienPajak() }}">
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

        {{-- 4 Pos — mobile-first: stack vertikal --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            @foreach (\App\Models\VehicleBudget::POSTS as $post)
                @php($existing = $costs[$post] ?? null)
                <section class="bg-white rounded-xl border border-gray-200 p-4" data-post="{{ $post }}">
                    <h3 class="font-semibold text-sm mb-3">{{ ucfirst(str_replace('_', ' ', $post)) }}</h3>

                    {{-- Rincian baris (input utama) --}}
                    <div class="js-details space-y-2">
                        @if ($existing && $existing->details->isNotEmpty())
                            @foreach ($existing->details as $detail)
                                {{-- Mobile: 2 baris (rincian / Rp+hapus) — desktop: 1 baris (rev UAT 04) --}}
                                <div class="js-detail-row flex flex-col sm:flex-row gap-2 sm:items-center">
                                    <input type="text" name="details[{{ $post }}][]" value="{{ $detail->description }}"
                                           placeholder="mis. Ganti oli mesin" maxlength="255"
                                           class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm min-h-[44px]">
                                    <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                                        <div class="flex items-center flex-1 sm:w-36">
                                            <span class="px-2 py-2 rounded-s-lg bg-gray-100 border border-e-0 border-gray-300 text-xs text-gray-400">Rp</span>
                                            <input type="text" inputmode="numeric" name="detail_amounts[{{ $post }}][]" value="{{ number_format((float)$detail->amount, 0, ',', '.') }}"
                                                   placeholder="0" class="w-full rounded-e-lg border-gray-300 border-s-0 text-sm min-h-[44px] js-detail-amount">
                                        </div>
                                        <button type="button" class="js-remove-detail text-red-400 hover:text-red-600 p-2 min-h-[44px] min-w-[44px] shrink-0" aria-label="Hapus">✕</button>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" class="js-add-detail mt-2 w-full px-3 py-2 rounded-lg border-2 border-dashed border-gray-300 text-sm text-gray-500 hover:border-indigo-300 hover:text-indigo-600 min-h-[44px] transition-colors">
                        + Tambah Rincian
                    </button>

                    {{-- Total per pos: AUTO-SUM dari rincian (readonly) --}}
                    <div class="mt-4 pt-3 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500">Subtotal</span>
                            <span class="js-subtotal font-semibold text-gray-700">Rp 0</span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-sm text-gray-500">Total <span class="text-xs">(dikalikan koefisien pajak)</span></span>
                            <span class="js-total font-bold text-emerald-700 text-lg">Rp 0</span>
                        </div>
                        {{-- Hidden input untuk server: total raw dari auto-sum --}}
                        <input type="hidden" name="costs[{{ $post }}]" value="0" class="js-pos-total">
                    </div>
                </section>
            @endforeach
        </div>

        <x-input-error :messages="$errors->get('details')" class="mb-4" />

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pb-4">
            <a href="{{ route('pengurus.maintenances.index') }}" class="w-full sm:w-auto px-4 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 text-center min-h-[40px] leading-[40px]">Batal</a>
            <button type="submit" class="px-6 py-2 rounded-lg {{ $hasNota ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }} text-white text-sm font-semibold min-h-[48px]">
                {{ $hasNota ? 'Selesai Edit' : 'Simpan Nota & Generate Dokumen' }}
            </button>
        </div>
    </form>

    <script>
    (function() {
        var koefisien = parseFloat(document.getElementById('nota-form').dataset.koefisien);

        function parseAngka(str) {
            var d = (str || '').replace(/\D/g, '');
            return d ? parseInt(d) : 0;
        }

        function formatRp(val) {
            return 'Rp ' + val.toLocaleString('id-ID');
        }

        // Recalculate subtotal + total per section
        function recalc(section) {
            var sum = 0;
            section.querySelectorAll('.js-detail-amount').forEach(function(input) {
                sum += parseAngka(input.value);
            });

            var subtotalEl = section.querySelector('.js-subtotal');
            var totalEl = section.querySelector('.js-total');
            var hiddenInput = section.querySelector('.js-pos-total');

            subtotalEl.textContent = formatRp(sum);
            totalEl.textContent = formatRp(Math.round(sum * koefisien));
            hiddenInput.value = sum;
        }

        // Format ribuan live
        function formatRibuan(input) {
            var digits = input.value.replace(/\D/g, '');
            input.value = digits ? parseInt(digits).toLocaleString('id-ID') : '';
        }

        function bindRow(section, row) {
            var amountInput = row.querySelector('.js-detail-amount');
            if (amountInput) {
                amountInput.addEventListener('input', function() {
                    formatRibuan(this);
                    recalc(section);
                });
                formatRibuan(amountInput);
            }

            row.querySelector('.js-remove-detail').addEventListener('click', function() {
                row.remove();
                recalc(section);
            });
        }

        document.querySelectorAll('[data-post]').forEach(function(section) {
            // Bind existing rows
            section.querySelectorAll('.js-detail-row').forEach(function(row) { bindRow(section, row); });

            // Add row
            section.querySelector('.js-add-detail').addEventListener('click', function() {
                var post = section.dataset.post;
                var row = document.createElement('div');
                row.className = 'js-detail-row flex flex-col sm:flex-row gap-2 sm:items-center';
                row.innerHTML =
                    '<input type="text" name="details[' + post + '][]" value="" placeholder="mis. Ganti oli mesin" maxlength="255" class="flex-1 min-w-0 rounded-lg border-gray-300 text-sm min-h-[44px]">' +
                    '<div class="flex items-center gap-2 w-full sm:w-auto shrink-0">' +
                    '<div class="flex items-center flex-1 sm:w-36">' +
                    '<span class="px-2 py-2 rounded-s-lg bg-gray-100 border border-e-0 border-gray-300 text-xs text-gray-400">Rp</span>' +
                    '<input type="text" inputmode="numeric" name="detail_amounts[' + post + '][]" value="" placeholder="0" class="w-full rounded-e-lg border-gray-300 border-s-0 text-sm min-h-[44px] js-detail-amount">' +
                    '</div>' +
                    '<button type="button" class="js-remove-detail text-red-400 hover:text-red-600 p-2 min-h-[44px] min-w-[44px] shrink-0" aria-label="Hapus">✕</button>' +
                    '</div>';
                section.querySelector('.js-details').appendChild(row);
                bindRow(section, row);
                row.querySelector('input[type="text"]').focus();
            });

            // Initial calc
            recalc(section);
        });
    })();
    </script>
</x-app-layout>
