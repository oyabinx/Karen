<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    /**
     * Selesaikan peminjaman milik sendiri (tombol "Selesai" +
     * pop-up keluhan opsional) — pegawai DAN pengurus.
     */
    public function store(Request $request, Booking $booking, ReturnService $returns): RedirectResponse
    {
        if ($booking->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->validate([
            'complaint' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $returns->manualReturn($booking, $request->input('complaint'));
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = 'Peminjaman selesai — terima kasih.';

        if ($request->filled('complaint')) {
            $pesan .= ' Keluhan Anda tercatat dan akan ditinjau pengurus.';
        }

        return back()->with('success', $pesan);
    }
}
