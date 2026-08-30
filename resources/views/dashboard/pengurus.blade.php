<x-app-layout title="Dashboard Pengurus">
    @include('partials.phone-reminder')

    <h1 class="text-2xl font-semibold mb-1">Selamat datang, {{ $user->name }}</h1>
    <p class="text-gray-500 mb-6">Dashboard <span class="font-medium">Pengurus</span> — kendaraan, maintenance, monitoring & anggaran.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Total Armada</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Vehicle::count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Bisa Dipinjam</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Vehicle::where('status', 'bisa_dipinjam')->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Maintenance Aktif</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Maintenance::where('status', 'terjadwal')->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-sm text-gray-500">Peminjaman Berjalan</p>
            <p class="text-3xl font-semibold mt-1">{{ \App\Models\Booking::where('status', 'dipinjam')->count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold mb-3">Tugas Anda</h2>
        <ul class="text-sm text-gray-600 space-y-2 list-disc list-inside">
            <li>Kelola data kendaraan & jadwal maintenance — <span class="text-gray-400">segera hadir (Fase 4)</span></li>
            <li>Anggaran maintenance 4 pos + generate bend26 & draft nota — <span class="text-gray-400">segera hadir (Fase 4b)</span></li>
            <li>Event armada bidang & penggantian mobil — <span class="text-gray-400">segera hadir (Fase 4c)</span></li>
            <li>Monitoring peminjaman, keluhan & laporan — <span class="text-gray-400">segera hadir (Fase 5–8)</span></li>
        </ul>
    </div>
</x-app-layout>
