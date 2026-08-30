<x-app-layout title="Impor Pegawai CSV">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Impor Pegawai via CSV</h1>
        <p class="text-sm text-gray-500 mt-1">Unggah banyak akun pegawai sekaligus tanpa input satu per satu.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Langkah 1: unduh template & unggah file --}}
    <section class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h2 class="font-semibold mb-2">1. Unduh Template & Unggah</h2>
        <p class="text-sm text-gray-500 mb-4">
            Gunakan template resmi agar kolom sesuai:
            <code class="bg-gray-100 rounded px-1.5 py-0.5 text-xs">nama;email;password;bidang;seksi;role</code>
            — kolom <strong>role</strong> boleh dikosongkan (otomatis <em>pegawai</em>).
        </p>

        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('admin.users.template') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium hover:bg-gray-50 min-h-[44px]">⬇ Unduh Template CSV</a>
        </div>

        <form method="POST" action="{{ route('admin.users.import.post') }}" enctype="multipart/form-data" class="mt-4 flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="file" name="file" accept=".csv,text/csv,text/plain" required
                   class="text-sm rounded-lg border-gray-300 flex-1 min-h-[44px]">
            <x-input-error :messages="$errors->get('file')" class="sm:hidden" />
            <x-primary-button>Pratinjau Validasi</x-primary-button>
        </form>
        @if ($errors->has('file'))
            <x-input-error :messages="$errors->get('file')" class="mt-2" />
        @endif
    </section>

    {{-- Langkah 2: pratinjau hasil validasi --}}
    @if ($preview)
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <h2 class="font-semibold">2. Pratinjau ({{ $preview['total'] }} baris)</h2>
                <div class="flex gap-2 text-sm">
                    <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 font-medium">✓ {{ $preview['valid_count'] }} valid</span>
                    <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-medium">✗ {{ $preview['invalid_count'] }} bermasalah</span>
                </div>
            </div>

            {{-- Kartu per baris (berfungsi baik di mobile & desktop) --}}
            <div class="space-y-3 max-h-[28rem] overflow-y-auto pe-1">
                @foreach ($preview['rows'] as $row)
                    <div class="rounded-lg border {{ $row['valid'] ? 'border-green-200 bg-green-50/40' : 'border-red-200 bg-red-50/40' }} p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium text-sm truncate">Baris {{ $row['line'] }} — {{ $row['data']['nama'] ?: '(tanpa nama)' }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $row['data']['email'] }} · {{ $row['data']['bidang'] }} / {{ $row['data']['seksi'] }} · {{ $row['data']['role'] ?: 'pegawai' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold {{ $row['valid'] ? 'text-green-600' : 'text-red-600' }}">{{ $row['valid'] ? '✓' : '✗' }}</span>
                        </div>
                        @if (! $row['valid'])
                            <ul class="mt-2 text-xs text-red-700 list-disc list-inside space-y-0.5">
                                @foreach ($row['errors'] as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col sm:flex-row gap-3 mt-5 pt-4 border-t border-gray-200">
                @if ($preview['invalid_count'] > 0)
                    <a href="{{ route('admin.users.import.failed') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-red-300 text-red-700 text-sm font-medium hover:bg-red-50 min-h-[44px]">⬇ Unduh Laporan Gagal ({{ $preview['invalid_count'] }})</a>
                @endif
                @if ($preview['valid_count'] > 0)
                    <form method="POST" action="{{ route('admin.users.import.commit') }}" class="flex-1">
                        @csrf
                        <x-primary-button class="w-full justify-center">Impor {{ $preview['valid_count'] }} Baris Valid</x-primary-button>
                    </form>
                @endif
            </div>
        </section>
    @endif
</x-app-layout>
