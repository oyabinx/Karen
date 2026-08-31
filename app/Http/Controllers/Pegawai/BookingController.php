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
     */
    public function index(Request $request): View
    {
        $bookings = Booking::with(['vehicle', 'originalVehicle'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('pegawai.bookings.index', ['bookings' => $bookings]);
    }
}
