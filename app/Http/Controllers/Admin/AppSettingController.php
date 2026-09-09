<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppSettingController extends Controller
{
    /**
     * Pengaturan aplikasi — khusus admin (skema baru UAT 03).
     * Durasi maksimal peminjaman dapat diubah tanpa mengubah kode.
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'maxBookingDays' => AppSettings::maxBookingDays(),
            'min' => AppSettings::MIN_MAX_BOOKING_DAYS,
            'max' => AppSettings::MAX_MAX_BOOKING_DAYS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'max_booking_days' => [
                'required', 'integer',
                'min:'.AppSettings::MIN_MAX_BOOKING_DAYS,
                'max:'.AppSettings::MAX_MAX_BOOKING_DAYS,
            ],
        ], [
            'max_booking_days.min' => 'Durasi minimal :min hari.',
            'max_booking_days.max' => 'Durasi maksimal :max hari.',
        ]);

        AppSettings::set(AppSettings::KEY_MAX_BOOKING_DAYS, (string) $validated['max_booking_days']);

        return back()->with('success', "Durasi maksimal peminjaman disimpan: {$validated['max_booking_days']} hari — langsung berlaku.");
    }
}
