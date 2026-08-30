<x-app-layout title="Penggantian Mobil">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Penggantian Mobil</h1>
        <p class="text-sm text-gray-500 mt-1">Peminjaman yang menabrak jadwal maintenance — pilih mobil pengganti atau batalkan.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="space-y-3">
        @forelse ($bookings as $b)
            <a href="{{ route('pengurus.replacements.show', $b) }}"
               class="block bg-white rounded-xl border-2 border-amber-200 p-4 hover:border-amber-400 transition-colors">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif
                        </p>
                        <p class="text-sm text-gray-500">
                            {{ $b->start_date->translatedFormat('d M') }} — {{ $b->end_date->translatedFormat('d M Y') }} · {{ $b->address }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700 shrink-0">Menunggu pengganti →</span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Tidak ada peminjaman yang menunggu penggantian. 🎉
            </div>
        @endforelse
    </div>
</x-app-layout>
