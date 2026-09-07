<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menu sidebar/drawer per role (docs/structure.md §2).
 *
 * Item hanya ditampilkan bila route-nya sudah terdaftar — jadi menu
 * otomatis bertambah seiring fase implementasi (user, kendaraan,
 * peminjaman, dst.) tanpa perlu mengubah layout.
 */
class KarenMenu
{
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $menu = [
            'Utama' => [
                self::item('dashboard', 'Beranda', self::icon('home')),
                self::item('profile.edit', 'Profil Saya', self::icon('user')),
            ],
        ];

        if ($user->hasAnyRole('admin')) {
            $menu['Administrasi'] = array_filter([
                Route::has('admin.users.index') ? self::item('admin.users.index', 'Manajemen User', self::icon('users')) : null,
                Route::has('admin.bidang.index') ? self::item('admin.bidang.index', 'Bidang & Seksi', self::icon('org')) : null,
                Route::has('admin.integrasi.google') ? self::item('admin.integrasi.google', 'Integrasi Google', self::icon('cloud')) : null,
            ]);
        }

        if ($user->hasAnyRole('admin', 'pengurus')) {
            $menu['Kendaraan'] = array_filter([
                Route::has('pengurus.vehicles.index') ? self::item('pengurus.vehicles.index', 'Data Kendaraan', self::icon('car')) : null,
                Route::has('pengurus.maintenances.index') ? self::item('pengurus.maintenances.index', 'Jadwal Maintenance', self::icon('wrench')) : null,
                // Menu mandiri — dipakai maintenance & event (UAT 03-D3)
                Route::has('pengurus.replacements.index') ? self::item('pengurus.replacements.index', 'Penggantian Mobil', self::icon('swap')) : null,
                Route::has('pengurus.documents.index') ? self::item('pengurus.documents.index', 'Dokumen (bend26/nota)', self::icon('doc')) : null,
                Route::has('pengurus.events.index') ? self::item('pengurus.events.index', 'Event Armada', self::icon('calendar')) : null,
            ]);

            $menu['Monitoring'] = array_filter([
                Route::has('pengurus.bookings.index') ? self::item('pengurus.bookings.index', 'Semua Peminjaman', self::icon('list')) : null,
                Route::has('pengurus.complaints.index') ? self::item('pengurus.complaints.index', 'Keluhan Unit', self::icon('alert')) : null,
                Route::has('pengurus.reports.index') ? self::item('pengurus.reports.index', 'Laporan', self::icon('chart')) : null,
            ]);
        }

        if ($user->hasAnyRole('pegawai', 'pengurus')) {
            $menu['Peminjaman'] = array_filter([
                Route::has('pegawai.search.index') ? self::item('pegawai.search.index', 'Cari Mobil Tersedia', self::icon('search')) : null,
                Route::has('pegawai.bookings.index') ? self::item('pegawai.bookings.index', 'Peminjaman Saya', self::icon('list')) : null,
            ]);
        }

        return array_filter($menu, fn ($items) => ! empty($items));
    }

    private static function item(string $routeName, string $label, string $icon): array
    {
        return [
            'url' => route($routeName),
            'label' => $label,
            'icon' => $icon,
            'active' => $routeName,
        ];
    }

    private static function icon(string $name): string
    {
        return match ($name) {
            'home' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/></svg>',
            'user' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
            'users' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-6.93M16 3.13a4 4 0 010 7.75"/></svg>',
            'org' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21v-2a4 4 0 00-4-4H9a4 4 0 00-4 4v2M12 7a4 4 0 100-8 4 4 0 000 8zm7 1a3 3 0 100-6"/></svg>',
            'cloud' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 010-8 5 5 0 019.6-1.6A4.5 4.5 0 0117 16H7z"/></svg>',
            'car' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h8l2 5H6l2-5zM4 12h16v5h-2a2 2 0 11-4 0h-4a2 2 0 11-4 0H4v-5z"/></svg>',
            'wrench' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L5.83 9.58a4 4 0 115.66-5.66l5.59 5.59m-5.66 5.66l5.66-5.66 3.17 3.17a4 4 0 01-5.66 5.66l-3.17-3.17z"/></svg>',
            'calendar' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
            'list' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>',
            'alert' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
            'chart' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6m4 6V9m4 10V5M5 19h16"/></svg>',
            'search' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>',
            'doc' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2zm7 0v5h5"/></svg>',
            'swap' => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h11m0 0l-3-3m3 3l-3 3M16 17H5m0 0l3 3m-3-3l3-3"/></svg>',
            default => '<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>',
        };
    }
}
