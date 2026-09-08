<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pegawai\BookingStoreRequest;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Peminjaman pribadi — pegawai DAN pengurus.
     */
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    /**
     * Form booking: ringkasan mobil + rentang, isi alamat & keperluan.
     */
    public function create(Request $request): View
    {
        $validated = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
        ]);

        return view('pegawai.bookings.create', [
            'vehicle' => Vehicle::findOrFail($validated['vehicle_id']),
            'start' => $validated['start_date'],
            'end' => $validated['end_date'],
            'quota' => $this->bookings->quotaInfo($request->user()),
        ]);
    }

    public function store(BookingStoreRequest $request): RedirectResponse
    {
        try {
            $booking = $this->bookings->create(
                $request->user(),
                Vehicle::findOrFail($request->input('vehicle_id')),
                $request->input('start_date'),
                $request->input('end_date'),
                $request->input('address'),
                $request->input('purpose'),
            );
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('pegawai.bookings.index')
            ->with('success', "Peminjaman {$booking->vehicle->name} ({$booking->start_date->translatedFormat('d M')}–{$booking->end_date->translatedFormat('d M Y')}) berhasil dibuat.");
    }

    /**
     * Riwayat peminjaman pribadi (badge status, penanda otomatis &
     * penggantian mobil).
     *
     * URUTAN (revisi user): peminjaman AKTIF selalu paling atas
     * (terdekat dulu) — studi kasus: booking 22–24 Sep yang dibatalkan
     * tidak boleh menindih booking aktif 14–15 Sep. Riwayat di bawahnya
     * diurutkan terbaru dulu.
     *
     * FILTER BULAN (revisi user F7): default hanya bulan berjalan —
     * agar tidak membebani pengguna; bulan sebelumnya via dropdown.
     * Booking AKTIF selalu tampil meski mulai bulan lalu.
     */
    public function index(Request $request): View
    {
        $bulan = $request->input('bulan', now()->format('Y-m'));
        $awalBulan = Carbon::parse($bulan.'-01')->startOfMonth();
        $akhirBulan = $awalBulan->copy()->endOfMonth();

        $bookings = Booking::with(['vehicle', 'originalVehicle'])
            ->where('user_id', $request->user()->id)
            // Aktif selalu tampil; non-aktif hanya dalam bulan terpilih
            ->where(function ($q) use ($awalBulan, $akhirBulan) {
                $q->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
                    ->orWhereBetween('start_date', [$awalBulan, $akhirBulan]);
            })
            ->orderByRaw("CASE WHEN status IN ('dipinjam', 'menunggu_penggantian') THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status IN ('dipinjam', 'menunggu_penggantian') THEN UNIX_TIMESTAMP(start_date) ELSE -UNIX_TIMESTAMP(start_date) END")
            ->paginate(10);

        // Daftar bulan untuk filter (6 bulan terakhir termasuk seeder)
        $bulanPilihan = collect(range(0, 5))
            ->map(fn ($i) => now()->subMonths($i)->format('Y-m'))
            ->mapWithKeys(fn ($b) => [$b => Carbon::parse($b.'-01')->translatedFormat('F Y')]);

        return view('pegawai.bookings.index', [
            'bookings' => $bookings,
            'bulanAktif' => $bulan,
            'bulanPilihan' => $bulanPilihan,
        ]);
    }
}
