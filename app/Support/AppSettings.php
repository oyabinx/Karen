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

    public const DEFAULT_MAX_BOOKING_DAYS = 3;
    public const MIN_MAX_BOOKING_DAYS = 1;
    public const MAX_MAX_BOOKING_DAYS = 30;

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
}
