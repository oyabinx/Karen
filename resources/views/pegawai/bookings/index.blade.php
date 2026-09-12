<x-app-layout title="Peminjaman Saya">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Peminjaman Saya</h1>
            <p class="text-sm text-gray-500">Riwayat & status seluruh peminjaman Anda.</p>
        </div>
        <a href="{{ route('pegawai.search.index') }}" class="inline-flex items-center px-5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 min-h-[48px]">+ Pinjam Mobil</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    {{-- Filter bulan (revisi user F7): default bulan berjalan, data lama via dropdown --}}
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <span class="text-sm text-gray-500">Menampilkan riwayat:</span>
        <form method="GET" class="flex items-center gap-2">
            <select name="bulan" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 text-sm min-h-[44px]">
                @foreach ($bulanPilihan as $nilai => $label)
                    <option value="{{ $nilai }}" {{ $bulanAktif === $nilai ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
        <span class="text-xs text-gray-400">— peminjaman aktif selalu tampil</span>
    </div>

    {{-- Kartu (mobile & desktop) --}}
    <div class="space-y-3">
        @forelse ($bookings as $b)
            <div class="bg-white rounded-xl border {{ in_array($b->status, ['dipinjam', 'menunggu_penggantian']) ? 'border-2 '.($b->status === 'dipinjam' ? 'border-indigo-200' : 'border-amber-300') : 'border-gray-200' }} p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $b->start_date->translatedFormat('d M Y') }} — {{ $b->end_date->translatedFormat('d M Y') }}
                            ({{ $b->start_date->diffInDays($b->end_date) + 1 }} hari)
                        </p>
                        <p class="text-sm text-gray-400">{{ $b->address }} · {{ $b->purpose }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 {{
                        $b->status === 'dipinjam' ? 'bg-indigo-100 text-indigo-700' :
                        ($b->status === 'menunggu_penggantian' ? 'bg-amber-100 text-amber-700' :
                        ($b->status === 'dikembalikan' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'))
                    }}">
                        {{ ucfirst(str_replace('_', ' ', $b->status)) }}
                    </span>
                </div>

                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    @if ($b->auto_returned)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Dikembalikan otomatis oleh sistem</span>
                    @endif
                    @if ($b->original_vehicle_id)
                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Diganti dari {{ $b->originalVehicle?->name ?? 'unit lama' }}</span>
                    @endif
                    @if ($b->returned_at)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Dikembalikan {{ $b->returned_at->translatedFormat('d M Y H:i') }}</span>
                    @endif
                    @if ($b->status === 'dibatalkan' && $b->cancelled_at)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Dibatalkan {{ $b->cancelled_at->translatedFormat('d M Y H:i') }}</span>
                    @endif
                </div>

                @if ($b->status === 'dipinjam')
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        @include('partials.finish-booking-modal', ['booking' => $b])
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
                Belum ada peminjaman. <a href="{{ route('pegawai.search.index') }}" class="text-indigo-600 underline">Cari mobil tersedia</a> untuk mulai meminjam.
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $bookings->links() }}</div>
</x-app-layout>
