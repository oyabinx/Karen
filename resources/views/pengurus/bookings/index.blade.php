<x-app-layout title="Semua Peminjaman">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Semua Peminjaman</h1>
        <p class="text-sm text-gray-500 mt-1">Monitoring seluruh peminjaman — pencarian, filter rentang tanggal, mobil, bidang, dan status.</p>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama peminjam…" class="rounded-lg border-gray-300 text-sm">
        <select name="vehicle" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua mobil</option>
            @foreach ($vehicles as $v)
                <option value="{{ $v->id }}" {{ (string) ($filters['vehicle'] ?? '') === (string) $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
            @endforeach
        </select>
        <select name="bidang" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua bidang</option>
            @foreach ($bidangList as $b)
                <option value="{{ $b->id }}" {{ (string) ($filters['bidang'] ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua status</option>
            @foreach (['dipinjam', 'menunggu_penggantian', 'dikembalikan', 'dibatalkan'] as $s)
                <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border-gray-300 text-sm w-full" title="Dari tanggal">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border-gray-300 text-sm w-full" title="Sampai tanggal">
        </div>
        <button class="px-4 rounded-lg bg-gray-800 text-white text-sm min-h-[44px]">Filter</button>
    </form>
    <p class="text-xs text-gray-400 -mt-2 mb-4">Filter tanggal menampilkan peminjaman yang MENYENTUH rentang terpilih.</p>

    {{-- Daftar kartu (mobile & desktop) --}}
    <div class="space-y-3">
        @forelse ($bookings as $b)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif ·
                            {{ $b->start_date->translatedFormat('d M') }} — {{ $b->end_date->translatedFormat('d M Y') }}
                        </p>
                        <p class="text-sm text-gray-400">{{ $b->address }} · {{ $b->purpose }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 {{
                        $b->status === 'dipinjam' ? 'bg-indigo-100 text-indigo-700' :
                        ($b->status === 'menunggu_penggantian' ? 'bg-amber-100 text-amber-700' :
                        ($b->status === 'dikembalikan' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600'))
                    }}">{{ ucfirst(str_replace('_', ' ', $b->status)) }}</span>
                </div>

                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    @if ($b->auto_returned)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Ditutup otomatis</span>
                    @endif
                    @if ($b->original_vehicle_id)
                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">Diganti dari {{ $b->originalVehicle?->name }}</span>
                    @endif
                    @if ($b->complaint)
                        <span class="px-2 py-0.5 rounded-full {{ $b->complaint->resolved ? 'bg-green-50 text-green-700' : 'bg-amber-100 text-amber-700' }}">Keluhan {{ $b->complaint->resolved ? 'selesai' : 'belum selesai' }}</span>
                    @endif
                    @if ($b->returned_at)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Kembali {{ $b->returned_at->translatedFormat('d M H:i') }}</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada peminjaman yang cocok dengan filter.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $bookings->links() }}</div>
</x-app-layout>
