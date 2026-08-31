<x-app-layout title="Keluhan Unit">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Keluhan Unit</h1>
        <p class="text-sm text-gray-500 mt-1">Keluhan dari form pengembalian pegawai — bersifat catatan; mobil tidak otomatis disisihkan. Bila perlu perbaikan, buat jadwal maintenance untuk unitnya.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif

    <div class="flex gap-2 mb-4 text-sm">
        @foreach (['belum' => 'Belum Selesai', 'selesai' => 'Selesai'] as $key => $label)
            <a href="{{ route('pengurus.complaints.index', ['status' => $key]) }}"
               class="px-3.5 py-2 rounded-full font-medium min-h-[44px] flex items-center {{ $status === $key ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-300 text-gray-600 hover:bg-gray-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($complaints as $c)
            @php($b = $c->booking)
            <div class="bg-white rounded-xl border {{ $c->resolved ? 'border-gray-200 opacity-70' : 'border-2 border-amber-200' }} p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $b->vehicle->name }} <span class="text-gray-400 font-normal">({{ $b->vehicle->plate_number }})</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $b->user->name }}@if ($b->user->seksi) · {{ $b->user->seksi->bidang->name }}@endif ·
                            peminjaman {{ $b->start_date->translatedFormat('d M Y') }} (dikembalikan {{ optional($b->returned_at)->translatedFormat('d M Y H:i') ?? '-' }})
                        </p>
                        <p class="text-sm text-gray-500 mt-1">{{ $b->address }} · {{ $b->purpose }}</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold shrink-0 {{ $c->resolved ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $c->resolved ? 'Selesai' : 'Belum selesai' }}</span>
                </div>

                <p class="mt-3 text-sm bg-amber-50 border border-amber-100 rounded-lg px-4 py-3">“{{ $c->message }}”</p>

                <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    @if ($c->resolved)
                        <form method="POST" action="{{ route('pengurus.complaints.reopen', $c) }}">
                            @csrf @method('PATCH')
                            <button class="text-gray-600 hover:underline">Buka kembali</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('pengurus.complaints.resolve', $c) }}">
                            @csrf @method('PATCH')
                            <button class="text-green-600 hover:underline">Tandai selesai</button>
                        </form>
                    @endif
                    <a href="{{ route('pengurus.maintenances.index') }}" class="text-indigo-600 hover:underline">Jadwalkan maintenance (manual) →</a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Tidak ada keluhan {{ $status }}. 🎉</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $complaints->links() }}</div>
</x-app-layout>
