<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AvailabilityService;
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
        private readonly AvailabilityService $availability,
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

        // Penggantian PARSIAL (UAT 03-B7): tawarkan bila tabrakan hanya
        // menimpa tepi rentang & pemblokir tunggal
        $partial = $this->replacements->partialRange($booking);
        $partialCandidates = collect();

        if ($partial) {
            $partialCandidates = $this->replacements->candidates($booking) // full range
                ->merge($this->availability->availableBetween($partial['os'], $partial['oe'], $booking->vehicle_id))
                ->unique('id');
        }

        return view('pengurus.replacements.show', [
            'booking' => $booking,
            'candidates' => $this->replacements->candidates($booking),
            'partial' => $partial,
            'partialCandidates' => $partialCandidates,
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
            ->with('success', 'Peminjaman dibatalkan (tanpa pengganti).');
    }

    /**
     * Penggantian PARSIAL (UAT 03-B7): pengganti hanya untuk tanggal
     * yang menabrak; sisa tanggal tetap mobil lama (booking terpecah).
     */
    public function assignPartial(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate(['vehicle_id' => ['required', 'exists:vehicles,id']]);

        if ($booking->status !== Booking::STATUS_MENUNGGU_PENGGANTIAN) {
            return back()->with('error', 'Peminjaman ini tidak menunggu penggantian.');
        }

        $partial = $this->replacements->partialRange($booking);

        if (! $partial) {
            return back()->with('error', 'Penggantian parsial tidak tersedia untuk peminjaman ini.');
        }

        try {
            $replacement = \App\Models\Vehicle::findOrFail($request->input('vehicle_id'));
            $result = $this->replacements->assignPartial($booking, $partial['os'], $partial['oe'], $replacement);
        } catch (\InvalidArgumentException|\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $lama = $result['original'];
        $split = $result['split'];

        return redirect()
            ->route('pengurus.replacements.index')
            ->with('success', "Peminjaman dipecah: {$lama->start_date->translatedFormat('d M')}–{$lama->end_date->translatedFormat('d M Y')} tetap {$lama->vehicle->name}; {$split->start_date->translatedFormat('d M')}–{$split->end_date->translatedFormat('d M Y')} memakai {$split->vehicle->name}.");
    }
}
