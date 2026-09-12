<x-app-layout title="Dashboard Admin">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">Dashboard <span class="font-medium">Administrator</span> — manajemen pengguna, organisasi & integrasi.</p>

    {{-- Ringkasan total --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Pengguna</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['totals']['user'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Bidang</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['totals']['bidang'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Seksi</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['totals']['seksi'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Armada</p>
            <p class="text-3xl font-semibold mt-1">{{ $data['totals']['armada'] }}</p>
        </div>
    </div>

    {{-- Armada hari ini: siapa pakai mobil apa hari ini (kurangi pertanyaan ke pengurus) --}}
    <div class="mb-6">
        @include('dashboard.partials.armada-hari-ini')
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Rincian per role & per bidang --}}
        <section class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="font-semibold mb-4">Struktur Organisasi & Anggota</h2>

            <div class="flex flex-wrap gap-2 mb-4">
                @foreach ($data['perRole'] as $role => $total)
                    <span class="px-3 py-1.5 rounded-full bg-gray-100 text-sm">
                        {{ ucfirst($role) }}: <strong>{{ $total }}</strong>
                    </span>
                @endforeach
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-gray-400 border-b">
                            <th class="py-2">Bidang</th>
                            <th class="py-2 text-center">Kuota</th>
                            <th class="py-2 text-center">Seksi</th>
                            <th class="py-2 text-center">Anggota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($data['perBidang'] as $b)
                            <tr>
                                <td class="py-2.5">{{ $b->name }}</td>
                                <td class="py-2.5 text-center">{{ $b->max_active_bookings }} mobil</td>
                                <td class="py-2.5 text-center">{{ $b->jumlah_seksi }}</td>
                                <td class="py-2.5 text-center font-medium">{{ $b->jumlah_user }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:underline">Manajemen User →</a>
                <a href="{{ route('admin.bidang.index') }}" class="text-indigo-600 hover:underline">Bidang & Seksi →</a>
                <a href="{{ route('admin.integrasi.google') }}" class="text-indigo-600 hover:underline">Integrasi Google →</a>
                <a href="{{ route('pengurus.events.index') }}" class="text-indigo-600 hover:underline">Event Armada →</a>
            </div>
        </section>

        {{-- Kesehatan scheduler (taskplan Fase 10.3 — antisipasi) --}}
        <section class="bg-white rounded-xl border {{ collect($data['scheduler'])->where('buruk', true)->isNotEmpty() ? 'border-red-300' : 'border-gray-200' }} p-6">
            <h2 class="font-semibold mb-1">Kesehatan Scheduler</h2>
            <p class="text-xs text-gray-400 mb-4">Tugas terjadwal membutuhkan pemicu eksternal (cron/systemd) — lihat README bagian “Scheduler WAJIB dipasang”.</p>

            <div class="space-y-3">
                @foreach (['autoReturn' => 'Pengembalian otomatis (harian 00:01)', 'autoFinish' => 'Penutupan event (harian 00:02)', 'sync' => 'Sinkron Google Sheets'] as $key => $label)
                    @php($s = $data['scheduler'][$key])
                    <div class="flex items-center justify-between gap-3 rounded-lg border {{ $s['buruk'] ? 'border-red-200 bg-red-50' : 'border-gray-200' }} px-4 py-3">
                        <div>
                            <p class="text-sm font-medium {{ $s['buruk'] ? 'text-red-700' : 'text-gray-800' }}">{{ $label }}</p>
                            <p class="text-xs text-gray-500">
                                @if ($s['waktu'])
                                    terakhir: {{ \Illuminate\Support\Carbon::parse($s['waktu'])->translatedFormat('d M Y H:i') }}
                                @else
                                    belum pernah berjalan {{ $key === 'sync' ? '(integrasi mungkin nonaktif)' : '' }}
                                @endif
                            </p>
                        </div>
                        @if ($s['buruk'])
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 shrink-0">⚠ Pemicu kemungkinan mati</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 shrink-0">OK</span>
                        @endif
                    </div>
                @endforeach
            </div>

            @unless ($data['scheduler']['produksi'])
                <p class="text-xs text-gray-400 mt-3">Mode development: peringatan “mati” hanya aktif di production.</p>
            @endunless
        </section>
    </div>
</x-app-layout>
