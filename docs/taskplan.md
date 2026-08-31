# Karen — Task Plan (Rencana Implementasi)

> Centang `[x]` saat task selesai. Referensi detail fitur ada di folder [feature/](feature/).

## Fase 0 — Inisialisasi Proyek (Development dengan Docker di Laptop) — ✅ SELESAI

> Seluruh pengembangan awal berjalan di laptop memakai **Laravel Sail** (Docker). Tidak perlu install PHP/Composer/MySQL secara manual di laptop — cukup Docker.

- [x] Install **Docker Desktop** (Windows/Mac) atau **Docker Engine + Compose** (Linux), jalankan dan pastikan `docker --version` serta `docker compose version` berhasil
- [x] Buat proyek Laravel 13 + Sail — *eksekusi aktual: `composer create-project` (PHP 8.5 lokal tersedia) → `composer require laravel/sail --dev` → `php artisan sail:install --with=mysql`*; hasil `compose.yaml` (nama baru Sail): service `laravel.test` (PHP 8.5) + `mysql:8.4`
- [x] `./vendor/bin/sail artisan -V` → pastikan artisan jalan di dalam kontainer (Laravel 13.29.0)
- [x] Konfigurasi `.env` di dalam kontainer: `APP_NAME=Karen`, `DB_CONNECTION=mysql`, `DB_HOST=mysql`, `DB_DATABASE=karen`
- [x] Alias singkat (opsional): shell laptop memakai **fish** — alias `sail` opsional via `alias sail './vendor/bin/sail'` di `~/.config/fish/config.fish`
- [x] Install Laravel Breeze (Blade stack): `sail composer require laravel/breeze --dev` → `sail artisan breeze:install blade` → `sail npm install && sail npm run dev`
- [x] Pastikan Tailwind CSS v4 tercompile dan halaman welcome tampil di `http://localhost` (HTTP 200) — *penyesuaian: core Tailwind di-upgrade v3→v4.3.3, config v3 dihapus*
- [x] Buat repository **GitHub**: `git init`, commit awal, tambahkan remote, push branch `main` — *remote `git@github.com:oyabinx/Karen.git`; push menunggu SSH key publik ditambahkan ke akun GitHub*
      (pastikan `.gitignore` Laravel tidak berubah — `.env` dan `vendor/` **tidak** ikut tercommit)
- [x] **Daftar perintah Sail harian** (untuk referensi): `sail up -d` (nyalakan), `sail down` (matikan), `sail logs -f laravel.test` (lihat log), `sail artisan ...`, `sail composer ...`, `sail npm ...`, `sail test` — terdokumentasi di `build_logs/fase0.log`

## Fase 1 — Fondasi Data & Role — ✅ SELESAI
- [x] Migrasi: `bidang` (termasuk `max_active_bookings` default 2), `seksi` (UNIQUE bidang_id+name)
- [x] Migrasi: `users` (tambah kolom `role`, `phone`, `seksi_id`, soft delete)
- [x] Migrasi: `vehicles`, `maintenances`, `bookings`, `complaints` + index — *plus `events`, `event_vehicles`, `vehicle_budgets`, `maintenance_costs`, `generated_documents`, `integration_settings`, `integration_logs` (14 migrasi, 22 tabel total)*
- [x] Model Eloquent + relasi (lihat [tech.md §3](tech.md)) — *13 model; `$table` eksplisit untuk Bidang/Seksi (pluralisasi Eloquent)*
- [x] Seeder: 5 bidang, contoh seksi, akun admin pertama, contoh mobil (dev) — *nama bidang/seksi placeholder, disesuaikan admin saat rilis*
- [x] Middleware `EnsureUserHasRole` + alias `role:`
- [x] Ubah registrasi Breeze: dinonaktifkan (user hanya dibuat admin) — *route/controller/view/test register dihapus; GET /register → 404*

## Fase 2 — Layout, Autentikasi & Profil — ✅ SELESAI
- [x] Layout utama `app.blade.php`: sidebar desktop (≥lg) + navbar/drawer/bottom-nav mobile (<lg) sesuai → [feature/ui_responsive.md](feature/ui_responsive.md) — *menu per role via `App\Support\KarenMenu` (item muncul otomatis saat route fase berikutnya terdaftar)*
- [x] Breakpoint responsive Tailwind (mobile-first): tabel→kartu di mobile, grid & form adaptif, touch target 44px — *tabel→kartu diterapkan bertahap di tiap halaman fase berikutnya; kerangka & touch target jadi*
- [x] Router dashboard per role (`DashboardController` → view admin/pengurus/pegawai) — *+ banner pengingat nomor HP di semua dashboard*
- [x] Halaman login email + password (Breeze, disesuaikan bahasa Indonesia) — *+ laravel-lang locale `id` untuk semua pesan validasi framework*
- [x] Fitur ubah profil: nama, email, **nomor HP wajib** (validasi regex HP Indonesia) → [feature/profil.md](feature/profil.md) — *fitur hapus-akun mandiri Breeze dihapus (hanya admin menonaktifkan)*
- [x] Ganti password (bawaan Breeze) — *UI diterjemahkan*

## Fase 3 — Admin: Manajemen User, Organisasi & Kuota — ✅ SELESAI
- [x] CRUD user (tambah, lihat, ubah, nonaktifkan/soft delete, atur role + seksi) → [feature/manajemen_user.md](feature/manajemen_user.md) — *filter pencarian/role/bidang/status; tabel desktop ↔ kartu mobile; guard larangan nonaktifkan akun sendiri*
- [x] **Impor massal pegawai via CSV**: unduh template CSV draft, unggah, pratinjau validasi per baris, commit baris valid, laporan baris gagal → [feature/manajemen_user.md](feature/manajemen_user.md) — *`UserCsvImporter` (template → dry-run → commit → laporan gagal CSV)*
- [x] CRUD bidang (5 bidang hasil seeder dapat diubah nama) → [feature/organisasi.md](feature/organisasi.md) — *kartu expandable + badge kuota*
- [x] CRUD seksi per bidang — *unik per bidang; guard hapus berseksi/beranggota*
- [x] Pengaturan kuota peminjaman per bidang (`max_active_bookings`, default 2; satu bidang diset 3) → [feature/kuota_bidang.md](feature/kuota_bidang.md) — *field kuota pada form bidang, validasi 1–5*
- [x] Validasi: email unique, role valid, **seksi wajib untuk pegawai dan pengurus** (opsional hanya admin)

## Fase 4 — Pengurus: Manajemen Kendaraan, Maintenance & Penggantian — ✅ SELESAI
- [x] CRUD kendaraan (nama, plat, kapasitas, foto upload) → [feature/manajemen_kendaraan.md](feature/manajemen_kendaraan.md) — *+ tahun pembuatan; grid kartu responsive; soft delete*
- [x] Toggle status kendaraan: `bisa_dipinjam` / `tidak_bisa_dipinjam` — *PATCH /vehicles/{v}/status*
- [x] Tandai kondisi kembali `baik` setelah selesai diperiksa — *PATCH /vehicles/{v}/condition*
- [x] CRUD jadwal maintenance (rentang tanggal + catatan); kendaraan maintenance tidak muncul di pencarian — *`AvailabilityService` memblokir rentang overlap; validasi larangan dua jadwal overlap (docs diperbarui)*
- [x] **Deteksi booking yang menabrak jadwal maintenance** → status `menunggu_penggantian` — *`ReplacementService::flagConflictingBookings` saat create/update jadwal*
- [x] `ReplacementService`: cari mobil pengganti tersedia otomatis + halaman konfirmasi pengurus (pilih pengganti / batalkan booking) → [feature/penggantian_mobil.md](feature/penggantian_mobil.md) — *assign dalam transaksi+lock & cek ulang; hapus jadwal mengembalikan booking yang belum diganti*

## Fase 4c — Event Armada Bidang (Admin & Pengurus) — ✅ SELESAI
- [x] Migrasi: `events`, `event_vehicles` → [feature/event_bidang.md](feature/event_bidang.md) — *sudah termigrasi di Fase 1*
- [x] Wizard event: info (nama, bidang, tanggal — **durasi fleksibel, boleh >3 hari**, jumlah mobil N) + pemilihan armada (kelompok bebas vs menabrak) — *pratinjau armada via GET; validasi server-side isEligible + jumlah tepat N*
- [x] `EventService`: validasi mobil layak (bisa dipinjam, baik, tanpa maintenance, tanpa event lain), kunci N armada
- [x] Penyelesaian tabrakan: booking terdampak → `menunggu_penggantian` + pilih pengganti per booking (reuse `ReplacementService`, endpoint `/pengurus/events/...`) — *konfirmasi event ditolak selama ada konflik; kandidat otomatis di luar armada event*
- [x] Kuotasi: armada event **dikecualikan** dari kuota bidang (update `BookingService`/query ketersediaan + pengecualian event) — *tercapai lewat desain: event bukan booking (kuota menghitung booking); ketersediaan sudah mengecualikan event sejak Fase 4*
- [x] Scheduler `EventService::autoFinish()` (00:02): event lewat `end_date` → `selesai` — *idempoten; teruji*
- [x] Pembatalan event (armada lepas; booking tergeser tetap di mobil pengganti) + banner notifikasi peminjam tergeser — *booking BELUM diganti kembali dipinjam (docs diperinci); banner dashboard pegawai ada sejak Fase 2*

## Fase 4b — Pengurus: Anggaran Maintenance & Dokumen — ✅ SELESAI
- [x] Migrasi: `vehicles.year`, `vehicle_budgets`, `maintenance_costs`, `generated_documents`; status maintenance (`terjadwal`/`selesai`) → [feature/anggaran_maintenance.md](feature/anggaran_maintenance.md) — *seluruh tabel sudah termigrasi di Fase 1; fase ini hanya implementasi fitur*
- [x] Halaman anggaran: atur total 4 pos (servis, suku cadang, AC, pelumas) per mobil + sisa anggaran real-time — *`/pengurus/vehicles/{v}/budgets` + `BudgetService::summary`*
- [x] Form input nota bengkel dipilah 4 pos + koefisien 1,13 (`BudgetService`) — *`/maintenances/{m}/costs` + kalkulasi ×1,13 live di form; upsert identitas nota*
- [x] Folder `resources/draft_documents/`: template `bend26.blade.php`, `draft_nota.blade.php`, `kartu_inventaris.blade.php` — *+ README variabel template; namespace view `drafts::`*
- [x] Generate PDF bend26 + draft nota per pos + kartu inventaris pemeliharaan (`DocumentService`, dompdf) + halaman unduh — *versioned + arsip versi lama; halaman `/pengurus/documents`; arsip Drive opsional*
- [x] Integrasi Google Sheets API v4 via Service Account (outbound): halaman konfigurasi admin (`IntegrationController`, upload kunci terenkripsi, test koneksi, log sync — lihat [feature/integrasi_google.md](feature/integrasi_google.md)), `SheetsBudgetSync`, task `budgets:sync-sheets` sesuai interval (default 15 menit) — *kunci terenkripsi di database; scheduler tiap 5 menit memeriksa interval sendiri*

## Fase 5 — Pegawai & Pengurus: Peminjaman (Inti) — ✅ SELESAI
- [x] Halaman cari mobil: input tanggal mulai & selesai → [feature/peminjaman.md](feature/peminjaman.md) — *form sticky mobile, date picker native; hasil grid kartu*
- [x] `AvailabilityService`: mobil tanpa booking overlap & tanpa maintenance overlap & status `bisa_dipinjam` & kondisi `baik` — *tersedia sejak Fase 4; dipakai ulang di sini*
- [x] Validasi durasi: maks 3 hari kalender, `start_date >= today`, hari penuh 00:00–24:00 — *`BookingService::rangeErrors` sumber tunggal (halaman, request, service)*
- [x] **Validasi kuota bidang** (maks mobil bersamaan per bidang; default 2, satu bidang 3) → [feature/kuota_bidang.md](feature/kuota_bidang.md) — *booking mendatang mengunci jatah; event otomatis dikecualikan; kartu kuota di pencarian & dashboard*
- [x] Form booking: pilih mobil, alamat tujuan, keperluan
- [x] `BookingService`: transaksi DB + `lockForUpdate` (cegah double-booking bersamaan) — *lock baris kendaraan + booking user; cek ulang ketersediaan dalam transaksi*
- [x] Validasi: 1 peminjaman aktif per pegawai — *berlaku juga untuk pengurus*
- [x] **Route peminjaman dibuka untuk pengurus** (`role:pegawai|pengurus`) — pengurus dapat meminjam mobil seperti pegawai — *teruji: pengurus meminjam & terkena kuota bidangnya*
- [x] Riwayat peminjaman pribadi (status badge, penanda pengembalian otomatis & penggantian mobil) — *+ slot tombol Kembalikan untuk Fase 6; bottom-nav mobile 4 slot (Beranda/Cari/Pinjaman/Profil)*

## Fase 6 — Pengembalian & Keluhan — ✅ SELESAI *(aturan revisi: keluhan = catatan saja)*
- [x] Tombol **"Selesai"** pada peminjaman aktif → [feature/pengembalian.md](feature/pengembalian.md) — *modal pop-up di dashboard & Peminjaman Saya (label "Selesai" sesuai kesepakatan, menggantikan "Kembalikan")*
- [x] **Pop-up textbox keluhan opsional** ("Apakah ada keluhan terkait unit?" — boleh kosong)
- [x] Keluhan = **catatan saja**: TIDAK mengubah kondisi/status kendaraan, TIDAK otomatis maintenance, mobil **tetap bisa dipinjam** — tindak lanjut diputuskan manual pengurus
- [x] Status booking → `dikembalikan`, mobil tersedia kembali segera
- [x] Daftar keluhan untuk pengurus (tab belum/selesai) + tombol **"Tandai selesai"** (resolved) + pintasan jadwalkan maintenance manual
- [x] Kondisi `perlu_diperiksa` menjadi **manual-only** (tombol Tandai/Set baik milik pengurus; keluhan pegawai tidak menyentuhnya) — *docs manajemen_kendaraan & tech.md §4.1 diperbarui*

## Fase 7 — Pengembalian Otomatis (Scheduler)
- [ ] `ReturnService::autoReturn()` + jadwal `dailyAt('00:01')` → [feature/pengembalian_otomatis.md](feature/pengembalian_otomatis.md)
- [ ] Penanda `auto_returned = true` pada booking terlambat
- [ ] Pembatalan otomatis booking `menunggu_penggantian` yang lewat `end_date` tanpa keputusan pengurus (kuota bidang lepas)
- [ ] `EventService::autoFinish()` (00:02): event lewat `end_date` → `selesai` (idempoten)
- [ ] Cron server untuk `schedule:run` (dokumentasikan di README)

## Fase 8 — Dashboard & Laporan
- [ ] Dashboard admin: ringkasan jumlah user per role, per bidang → [feature/dashboard.md](feature/dashboard.md)
- [ ] Dashboard pengurus: mobil tersedia/dipinjam/maintenance, keluhan belum selesai
- [ ] Dashboard pegawai: peminjaman aktif + tombol kembalikan, riwayat singkat
- [ ] Laporan penggunaan kendaraan (filter rentang tanggal, mobil, bidang) + export CSV → [feature/laporan.md](feature/laporan.md)

## Fase 9 — Pengujian & Persiapan Rilis
- [ ] Unit test: `AvailabilityService` (overlap, maintenance, status, **event armada**), validasi durasi 3 hari, hitung kuota bidang (termasuk pengecualian event)
- [ ] Unit test: `BudgetService` (pilah nota, 1,13, sisa anggaran), payload sinkronisasi Sheets
- [ ] Feature test: alur lengkap (login → cari → booking → kembalikan), auto-return, keluhan, penggantian mobil karena maintenance, event armada (wizard, geser booking, auto-finish), generate bend26/draft nota/kartu inventaris
- [ ] Uji manual: akses lintas role ditolak middleware & policy
- [ ] Uji sinkronisasi Google Sheets dengan spreadsheet staging (bukan spreadsheet produksi)
- [ ] Penulisan README (cara install, migrate --seed, cron scheduler)

## Fase 10 — Deployment ke Server Intranet Kantor (via GitHub)

> Alur: laptop (Docker/Sail) → push ke GitHub → server intranet **pull** dari GitHub → jalankan native di server. Server kantor hanya butuh **akses outbound internet** (untuk GitHub & Google API), tidak membuka inbound apa pun.

### 10.1 — Persiapan di Laptop
- [ ] Pastikan semua perubahan ter-commit & ter-push ke branch `main` di GitHub
- [ ] Pastikan `.env` **tidak** ada di repo (cek `.gitignore`); buat salinan `.env.production` sebagai acuan (simpan di luar repo / private)
- [ ] Bersihkan cache lokal sebelum push (`sail artisan config:clear && sail artisan route:clear && sail artisan view:clear`) agar tidak ada file cache ikut tercommit
- [ ] Tag rilis: `git tag v1.0.0 && git push --tags`

### 10.2 — Setup Server Intranet (sekali saja, server Ubuntu Linux)
- [ ] Install di server Ubuntu: `git`, `PHP 8.2+` + ekstensi (`cli, fpm, mysql, mbstring, xml, curl, zip, gd, intl`), `Composer`, `Node.js` (untuk build asset), `MySQL 8`, web server `Nginx`
- [ ] Buat user deploy & folder aplikasi, mis. `/var/www/karen`; `git clone https://github.com/<akun>/karen.git`
- [ ] Salin `.env.production` → `/var/www/karen/.env`, sesuaikan: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=http://<ip-hostname-server>`, `DB_HOST=127.0.0.1`, kredensial DB produksi — **dikerjakan sekali oleh petugas IT saat deploy** (tidak ada kredensial Google di sini; integrasi Google dikonfigurasi via UI, lihat 10.4)
- [ ] Nginx server block: `root /var/www/karen/public`, `index index.php`, PHP-FPM upstream; arahkan DNS/hosts internal kantor ke server (mis. `http://karen.internal`)
- [ ] Permission: `chown -R deploy:www-data /var/www/karen`, `chmod -R 775 storage bootstrap/cache`

### 10.3 — Rilis Perdana di Server
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm install && npm run build` (asset production)
- [ ] `php artisan key:generate` (bila `.env` baru) → `php artisan migrate --seed` (seeder: 5 bidang + admin pertama — **ganti password admin segera**)
- [ ] `php artisan storage:link` (agar foto mobil & dokumen PDF bisa diunduh)
- [ ] Nginx: `systemctl reload nginx`; uji buka `http://karen.internal` dari komputer kantor lain → login admin
- [ ] **Pemicu scheduler (WAJIB — dipilih salah satu, dipasang sekali oleh IT, bukan oleh pengguna aplikasi)**:
      - Linux cron: `crontab -e` → `* * * * * cd /var/www/karen && php artisan schedule:run >> /dev/null 2>&1`, atau
      - Linux systemd: unit long-running `php artisan schedule:work` (`Restart=always`), atau
      - Windows Task Scheduler: task tiap 1 menit menjalankan `php artisan schedule:run`
- [ ] Uji auto-return: cek log Laravel setelah 00:01 bahwa `ReturnService::autoReturn()` berjalan
- [ ] Peringatan scheduler mati di dashboard admin (deteksi via timestamp eksekusi terakhir > 1 hari)

### 10.4 — Integrasi Google di Produksi (Sheets API / Drive / Apps Script)
> Karen hanya melakukan panggilan **outbound** ke Google; tidak ada webhook/akses masuk, sehingga aman di balik jaringan intranet.

- [ ] Buat **Service Account** di Google Cloud Console (project kantor), aktifkan **Google Sheets API** (dan **Google Drive API** bila arsip sekunder ke Drive diinginkan), unduh kunci JSON — simpan di luar repo, **jangan pernah commit kunci ke GitHub**
- [ ] Bagikan (share) spreadsheet anggaran produksi ke email service account (role Editor)
- [ ] Pastikan firewall/proxy server kantor mengizinkan **outbound HTTPS** ke: `oauth2.googleapis.com`, `sheets.googleapis.com`, `www.googleapis.com` (Drive API) — uji dari server: `curl -I https://sheets.googleapis.com`
- [ ] Setelah aplikasi berjalan: login sebagai admin → **Konfigurasi Integrasi Google** (`/admin/integrasi/google`) → upload kunci JSON → paste URL spreadsheet → **Test Koneksi** hingga semua ✅ → aktifkan → "Sinkron Sekarang" (semua konfigurasi runtime via UI, tanpa edit file server)
- [ ] Uji task: `php artisan budgets:sync-sheets` manual dari server → cek spreadsheet berubah/tarik data; lalu pastikan scheduler otomatis menjalankannya sesuai interval
- [ ] **Google Apps Script**: TIDAK dipakai untuk memanggil Karen (Apps Script hidup di cloud Google dan tidak bisa menjangkau intranet). Bila nanti butuh otomasi di sisi spreadsheet, Apps Script hanya boleh bekerja **di dalam spreadsheet** (manipulasi sheet/trigger onEdit) — semua data tetap mengalir lewat Sheets API
- [ ] Tambahkan logging & alert sederhana: gagal sync tercatat di `storage/logs/laravel.log` + tampil badge "sync gagal" di dashboard pengurus agar tidak diam-diam rusak

### 10.5 — Update Rutin (laptop → GitHub → server)
- [ ] Laptop: develop di Sail → `sail test` hijau → push ke GitHub
- [ ] Server: `cd /var/www/karen && git pull` → `composer install --no-dev` (bila dependency berubah) → `npm run build` (bila asset berubah) → `php artisan migrate --force` (bila ada migrasi baru) → `php artisan optimize`
- [ ] (Opsional, setelah stabil) buat script `deploy.sh` satu klik di server yang merangkai langkah di atas + rollback `git checkout` bila gagal

## Definisi Selesai (Definition of Done) per Task
1. Fitur berfungsi sesuai dokumen `feature/` terkait.
2. Validasi form lengkap dengan pesan error Bahasa Indonesia.
3. Route terlindungi middleware role yang benar.
4. Test otomatis lulus untuk logika kritis (ketersediaan, durasi, auto-return).
5. UI konsisten dengan layout & badge status yang ada.
