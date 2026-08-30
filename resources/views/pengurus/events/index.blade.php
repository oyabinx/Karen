<x-app-layout title="Event Armada Bidang">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-semibold">Event Armada Bidang</h1>
            <p class="text-sm text-gray-500">Pemakaian banyak mobil sekaligus untuk satu bidang — bebas dari kuota, durasi fleksibel (boleh &gt;3 hari).</p>
        </div>
        <a href="{{ route('pengurus.events.create') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 min-h-[44px]">+ Buat Event</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="space-y-3">
        @forelse ($events as $e)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $e->name }} <span class="text-gray-400 font-normal">· {{ $e->bidang->name }}</span></p>
                        <p class="text-sm text-gray-500">
                            {{ $e->start_date->translatedFormat('d M Y') }} — {{ $e->end_date->translatedFormat('d M Y') }}
                            ({{ $e->start_date->diffInDays($e->end_date) + 1 }} hari) · {{ $e->armada_count }} armada · oleh {{ $e->creator->name }}
                        </p>
                        @if ($e->note)<p class="text-sm text-gray-400">{{ $e->note }}</p>@endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($e->unresolved_count > 0 && $e->status === 'terjadwal')
                            <a href="{{ route('pengurus.events.conflicts', $e) }}" class="px-3 py-1.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">⚠ {{ $e->unresolved_count }} konflik — atur pengganti</a>
                        @endif
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $e->status === 'terjadwal' ? 'bg-indigo-100 text-indigo-700' : ($e->status === 'selesai' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-600') }}">{{ ucfirst($e->status) }}</span>
                    </div>
                </div>

                @if ($e->status === 'terjadwal')
                    <div class="mt-3 pt-3 border-t border-gray-100 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        <a href="{{ route('pengurus.events.conflicts', $e) }}" class="text-indigo-600 hover:underline">Konflik & pengganti</a>
                        @if ($e->unresolved_count === 0)
                            <form method="POST" action="{{ route('pengurus.events.confirm', $e) }}">
                                @csrf @method('PATCH')
                                <button class="text-green-600 hover:underline">Konfirmasi</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('pengurus.events.cancel', $e) }}" onsubmit="return confirm('Batalkan event ini? Armada lepas; peminjaman yang belum diganti kembali ke mobil semula.')">
                            @csrf @method('PATCH')
                            <button class="text-red-600 hover:underline">Batalkan Event</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">Belum ada event — buat event pertama untuk pemakaian armada di atas kuota.</div>
        @endforelse
    </div>
</x-app-layout>
