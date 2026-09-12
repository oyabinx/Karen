<?php

namespace App\Http\Controllers\Pengurus;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Maintenance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /**
     * Daftar keluhan unit dari form pengembalian — pengurus
     * (docs/feature/pengembalian.md: keluhan = catatan; tindak
     * lanjut manual, termasuk menjadwalkan maintenance bila perlu).
     *
     * Tab "Belum Selesai": dikelompokkan PER KENDARAAN dengan tombol
     * "Jadwalkan Maintenance" yang membuka modal tanggal — catatan
     * otomatis berisi gabungan seluruh keluhan aktif unit tsb
     * (rev user: tidak perlu bolak-balik mencatat plat & keluhan).
     */
    public function index(Request $request): View
    {
        $status = $request->input('status', 'belum');

        if ($status === 'selesai') {
            return view('pengurus.complaints.index', [
                'status' => $status,
                'complaints' => Complaint::with(['booking.vehicle', 'booking.user.seksi.bidang'])
                    ->where('resolved', true)
                    ->latest()
                    ->paginate(15)
                    ->withQueryString(),
                'perVehicle' => collect(),
            ]);
        }

        $complaints = Complaint::with(['booking.vehicle', 'booking.user.seksi.bidang'])
            ->where('resolved', false)
            ->orderByDesc('id') // deterministik: keluhan terbaru dulu
            ->get();

        // Gabungkan keluhan per kendaraan → satu kartu + catatan gabungan
        $perVehicle = $complaints
            ->groupBy(fn (Complaint $c) => $c->booking->vehicle_id)
            ->map(function ($group) {
                // "setir tidak center (Pegawai A, 10 Sep); rem bermasalah (Pegawai B, 11 Sep)"
                $catatan = $group->map(fn (Complaint $c) => sprintf(
                    '%s (%s, %s)',
                    $c->message,
                    $c->booking->user->name,
                    optional($c->booking->returned_at ?? $c->created_at)->translatedFormat('d M'),
                ))->implode('; ');

                return [
                    'vehicle' => $group->first()->booking->vehicle,
                    'complaints' => $group->values(),
                    // Batas kolom note 255 — pengurus dapat menyunting di modal
                    'catatanGabungan' => Str::limit('Keluhan: '.$catatan, 255),
                ];
            })
            // Kendaraan dengan keluhan terbaru di atas
            ->sortByDesc(fn ($v) => $v['complaints']->first()->created_at->getTimestamp())
            ->values();

        // Unit yang SUDAH terjadwal → tombol "Sedang Maintenance" (bukan jadwalkan lagi)
        $terjadwal = Maintenance::query()
            ->where('status', 'terjadwal')
            ->whereIn('vehicle_id', $perVehicle->map(fn ($v) => $v['vehicle']->id))
            ->orderBy('start_date')
            ->get()
            ->groupBy('vehicle_id');

        $perVehicle = $perVehicle->map(fn ($v) => $v + [
            'maintenanceTerjadwal' => $terjadwal->get($v['vehicle']->id)?->first(),
        ]);

        return view('pengurus.complaints.index', [
            'status' => $status,
            'complaints' => null,
            'perVehicle' => $perVehicle,
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
