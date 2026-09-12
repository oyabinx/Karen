<?php

namespace App\Support;

use App\Models\IntegrationSetting;

/**
 * Pengaturan aplikasi Karen yang dapat diubah admin via UI —
 * tanpa mengubah kode (skema baru UAT 03).
 *
 * Setting disimpan di tabel integration_settings (dipakai bersama
 * dengan konfigurasi Google — tabel generik key-value).
 */
class AppSettings
{
    public const KEY_MAX_BOOKING_DAYS = 'max_booking_days';
    public const KEY_KOEFISIEN_PAJAK = 'koefisien_pajak';
    public const KEY_BEND26_IDENTITY = 'bend26_identity';

    public const DEFAULT_MAX_BOOKING_DAYS = 3;
    public const MIN_MAX_BOOKING_DAYS = 1;
    public const MAX_MAX_BOOKING_DAYS = 30;

    public const DEFAULT_KOEFISIEN_PAJAK = 1.13;
    public const MIN_KOEFISIEN_PAJAK = 1.00;
    public const MAX_KOEFISIEN_PAJAK = 2.00;

    public static function get(string $key, mixed $default = null): mixed
    {
        return IntegrationSetting::where('setting_key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        IntegrationSetting::updateOrCreate(
            ['setting_key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Durasi maksimal peminjaman (hari) — dibaca dinamis oleh
     * BookingService, UI pencarian, dan form booking.
     */
    public static function maxBookingDays(): int
    {
        $val = (int) self::get(self::KEY_MAX_BOOKING_DAYS, self::DEFAULT_MAX_BOOKING_DAYS);

        return max(self::MIN_MAX_BOOKING_DAYS, min(self::MAX_MAX_BOOKING_DAYS, $val));
    }

    /**
     * Koefisien pajak (multiplier) — dibaca dinamis oleh BudgetService,
     * form nota JS, dan semua tampilan realisasi. Perubahan hanya
     * berlaku untuk nota BARU (skema baru UAT 04).
     */
    public static function koefisienPajak(): float
    {
        $val = (float) self::get(self::KEY_KOEFISIEN_PAJAK, self::DEFAULT_KOEFISIEN_PAJAK);

        return round(max(self::MIN_KOEFISIEN_PAJAK, min(self::MAX_KOEFISIEN_PAJAK, $val)), 2);
    }

    /**
     * Identitas dokumen bend26 (nama dinas, pejabat + NIP, No. Rek &
     * tarif PPN/PPh per pos) — JSON, dikonsumsi Bend26Identity.
     * Default diambil dari file contoh docs/Bend26 (UAT 04 rev-2).
     * Tarif PPN lama (scalar) dinormalisasi ke format per-pos.
     */
    public static function bend26Identity(): array
    {
        $stored = json_decode((string) self::get(self::KEY_BEND26_IDENTITY, '{}'), true);

        if (is_array($stored) && isset($stored['ppn_percent']) && ! is_array($stored['ppn_percent'])) {
            $legacy = (float) $stored['ppn_percent'];
            unset($stored['ppn_percent']);
            $stored['ppn_percent'] = [
                'servis' => $legacy,
                'suku_cadang' => $legacy,
                'ac' => $legacy,
                'pelumas' => 0.0,
            ];
        }

        return array_replace_recursive(Bend26Identity::defaults(), is_array($stored) ? $stored : []);
    }

    public static function setBend26Identity(array $identity): void
    {
        self::set(self::KEY_BEND26_IDENTITY, json_encode($identity, JSON_UNESCAPED_UNICODE));
    }
}
