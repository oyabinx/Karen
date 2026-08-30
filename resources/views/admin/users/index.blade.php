<x-app-layout title="Manajemen User">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Manajemen User</h1>
            <p class="text-sm text-gray-500">Kelola akun pegawai, pengurus & administrator.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.users.import') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium hover:bg-gray-50 min-h-[44px]">Impor CSV</a>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px]">+ Tambah User</a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Filter --}}
    <form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nama / email…" class="rounded-lg border-gray-300 text-sm lg:col-span-2">
        <select name="role" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua Role</option>
            @foreach (['admin', 'pengurus', 'pegawai'] as $r)
                <option value="{{ $r }}" {{ ($filters['role'] ?? '') === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
            @endforeach
        </select>
        <select name="bidang" class="rounded-lg border-gray-300 text-sm">
            <option value="">Semua Bidang</option>
            @foreach ($bidangList as $b)
                <option value="{{ $b->id }}" {{ (string) ($filters['bidang'] ?? '') === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm flex-1">
                <option value="">Status: Aktif</option>
                <option value="nonaktif" {{ ($filters['status'] ?? '') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                <option value="semua" {{ ($filters['status'] ?? '') === 'semua' ? 'selected' : '' }}>Semua</option>
            </select>
            <button class="px-4 rounded-lg bg-gray-800 text-white text-sm min-h-[44px]">Cari</button>
        </div>
    </form>

    {{-- TABEL DESKTOP (≥md) --}}
    <div class="hidden md:block bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wider text-gray-500">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">No. HP</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Penempatan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($users as $u)
                    <tr class="{{ $u->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 font-medium">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $u->email }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $u->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($u->role === 'pengurus' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($u->role) }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $u->seksi?->name ?? '—' }}@if ($u->seksi) <span class="text-gray-400">({{ $u->seksi->bidang->name }})</span> @endif</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $u->trashed() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ $u->trashed() ? 'Nonaktif' : 'Aktif' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $u) }}" class="text-indigo-600 hover:underline text-sm">Ubah</a>
                            @if ($u->trashed())
                                <form method="POST" action="{{ route('admin.users.restore', $u) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button class="text-green-600 hover:underline text-sm ms-3">Aktifkan</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Nonaktifkan user ini? Riwayat peminjamannya tetap tersimpan.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-sm ms-3">Nonaktifkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada user yang cocok dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- KARTU MOBILE (<md) — docs/feature/ui_responsive.md --}}
    <div class="md:hidden space-y-3">
        @forelse ($users as $u)
            <div class="bg-white rounded-xl border border-gray-200 p-4 {{ $u->trashed() ? 'opacity-60' : '' }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ $u->name }}</p>
                        <p class="text-sm text-gray-500">{{ $u->email }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium shrink-0 {{ $u->trashed() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">{{ $u->trashed() ? 'Nonaktif' : 'Aktif' }}</span>
                </div>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                    <span class="px-2 py-0.5 rounded-full font-medium {{ $u->role === 'admin' ? 'bg-purple-100 text-purple-700' : ($u->role === 'pengurus' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600') }}">{{ ucfirst($u->role) }}</span>
                    @if ($u->seksi)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $u->seksi->name }} · {{ $u->seksi->bidang->name }}</span>
                    @endif
                    @if ($u->phone)
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">{{ $u->phone }}</span>
                    @endif
                </div>
                <div class="mt-3 flex gap-4 border-t border-gray-100 pt-3">
                    <a href="{{ route('admin.users.edit', $u) }}" class="text-indigo-600 text-sm font-medium min-h-[44px] flex items-center">Ubah</a>
                    @if ($u->trashed())
                        <form method="POST" action="{{ route('admin.users.restore', $u) }}">
                            @csrf @method('PATCH')
                            <button class="text-green-600 text-sm font-medium min-h-[44px]">Aktifkan</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('Nonaktifkan user ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 text-sm font-medium min-h-[44px]">Nonaktifkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada user yang cocok.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-app-layout>
