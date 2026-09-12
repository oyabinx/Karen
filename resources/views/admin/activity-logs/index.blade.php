<x-app-layout title="Log Aktivitas">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Log Aktivitas</h1>
        <p class="text-sm text-gray-500 mt-1">Siapa mengubah apa, kapan — seluruh perubahan data penting (kendaraan, jadwal, anggaran, peminjaman, user, konfigurasi). Entri "Sistem (otomatis)" berasal dari scheduler.</p>
    </div>

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari deskripsi / objek…" class="rounded-lg border-gray-300 text-sm">
        <select name="user" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua pelaku</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" {{ (string) ($filters['user'] ?? '') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
            @endforeach
        </select>
        <select name="action" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua aksi</option>
            @foreach (['created' => 'Menambah', 'updated' => 'Mengubah', 'deleted' => 'Menghapus', 'restored' => 'Mengaktifkan kembali', 'system' => 'Sistem'] as $k => $label)
                <option value="{{ $k }}" {{ ($filters['action'] ?? '') === $k ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="model" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua objek</option>
            @foreach ($modelTypes as $mt)
                <option value="{{ $mt }}" {{ ($filters['model'] ?? '') === $mt ? 'selected' : '' }}>{{ class_basename($mt) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="rounded-lg border-gray-300 text-sm w-full" title="Dari">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="rounded-lg border-gray-300 text-sm w-full" title="Sampai">
        </div>
        <button class="px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold min-h-[48px]">Filter</button>
    </form>

    {{-- Daftar log --}}
    <div class="space-y-2">
        @forelse ($logs as $log)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm">
                            <strong>{{ $log->pelaku() }}</strong>
                            <span class="px-2 py-0.5 mx-1 rounded-full text-xs font-semibold {{ match($log->action) {
                                'created' => 'bg-green-100 text-green-700',
                                'updated' => 'bg-indigo-100 text-indigo-700',
                                'deleted' => 'bg-red-100 text-red-700',
                                'restored' => 'bg-teal-100 text-teal-700',
                                default => 'bg-gray-200 text-gray-600',
                            } }}">{{ $log->aksiLabel() }}</span>
                            <span class="text-gray-700">{{ $log->model_label ?? class_basename($log->model_type ?? '—') }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $log->created_at->translatedFormat('d M Y H:i') }} · {{ class_basename($log->model_type ?? 'Sistem') }}@if ($log->model_id) #{{ $log->model_id }}@endif
                        </p>
                    </div>
                    @if ($log->changes)
                        <details class="w-full sm:w-auto">
                            <summary class="cursor-pointer select-none text-xs font-medium text-indigo-600 hover:underline">Detail perubahan</summary>
                            <div class="mt-2 overflow-x-auto">
                                <table class="text-xs border rounded-lg">
                                    <thead class="bg-gray-50 text-left">
                                        <tr><th class="px-2 py-1">Kolom</th><th class="px-2 py-1">Nilai lama</th><th class="px-2 py-1">Nilai baru</th></tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        @foreach ($log->changes as $kolom => $isi)
                                            <tr>
                                                <td class="px-2 py-1 font-medium">{{ $kolom }}</td>
                                                <td class="px-2 py-1 text-gray-500">{{ $isi['lama'] ?? '—' }}</td>
                                                <td class="px-2 py-1">{{ $isi['baru'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endif
                </div>
                @if (! $log->changes)
                    <p class="text-xs text-gray-500 mt-1">{{ $log->description }}</p>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada aktivitas yang cocok.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
