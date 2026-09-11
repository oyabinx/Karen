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
            'koefisienPajak' => AppSettings::koefisienPajak(),
            'koefMin' => AppSettings::MIN_KOEFISIEN_PAJAK,
            'koefMax' => AppSettings::MAX_KOEFISIEN_PAJAK,
            'bend26' => AppSettings::bend26Identity(),
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
            'koefisien_pajak' => [
                'required', 'numeric',
                'min:'.AppSettings::MIN_KOEFISIEN_PAJAK,
                'max:'.AppSettings::MAX_KOEFISIEN_PAJAK,
            ],
            // Identitas bend26 — opsional; default dipertahankan bila kosong
            'b26' => ['nullable', 'array'],
            'b26.terima_dari' => ['nullable', 'string', 'max:150'],
            'b26.kota' => ['nullable', 'string', 'max:50'],
            'b26.kode_kegiatan' => ['nullable', 'string', 'max:50'],
            'b26.ppn_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'b26.pejabat' => ['nullable', 'array'],
            'b26.pejabat.*.nama' => ['nullable', 'string', 'max:100'],
            'b26.pejabat.*.nip' => ['nullable', 'string', 'max:40'],
            'b26.no_rek' => ['nullable', 'array'],
            'b26.no_rek.*' => ['nullable', 'string', 'max:40'],
            'b26.pph_percent' => ['nullable', 'array'],
            'b26.pph_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'max_booking_days.min' => 'Durasi minimal :min hari.',
            'max_booking_days.max' => 'Durasi maksimal :max hari.',
            'koefisien_pajak.min' => 'Koefisien minimal :min.',
            'koefisien_pajak.max' => 'Koefisien maksimal :max.',
        ]);

        AppSettings::set(AppSettings::KEY_MAX_BOOKING_DAYS, (string) $validated['max_booking_days']);
        AppSettings::set(AppSettings::KEY_KOEFISIEN_PAJAK, (string) $validated['koefisien_pajak']);

        if (array_key_exists('b26', $validated)) {
            // Gabungkan dengan nilai tersimpan/default agar field kosong
            // tidak menghapus identitas lain
            $identity = array_replace_recursive(AppSettings::bend26Identity(), $validated['b26']);
            AppSettings::setBend26Identity($identity);
        }

        return back()->with('success', "Pengaturan disimpan — durasi maksimal {$validated['max_booking_days']} hari, koefisien pajak ×{$validated['koefisien_pajak']} (hanya untuk nota baru)".(array_key_exists('b26', $validated) ? ', identitas bend26 diperbarui' : '').'.');
    }
}
