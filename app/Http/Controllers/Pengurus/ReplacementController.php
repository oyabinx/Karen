<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ReplacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReplacementController extends Controller
{
    /**
     * Konfirmasi mobil pengganti untuk booking menabrak maintenance
     * (docs/feature/penggantian_mobil.md) — hanya pengurus.
     */
    public function __construct(
        private readonly ReplacementService $replacements,
    ) {}

    public function index(): View
    {
        $bookings = Booking::with(['user.seksi.bidang', 'vehicle'])
            ->where('status', Booking::STATUS_MENUNGGU_PENGGANTIAN)
            ->orderBy('start_date')
            ->get();

        return view('pengurus.replacements.index', ['bookings' => $bookings]);
    }

    public function show(Booking $booking): View|RedirectResponse
    {
        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return redirect()->route('pengurus.replacements.index')
                ->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        $booking->load(['user.seksi.bidang', 'vehicle']);

        return view('pengurus.replacements.show', [
            'booking' => $booking,
            'candidates' => $this->replacements->candidates($booking),
        ]);
    }

    public function assign(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
        ]);

        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return redirect()->route('pengurus.replacements.index')
                ->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        try {
            $replacement = \App\Models\Vehicle::findOrFail($request->input('vehicle_id'));
            $this->replacements->assign($booking, $replacement);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('pengurus.replacements.index')
            ->with('success', "Pengganti ditetapkan: {$replacement->name}.");
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return redirect()->route('pengurus.replacements.index')
                ->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        $this->replacements->cancel($booking);

        return redirect()
            ->route('pengurus.replacements.index')
            ->with('success', 'Peminjaman dibatalkan (tidak ada pengganti).');
    }
}
