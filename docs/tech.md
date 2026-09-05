# Karen — Dokumen Teknis

## 1. Stack Teknologi

| Komponen | Teknologi | Keterangan |
|----------|-----------|------------|
| Backend & Framework | PHP 8.2+ / **Laravel 13** | Monolith (server-rendered) |
| Frontend | Blade + **Tailwind CSS v4** + Alpine.js (seperlunya) | Vite untuk build asset |
| Database | **MySQL 8** / MariaDB 10.11+ | |
| Dokumen PDF | **barryvdh/laravel-dompdf** | generate bend26 & draft nota per pos |
| Integrasi anggaran | **Google Sheets API v4 + Service Account** (outbound JSON) | aplikasi intranet — inbound internet ditutup; konfigurasi via halaman admin [feature/integrasi_google.md](feature/integrasi_google.md), task `budgets:sync-sheets` sesuai interval (default 15 menit) |
| Autentikasi | Laravel Breeze (Blade stack) | Login email + password |
| Scheduler | Laravel Scheduler (`schedule:run` via cron) | Pengembalian otomatis harian |
| Role | Kolom `role` pada tabel users + middleware kustom | `admin`, `pengurus`, `pegawai` |

### Alasan pemilihan
- **Monolith Blade**: aplikasi internal kantor, satu deploy, pengembangan cepat, tidak butuh API terpisah.
- **Breeze**: scaffold auth standar Laravel, ringan, mudah dikustomisasi.
- **Role via middleware** (bukan package spatie): kebutuhan role sederhana & tetap (3 role), mengurangi dependensi.

## 2. Arsitektur

```
Browser
  │
  ▼
Laravel HTTP Kernel
  ├── Middleware: auth, role:admin|pengurus|pegawai
  ├── Routes
  │     ├── routes/web.php        (semua route, dikelompokkan per role)
  │     └── routes/console.php    (definisi scheduler)
  ├── Controllers (per fitur, di sub-folder)
  ├── Services (logika bisnis: AvailabilityService, BookingService)
  ├── Models (Eloquent)
  └── Blade Views (layout + page per role)
```

- **Controller tipis**: validasi di FormRequest, logika bisnis di Service, query di Model/Service.
- **Service utama**:
  - `AvailabilityService` — hitung mobil tersedia pada rentang tanggal.
  - `BookingService` — buat peminjaman (validasi overlap + durasi ≤ 3 hari + **kuota bidang**).
  - `ReturnService` — pengembalian manual & otomatis + pencatatan keluhan.
  - `ReplacementService` — cari mobil pengganti untuk booking yang menabrak jadwal maintenance **atau armada event**.
  - `EventService` — reservasi armada event bidang (pemilihan mobil, penyelesaian tabrakan via ReplacementService).

## 3. Skema Database

### 3.1 ERD (ringkas)

```
users ──< bookings >── vehicles
  │                       │
  └──< (1 user 1 bidang)  └──< maintenances
bidang ──< seksi ──< users
bookings ──< complaints (1 booking max 1 keluhan saat pengembalian)
```

### 3.2 Tabel

#### `users`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR(255) | |
| email | VARCHAR(255) UNIQUE | untuk login |
| password | VARCHAR(255) | hash bcrypt |
| role | ENUM('admin','pengurus','pegawai') | default `pegawai` |
| phone | VARCHAR(20) NULL | **wajib diisi saat update profil** (unique) |
| seksi_id | BIGINT NULL FK → seksi | |
| timestamps, soft deletes | | |

#### `bidang`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR(100) UNIQUE | |
| max_active_bookings | TINYINT default 2 | kuota mobil per bidang; satu bidang khusus diset 3 (diatur admin) |
| timestamps | | |

> Seeder awal: 5 bidang (nama disesuaikan saat setup kantor).

#### `seksi`
| Kolom | Tipe |
|-------|------|
| id | BIGINT PK |
| bidang_id | BIGINT FK → bidang |
| name | VARCHAR(100) |
| timestamps |
| UNIQUE(bidang_id, name) | |

#### `vehicles`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR(100) | misal "Avanza B 1234 XYZ" |
| plate_number | VARCHAR(15) UNIQUE | |
| capacity | TINYINT | jumlah kursi |
| status | ENUM('bisa_dipinjam','tidak_bisa_dipinjam') | diatur pengurus |
| condition | ENUM('baik','perlu_diperiksa') | `perlu_diperiksa` saat ada keluhan |
| photo_path | VARCHAR(255) NULL | |
| timestamps, soft deletes | | |

#### `maintenances`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| vehicle_id | BIGINT FK → vehicles | |
| start_date | DATE | |
| end_date | DATE | |
| status | ENUM('terjadwal','selesai') | daur maintenance |
| workshop_name, nota_number, nota_date | VARCHAR/DATE NULL | identitas nota bengkel (input saat selesai) |
| note | VARCHAR(255) NULL | |
| timestamps | | |

> Detail skema anggaran (`vehicle_budgets`, `maintenance_costs`, `generated_documents`, `vehicles.year`) ada di [feature/anggaran_maintenance.md](feature/anggaran_maintenance.md).

#### `events`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| name | VARCHAR(150) | nama event |
| bidang_id | BIGINT FK → bidang | bidang penyelenggara |
| start_date | DATE | |
| end_date | DATE | durasi fleksibel — event khusus boleh >3 hari (batas 3 hari hanya utk peminjaman biasa) |
| note | VARCHAR(255) NULL | |
| status | ENUM('terjadwal','selesai','dibatalkan') | |
| created_by | BIGINT FK → users | admin/pengurus pembuat |
| timestamps | | |

#### `event_vehicles`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| event_id | BIGINT FK → events | |
| vehicle_id | BIGINT FK → vehicles | UNIQUE(event_id, vehicle_id) |

> Armada event `terjadwal` memblokir ketersediaan mobil (ikut query §4.1) dan **dikecualikan dari kuota bidang**.

#### `bookings`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| user_id | BIGINT FK → users | peminjam |
| vehicle_id | BIGINT FK → vehicles | |
| start_date | DATE | mulai 00:00 |
| end_date | DATE | selesai 24:00 |
| address | VARCHAR(255) | alamat tujuan |
| purpose | VARCHAR(255) | keperluan |
| status | ENUM('dipinjam','menunggu_penggantian','dikembalikan','dibatalkan') | `menunggu_penggantian` = mobil dijadwalkan maintenance, menunggu konfirmasi pengurus |
| original_vehicle_id | BIGINT NULL FK → vehicles | terisi bila mobil diganti karena maintenance (jejak audit) |
| returned_at | DATETIME NULL | waktu pengembalian manual |
| auto_returned | BOOLEAN default false | true jika dikembalikan scheduler |
| timestamps | | |

> Index: `(vehicle_id, start_date, end_date)` untuk query ketersediaan; `(user_id, status)` untuk cek satu peminjaman aktif.

#### `complaints`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| id | BIGINT PK | |
| booking_id | BIGINT FK → bookings UNIQUE | 1 keluhan per peminjaman |
| message | TEXT | isi keluhan |
| resolved | BOOLEAN default false | ditandai pengurus setelah ditindaklanjuti |
| timestamps | | |

## 4. Logika Kunci

### 4.1 Ketersediaan (AvailabilityService)
Peminjaman berbasis **hari penuh** (tidak ada jam). Dua rentang tanggal [A1,A2] dan [B1,B2] **overlap** jika:

```
A1 <= B2 AND A2 >= B1
```

Mobil **tersedia** pada rentang [mulai, akhir] jika **TIDAK** ada:
- booking dengan status `dipinjam` ATAU `menunggu_penggantian` yang overlap dengan rentang;
- jadwal maintenance **berstatus `terjadwal`** yang overlap dengan rentang (yang sudah `selesai` TIDAK memblokir — ketersediaan berbasis tanggal & status);
- event berstatus `terjadwal` yang rentangnya overlap (mobil termasuk armada event);
- mobil berstatus `tidak_bisa_dipinjam`;
- mobil bercondition `perlu_diperiksa` — **hanya diatur manual oleh pengurus** (keluhan pengembalian TIDAK mengubah condition; lihat [feature/pengembalian.md](feature/pengembalian.md)).

Query (Eloquent, via `whereDoesntHave` / `whereNotExists`):

```php
Vehicle::query()
    ->where('status', 'bisa_dipinjam')
    ->where('condition', 'baik')
    ->whereDoesntHave('bookings', fn ($q) => $q
        ->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
        ->whereDate('start_date', '<=', $end)
        ->whereDate('end_date', '>=', $start))
    ->whereDoesntHave('maintenances', fn ($q) => $q
        ->whereDate('start_date', '<=', $end)
        ->whereDate('end_date', '>=', $start))
    ->whereDoesntHave('events', fn ($q) => $q
        ->where('status', 'terjadwal')
        ->whereDate('start_date', '<=', $end)
        ->whereDate('end_date', '>=', $start))
    ->get();
```

### 4.2 Durasi maksimal 3 hari
- Durasi = `end_date - start_date + 1` (inklusif, dalam hari kalender — Sabtu/Minggu dihitung).
- Validasi: `end_date >= start_date` dan `end_date - start_date <= 2 hari` (maks 3 hari total).
- Booking hari-H (tanggal mulai = hari ini) tetap dihitung mulai pukul 00:00 hari itu — **tidak ada field jam**, semua peminjaman adalah hari penuh 00:00–24:00.

### 4.2b Kuota peminjaman per bidang
- Kuota disimpan di `bidang.max_active_bookings` (default 2; satu bidang khusus diset 3 oleh admin).
- Sebelum menyimpan booking, hitung jumlah booking bidang peminjam:

```php
$count = Booking::query()
    ->whereIn('status', ['dipinjam', 'menunggu_penggantian'])
    ->whereHas('user.seksi.bidang', fn ($q) => $q->where('id', $bidangId))
    ->count();

// tolak bila $count >= $bidang->max_active_bookings
```

- Booking `dikembalikan` / `dibatalkan` tidak dihitung → jatah lepas.
- Berlaku juga untuk pengurus yang meminjam (dihitung pada bidang tempat seksi pengurus terdaftar).
- **Pengecualian**: mobil yang terpakai pada **event bidang** tidak dihitung kuota — event adalah mekanisme resmi pemakaian armada di atas kuota (lihat [feature/event_bidang.md](feature/event_bidang.md)).

### 4.2c Penggantian mobil karena maintenance (ReplacementService)
Dipicu saat maintenance dibuat/diubah dan overlap dengan booking `dipinjam`:

```
1. booking terdampak → status 'menunggu_penggantian'
2. kandidat pengganti = AvailabilityService untuk rentang booking,
   exclude mobil yang di-maintenance
3. pengurus memilih → vehicle_id = pengganti,
   original_vehicle_id = mobil lama, status kembali 'dipinjam'
4. tanpa kandidat / pengurus membatalkan → status 'dibatalkan' (kuota lepas)
```

- Pengecekan juga dijalankan dalam transaksi + `lockForUpdate` agar mobil pengganti tidak dibooking orang lain saat dikonfirmasi.
- Booking `menunggu_penggantian` tetap menahan kuota bidang (jumlah mobil bidang tidak berubah) dan tetap dihitung "tidak tersedia" pada mobil lamanya agar tidak terjadi peminjaman ganda selama proses.

### 4.3 Pengembalian otomatis & penutupan event (scheduler)
`routes/console.php`:

```php
// routes/console.php — gunakan FACADE Illuminate\Support\Facades\Schedule
// (di Laravel 13, Schedule bukan kelas statis yang di-import langsung)
use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(ReturnService::class)->autoReturn())->dailyAt('00:01');
Schedule::call(fn () => app(EventService::class)->autoFinish())->dailyAt('00:02');
// Sinkron Sheets berjalan tiap 5 menit & memeriksa interval sendiri
// (5/15/30/60 menit via UI admin) sehingga cron tidak perlu diubah:
Schedule::call(fn () => app(\App\Services\Google\SheetsBudgetSync::class)->runIfDue())->everyFiveMinutes();
```

`autoReturn()`:
```
 booking status='dipinjam' AND end_date < hari_ini:
   - status → 'dikembalikan'
   - auto_returned → true
   - returned_at → now()

 booking status='menunggu_penggantian' AND end_date < hari_ini
 (pengurus belum memutuskan pengganti):
   - status → 'dibatalkan'   → kuota bidang lepas, mobil lama terblokir karena maintenance saja

 event status='terjadwal' AND end_date < hari_ini:
   - status → 'selesai'      → armada event otomatis lepas (ketersediaan berbasis tanggal)
```
Status mobil sudah turunan dari query ketersediaan (berbasis tanggal), sehingga mobil otomatis tersedia kembali setelah `end_date` lewat — namun update tetap dilakukan agar data booking konsisten.

Cron server: `* * * * * php /path/artisan schedule:run`

### 4.4 Transaksi & race condition
Pembuatan booking dibungkus `DB::transaction()` + **lock** (`lockForUpdate` pada baris vehicle) untuk mencegah dua pegawai booking mobil sama pada rentang overlap secara bersamaan.

## 5. Keamanan

| Aspek | Kebijakan |
|-------|-----------|
| Password | Hash bcrypt (bawaan Laravel), minimal 8 karakter |
| Session | Driver database/cookie bawaan Breeze, logout mengakhiri sesi |
| Authorization | Middleware `role:` pada semua route; pegawai hanya boleh melihat/mengubah booking miliknya sendiri (policy) |
| Mass assignment | `$fillable` whitelist di semua model |
| SQL Injection | Eloquent / query binding |
| XSS | Escaping otomatis Blade `{{ }}` |
| CSRF | Laravel CSRF token pada semua form |
| Upload foto | Validasi mime (jpg/png/webp), maks 2 MB, simpan di `storage/` dengan nama acak |
| Kunci service account | Upload via halaman admin; JSON valid `service_account`, maks 50 KB, disimpan terenkripsi (lihat [feature/integrasi_google.md](feature/integrasi_google.md)) |

## 6. Validasi Penting (ringkasan)

| Data | Aturan |
|------|--------|
| No. HP | `required` saat update profil, regex `^(\+62|62|0)8[1-9][0-9]{6,10}$`, unique |
| Tanggal booking | `start_date >= today`, `end_date >= start_date`, durasi ≤ 3 hari |
| Alamat & keperluan | `required`, string, maks 255 karakter |
| Email | format email, unique (kecuali user sendiri) |
| Seksi user | wajib untuk role pegawai & pengurus (dasar hitung kuota bidang); opsional untuk admin |
| Plat nomor | required, unique, maks 15 karakter |
| Kuota bidang | `max_active_bookings` required, angka 1–5 |

## 7. Environment & Deploy (ringkas)

**Pemisahan konfigurasi (prinsip intranet kantor):**
- **Zona waktu**: `APP_TIMEZONE=Asia/Jakarta` (**satu sumber kebenaran waktu** — seluruh `now()`/`today()` dan tampilan datetime backend-front konsisten WIB; pendekatan epoch-millis ditolak karena menambah kompleksitas tanpa keuntungan pada aplikasi server-rendered satu zona waktu — temuan UAT F1). `APP_LOCALE=id` + `APP_FAKER_LOCALE=id_ID`.
- **Saat deploy (sekali, oleh petugas IT)**: `.env` hanya berisi konfigurasi inti — `APP_ENV=production`, `APP_URL`, koneksi database, `APP_KEY`. Tidak ada kredensial Google di `.env`.
- **Runtime (via UI Karen, kapan pun oleh admin)**: seluruh konfigurasi operasional — integrasi Google (kunci service account terenkripsi, spreadsheet, Drive, interval sync — lihat [feature/integrasi_google.md](feature/integrasi_google.md)), kuota bidang, struktur organisasi, user. Pengguna aplikasi tidak pernah mengedit file di server.
- `php artisan migrate --seed` saat setup awal (seeder: 5 bidang + admin pertama).
- Queue driver: `sync` (tidak ada job asinkron berat di v1).

**Scheduler (auto-return, auto-finish event, sync Sheets):**
Laravel Scheduler **butuh pemicu eksternal** — aplikasi web tidak bisa membangunkan dirinya sendiri pukul 00:01 (intranet tidak punya trafik tengah malam), sehingga tugas terjadwal tidak dapat diandalkan bila hanya dipicu dari request. Pilihan pemasangan (sekali oleh petugas IT, bukan oleh pengguna aplikasi):
1. **Cron** (Linux, standar): `* * * * * cd /var/www/karen && php artisan schedule:run >> /dev/null 2>&1`
2. **Systemd service** (Linux, tanpa cron): unit `schedule:work` long-running (`php artisan schedule:work`) dengan `Restart=always`
3. **Windows Task Scheduler** (bila server kantor Windows): task per 1 menit menjalankan `php artisan schedule:run`

> Status pemicu scheduler sebaiknya dipantau: task terakhir tercatat di `integration_logs`/log Laravel — bila auto-return tidak berjalan > 1 hari, tampilkan peringatan di dashboard admin (deteksi sederhana via timestamp eksekusi terakhir).
