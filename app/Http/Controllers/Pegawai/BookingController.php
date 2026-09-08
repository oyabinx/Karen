<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pegawai\BookingStoreRequest;
use App\Models\Booking;
use App\Models\Vehicle;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     */
    public function index(Request $request): View
    {
        $bookings = Booking::with(['vehicle', 'originalVehicle'])
            ->where('user_id', $request->user()->id)
            ->orderByRaw("CASE WHEN status IN ('dipinjam', 'menunggu_penggantian') THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN status IN ('dipinjam', 'menunggu_penggantian') THEN UNIX_TIMESTAMP(start_date) ELSE -UNIX_TIMESTAMP(start_date) END")
            ->paginate(10);

        return view('pegawai.bookings.index', ['bookings' => $bookings]);
    }
}
