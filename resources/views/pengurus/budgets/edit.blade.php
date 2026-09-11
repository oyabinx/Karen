<x-app-layout title="Anggaran — {{ $vehicle->name }}">
    <div class="mb-6">
        <a href="{{ route('pengurus.vehicles.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke kendaraan</a>
        <div class="flex flex-wrap items-end justify-between gap-3 mt-2">
            <div>
                <h1 class="text-2xl font-semibold">Anggaran Maintenance — {{ $vehicle->name }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $vehicle->plate_number }} · tahun pembuatan {{ $vehicle->year }} · alokasi tiap tahun bersifat independen</p>
            </div>
            {{-- Pemilih tahun anggaran: 2026 dan 2027 (dst.) punya alokasi terpisah --}}
            <div class="flex items-center gap-1 bg-white rounded-lg border border-gray-300 p-1">
                <a href="{{ route('pengurus.budgets.edit', ['vehicle' => $vehicle, 'year' => $year - 1]) }}"
                   class="px-3 py-1.5 rounded-md hover:bg-gray-100 text-gray-600 min-h-[44px] flex items-center" aria-label="Tahun sebelumnya">◀</a>
                <span class="px-3 font-semibold">{{ $year }}</span>
                <a href="{{ route('pengurus.budgets.edit', ['vehicle' => $vehicle, 'year' => $year + 1]) }}"
                   class="px-3 py-1.5 rounded-md hover:bg-gray-100 text-gray-600 min-h-[44px] flex items-center" aria-label="Tahun berikutnya">▶</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Form anggaran --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-4">Atur Total Anggaran 4 Pos</h2>

            <form method="POST" action="{{ route('pengurus.budgets.update', $vehicle) }}" class="space-y-4">
                @csrf @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm">
                        <span class="text-gray-600">Tahun anggaran</span>
                        <input type="number" name="year" value="{{ $year }}" min="2000" max="2100" required class="mt-1 block w-full rounded-lg border-gray-300">
                    </label>
                </div>

                @foreach (\App\Models\VehicleBudget::POSTS as $post)
                    <div>
                        <x-input-label for="amounts_{{ $post }}" value="{{ ucfirst(str_replace('_', ' ', $post)) }}" />
                        <div class="flex items-center mt-1">
                            <span class="px-3 py-2 rounded-s-lg bg-gray-100 border border-e-0 border-gray-300 text-sm text-gray-500">Rp</span>
                            <input id="amounts_{{ $post }}" type="text" inputmode="numeric"
                                   name="amounts[{{ $post }}]"
                                   value="{{ old('amounts.'.$post, number_format((float)($budgets[$post]->amount ?? 0), 0, ',', '.')) }}"
                                   required
                                   class="flex-1 rounded-none rounded-e-lg border-gray-300 text-sm js-thousand-sep"
                                   placeholder="0">
                        </div>
                        <x-input-error :messages="$errors->get('amounts.'.$post)" class="mt-1" />
                    </div>
                @endforeach

                <div class="flex justify-end pt-2">
                    <x-primary-button>Simpan Anggaran</x-primary-button>
                </div>
            </form>
        </section>

        {{-- Ringkasan sisa --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold">Realisasi & Sisa ({{ $year }})</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b">
                            <th class="py-2">Pos</th>
                            <th class="py-2 text-right">Anggaran</th>
                            <th class="py-2 text-right">Realisasi (termasuk pajak)</th>
                            <th class="py-2 text-right">Sisa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($summary as $post => $s)
                            <tr>
                                <td class="py-2.5">{{ ucfirst(str_replace('_', ' ', $post)) }}</td>
                                <td class="py-2.5 text-right">Rp {{ number_format($s['anggaran'], 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right">Rp {{ number_format($s['realisasi'], 0, ',', '.') }}</td>
                                <td class="py-2.5 text-right font-medium {{ $s['sisa'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    Rp {{ number_format($s['sisa'], 0, ',', '.') }}
                                    @if ($s['sisa'] < 0) ⚠ @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 font-semibold">
                        <tr>
                            <td class="py-2.5">Total</td>
                            <td class="py-2.5 text-right">Rp {{ number_format(collect($summary)->sum('anggaran'), 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right">Rp {{ number_format(collect($summary)->sum('realisasi'), 0, ',', '.') }}</td>
                            <td class="py-2.5 text-right {{ collect($summary)->sum('sisa') < 0 ? 'text-red-600' : '' }}">Rp {{ number_format(collect($summary)->sum('sisa'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if (collect($summary)->contains(fn ($s) => $s['sisa'] < 0))
                <p class="mt-3 text-xs text-red-600">⚠ Ada pos yang realisasinya melebihi anggaran — peringatan saja, tidak memblokir.</p>
            @endif
        </section>
    </div>

    {{-- Pemisah ribuan live (UAT 04-A2): 1000000 → 1.000.000 --}}
    <script>
        document.querySelectorAll('.js-thousand-sep').forEach(function (input) {
            input.addEventListener('input', function () {
                var digits = this.value.replace(/\D/g, '');
                this.value = digits ? parseInt(digits).toLocaleString('id-ID') : '';
            });
            // Trigger awal untuk nilai yang sudah ada
            input.dispatchEvent(new Event('input'));
        });
    </script>
</x-app-layout>
