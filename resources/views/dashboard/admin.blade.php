<x-app-layout title="Dashboard Admin">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">Dashboard <span class="font-medium">Administrator</span> — manajemen pengguna & organisasi.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Pengguna Aktif</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\User::count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Bidang</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Bidang::count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Seksi</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Seksi::count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Armada</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Vehicle::count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold mb-3">Tugas Anda</h2>
        <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
            <li>Kelola data pegawai/pengurus (tambah, ubah, nonaktifkan, atur role & seksi) — <span class="text-gray-400">segera hadir (Fase 3)</span></li>
            <li>Kelola 5 bidang & seksi beserta kuota peminjaman — <span class="text-gray-400">segera hadir (Fase 3)</span></li>
            <li>Konfigurasi integrasi Google — <span class="text-gray-400">segera hadir (Fase 4b)</span></li>
            <li>Buat event armada bidang — <span class="text-gray-400">segera hadir (Fase 4c)</span></li>
        </ul>
    </div>
</x-app-layout>
