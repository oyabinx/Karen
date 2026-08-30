# Karen — Struktur Folder & Aplikasi

## 1. Struktur Root Proyek

```
karen/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                     # Breeze: login, register (nonaktif), password
│   │   │   ├── Admin/
│   │   │   │   ├── UserController.php
│   │   │   │   ├── BidangController.php
│   │   │   │   ├── SeksiController.php
│   │   │   │   └── IntegrationController.php     # konfigurasi integrasi Google
│   │   │   ├── Pengurus/
│   │   │   │   ├── VehicleController.php
│   │   │   │   ├── MaintenanceController.php
│   │   │   │   ├── ReplacementController.php      # konfirmasi mobil pengganti
│   │   │   ├── EventController.php           # reservasi armada event bidang (akses: admin+pengurus)
│   │   │   │   ├── BudgetController.php           # anggaran 4 pos per mobil + input nota
│   │   │   │   ├── DocumentController.php         # unduh bend26 / draft nota
│   │   │   │   ├── BookingMonitorController.php
│   │   │   │   ├── ComplaintController.php
│   │   │   │   └── ReportController.php
│   │   │   ├── Pegawai/
│   │   │   │   ├── SearchController.php      # cari mobil tersedia
│   │   │   │   ├── BookingController.php
│   │   │   │   └── ReturnController.php
│   │   │   ├── Controller.php
│   │   │   ├── DashboardController.php       # router dashboard per role
│   │   │   └── ProfileController.php         # ubah profil (semua role)
│   │   ├── Middleware/
│   │   │   └── EnsureUserHasRole.php         # role:admin|pengurus|pegawai
│   │   └── Requests/                         # FormRequest validasi
│   │       ├── ProfileUpdateRequest.php      # phone wajib
│   │       ├── BookingStoreRequest.php       # tanggal ≤3 hari, alamat, keperluan
│   │       ├── VehicleStoreRequest.php
│   │       ├── MaintenanceStoreRequest.php
│   │       ├── UserStoreRequest.php
│   │       └── ...
│   ├── Models/
│   │   ├── User.php
│   │   ├── Bidang.php
│   │   ├── Seksi.php
│   │   ├── Vehicle.php
│   │   ├── Maintenance.php
│   │   ├── Booking.php
│   │   └── Complaint.php
│   ├── Services/
│   │   ├── AvailabilityService.php       # mobil tersedia pada rentang tanggal
│   │   ├── BookingService.php            # create booking (transaksi + lock + kuota bidang)
│   │   ├── ReturnService.php             # return manual & auto + keluhan
│   │   ├── ReplacementService.php        # cari mobil pengganti saat maintenance/event menabrak booking
│   │   ├── EventService.php              # reservasi armada event bidang + auto-finish scheduler
│   │   ├── BudgetService.php             # input nota 4 pos, koefisien 1,13, sisa anggaran
│   │   └── DocumentService.php           # render template resources/draft_documents/ → PDF (dompdf)
│   └── Services/Google/
│       └── SheetsBudgetSync.php          # sinkron anggaran via Google Sheets API (outbound)
│   └── Policies/
│       └── BookingPolicy.php             # pegawai hanya bisa akses booking sendiri
├── bootstrap/
├── config/
├── database/
│   ├── migrations/                       # skema lihat tech.md §3
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── BidangSeeder.php              # 5 bidang awal
│       ├── SeksiSeeder.php               # contoh seksi per bidang
│       ├── AdminSeeder.php               # akun admin pertama
│       └── VehicleSeeder.php             # contoh data mobil (dev)
├── docs/                                 # dokumentasi perencanaan (dokumen ini)
│   ├── product.md
│   ├── tech.md
│   ├── structure.md
│   ├── taskplan.md
│   └── feature/                          # detail per fitur
├── public/
├── resources/
│   ├── css/  └── js/                     # Tailwind v4 + Alpine
│   ├── draft_documents/                  # MASTER DRAFT DOKUMEN — template sumber generate (satu sistem di Karen)
│   │   ├── README.md                     # petunjuk template & variabel yang tersedia
│   │   ├── bend26.blade.php              # formulir bukti pengeluaran bendahara (lembar bend26)
│   │   ├── draft_nota.blade.php          # draft nota per pos (servis/suku cadang/AC/pelumas)
│   │   └── kartu_inventaris.blade.php    # kartu inventaris pemeliharaan kendaraan
│   └── views/                            # (halaman UI aplikasi — hasil PDF JANGAN disimpan di sini)
│       ├── layouts/
│       │   ├── app.blade.php             # layout utama (sidebar per role)
│       │   └── guest.blade.php           # layout login
│       ├── components/                   # komponen Blade reusable
│       ├── dashboard/
│       │   ├── admin.blade.php
│       │   ├── pengurus.blade.php
│       │   └── pegawai.blade.php
│       ├── auth/                         # login
│       ├── profile/                      # edit profil (semua role)
│       ├── admin/
│       │   ├── users/
│       │   ├── bidang/
│       │   ├── seksi/
│       │   └── integrations/             # konfigurasi Google + log sinkronisasi
│       ├── pengurus/
│       │   ├── vehicles/
│       │   ├── maintenances/
│       │   ├── bookings/                 # monitoring
│       │   ├── replacements/             # konfirmasi mobil pengganti (maintenance & event)
│       │   ├── events/                   # wizard & daftar event armada bidang
│       │   ├── budgets/                  # UI anggaran 4 pos + input nota
│       │   ├── documents/                # UI daftar & unduh dokumen (PDF tersimpan di storage/app/documents)
│       │   ├── complaints/
│       │   └── reports/
│       ├── pegawai/
│       │   ├── search/                   # hasil cari mobil tersedia
│       │   ├── bookings/                 # form booking + riwayat
│       │   └── returns/                  # form keluhan saat kembalikan
│       └── partials/
├── routes/
│   ├── web.php                           # route terkelompok per role (middleware)
│   └── console.php                       # scheduler auto-return (daily 00:01)
├── storage/
│   ├── app/documents/                    # OUTPUT: hasil generate PDF (bend26, draft nota, kartu inventaris)
│   └── app/public/vehicles/              # foto mobil
├── tests/
│   ├── Feature/                          # test fitur utama (booking, return, dll.)
│   └── Unit/                             # test AvailabilityService, validasi durasi
├── .env
├── artisan
└── composer.json
```

## 2. Konvensi Route

Terpusat di `routes/web.php`, dikelompokkan dengan middleware role:

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', ...)->name('profile.edit');   // semua role

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(...);

    Route::middleware('role:pengurus')->prefix('pengurus')->name('pengurus.')->group(...);

    // fitur peminjaman (cari, booking, kembalikan) — pegawai DAN pengurus
    Route::middleware('role:pegawai|pengurus')->prefix('pegawai')->name('pegawai.')->group(...);

    // event armada bidang — pengurus DAN admin
    Route::middleware('role:admin|pengurus')->prefix('pengurus/events')->name('pengurus.events.')->group(...);
});
```

| Prefix | Role | Isi |
|--------|------|-----|
| `/admin` | admin | users, bidang (+ kuota peminjaman), seksi, integrasi/google |
| `/pengurus` | pengurus | vehicles, maintenances, budgets (anggaran 4 pos), replacements, documents (bend26/nota), bookings (monitoring), complaints, reports |
| `/pengurus/events` | pengurus **dan admin** | wizard event armada bidang |
| `/pegawai` | pegawai **dan pengurus** | search, bookings, returns |

## 3. Konvensi Umum

- **Bahasa**: UI dan seluruh dokumen dalam Bahasa Indonesia; nama variabel/kode dalam Bahasa Inggris.
- **Penamaan route**: `role.resource.aksi` → `admin.users.index`, `pegawai.bookings.store`.
- **Controller per role** di sub-folder (`Admin/`, `Pengurus/`, `Pegawai/`) agar jelas batas wewenangnya.
- **Logika bisnis di Service**, controller hanya koordinasi HTTP.
- **Blade partial** untuk komponen berulang (tabel, kartu mobil, badge status).
- Badge status: `dipinjam` (kuning), `dikembalikan` (hijau), `perlu_diperiksa` (merah), `maintenance` (abu-abu).
