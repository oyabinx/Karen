<x-app-layout title="Dokumen">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Dokumen</h1>
        <p class="text-sm text-gray-500 mt-1">Hasil generate: bend26, draft nota per pos, dan kartu inventaris pemeliharaan.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Filter kendaraan + generate kartu --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
        <div class="flex-1">
            <x-input-label for="vehicle" value="Filter kendaraan" />
            <select id="vehicle" name="vehicle" class="block mt-1 w-full rounded-lg border-gray-300 text-sm" onchange="this.form.submit()">
                <option value="">Semua kendaraan</option>
                @foreach ($vehicles as $v)
                    <option value="{{ $v->id }}" {{ $vehicle?->id === $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
        @if ($vehicle)
            <button type="submit" formaction="{{ route('pengurus.documents.kartu', $vehicle) }}" formmethod="POST"
                    class="px-4 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px] whitespace-nowrap">
                @csrf Generate Kartu Inventaris {{ $vehicle->name }}
            </button>
        @endif
    </form>

    {{-- Daftar dokumen --}}
    <div class="space-y-3">
        @forelse ($documents as $d)
            <div class="bg-white rounded-xl border border-gray-200 p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-medium">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $d->type === 'bend26' ? 'bg-indigo-100 text-indigo-700' : ($d->type === 'kartu_inventaris' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $d->type === 'bend26' ? 'BEND26' : ($d->type === 'kartu_inventaris' ? 'Kartu Inventaris' : 'Draft Nota — '.ucfirst(str_replace('_', ' ', $d->post))) }}
                        </span>
                        {{ $d->vehicle?->name ?? '—' }}
                        @if ($d->maintenance) <span class="text-gray-400">· maintenance #{{ $d->maintenance->id }} {{ $d->maintenance->workshop_name ? '('.$d->maintenance->workshop_name.')' : '' }}</span> @endif
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">v{{ $d->version }} · digenerate {{ $d->created_at->translatedFormat('d M Y H:i') }}</p>
                </div>
                <a href="{{ route('pengurus.documents.download', $d) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium hover:bg-gray-50 min-h-[44px] flex items-center">⬇ Unduh PDF</a>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Belum ada dokumen. Input nota pada maintenance yang selesai, atau generate kartu inventaris dari halaman anggaran kendaraan.
            </div>
        @endforelse
    </div>
</x-app-layout>
