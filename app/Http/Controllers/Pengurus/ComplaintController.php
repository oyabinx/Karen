<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /**
     * Daftar keluhan unit dari form pengembalian — pengurus
     * (docs/feature/pengembalian.md: keluhan = catatan; tindak
     * lanjut manual, termasuk menjadwalkan maintenance bila perlu).
     */
    public function index(Request $request): View
    {
        $complaints = Complaint::with(['booking.vehicle', 'booking.user.seksi.bidang'])
            ->when($request->status === 'selesai', fn ($q) => $q->where('resolved', true))
            ->when($request->status !== 'selesai', fn ($q) => $q->where('resolved', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pengurus.complaints.index', [
            'complaints' => $complaints,
            'status' => $request->input('status', 'belum'),
        ]);
    }

    public function resolve(Complaint $complaint): RedirectResponse
    {
        $complaint->update(['resolved' => true]);

        return back()->with('success', 'Keluhan ditandai selesai.');
    }

    public function reopen(Complaint $complaint): RedirectResponse
    {
        $complaint->update(['resolved' => false]);

        return back()->with('success', 'Keluhan dibuka kembali.');
    }
}
