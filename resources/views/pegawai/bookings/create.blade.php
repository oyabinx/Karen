<x-app-layout title="Form Peminjaman">
    <div class="mb-6">
        <a href="{{ route('pegawai.search.index', ['start_date' => $start, 'end_date' => $end]) }}" class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke hasil pencarian</a>
        <h1 class="text-2xl font-semibold mt-2">Form Peminjaman</h1>
    </div>

    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    {{-- Ringkasan mobil & rentang --}}
    <section class="bg-white rounded-xl border border-indigo-200 p-5 mb-5">
        <div class="flex items-start gap-4">
            <div class="w-24 h-20 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                @if ($vehicle->photo_path)
                    <img src="{{ Storage::url($vehicle->photo_path) }}" alt="{{ $vehicle->name }}" class="w-full h-full object-cover">
                @else
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8l2 5H6l2-5zM4 12h16v5h-2a2 2 0 11-4 0h-4a2 2 0 11-4 0H4v-5z"/></svg>
                @endif
            </div>
            <div>
                <p class="font-semibold text-lg">{{ $vehicle->name }}</p>
                <p class="text-sm text-gray-500">{{ $vehicle->plate_number }} · {{ $vehicle->year }} · {{ $vehicle->capacity }} kursi</p>
                <p class="text-sm text-indigo-700 font-medium mt-1">
                    {{ \Illuminate\Support\Carbon::parse($start)->translatedFormat('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($end)->translatedFormat('d M Y') }}
                    ({{ \Illuminate\Support\Carbon::parse($start)->diffInDays(\Illuminate\Support\Carbon::parse($end)) + 1 }} hari · penuh 00:00–24:00)
                </p>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-3">Booking hari-H tetap terhitung mulai pukul 00:00 hari ini.</p>
    </section>

    <form method="POST" action="{{ route('pegawai.bookings.store') }}" class="max-w-2xl bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf
        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
        <input type="hidden" name="start_date" value="{{ $start }}">
        <input type="hidden" name="end_date" value="{{ $end }}">

        <div class="space-y-4">
            <div>
                <x-input-label for="address" value="Alamat Tujuan" />
                <x-text-input id="address" name="address" type="text" class="block mt-1 w-full" value="{{ old('address') }}" required placeholder="mis. Gedung B Dinas X, Jl. …" />
                <x-input-error :messages="$errors->get('address')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="purpose" value="Keperluan" />
                <x-text-input id="purpose" name="purpose" type="text" class="block mt-1 w-full" value="{{ old('purpose') }}" required placeholder="mis. Rapat koordinasi" />
                <x-input-error :messages="$errors->get('purpose')" class="mt-1" />
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="{{ route('pegawai.search.index', ['start_date' => $start, 'end_date' => $end]) }}" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-center hover:bg-gray-50 min-h-[44px] leading-[44px]">Batal</a>
            <x-primary-button>Pinjam Sekarang</x-primary-button>
        </div>
    </form>

    <p class="text-sm text-gray-400 mt-4">Peminjaman langsung terkonfirmasi tanpa persetujuan. Sisa kuota {{ $quota['bidang'] }}: {{ $quota['remaining'] }} mobil.</p>
</x-app-layout>
