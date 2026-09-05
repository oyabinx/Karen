# UAT Karen — Panduan Penguji

Dokumen ini memandu **User Acceptance Test manual** aplikasi Karen.
Ikuti checklist per skenario, centang hasilnya, dan catat setiap
keanehan/bug di [bug-log.md](bug-log.md).

## Cara Menggunakan

1. **Nyalakan aplikasi** (laptop pengembangan):
   ```bash
   cd /home/oyabin/Projects/Karen
   ./vendor/bin/sail up -d        # tunggu mysql healthy: ./vendor/bin/sail ps
   ./vendor/bin/sail npm run build  # WAJIB setelah ada perubahan tampilan!
   # (alternatif saat sesi UAT panjang: ./vendor/bin/sail npm run dev
   #  — asset otomatis ter-update tanpa build ulang)
   # buka http://localhost
   ```
2. **Mulai dari keadaan bersih (disarankan tiap sesi UAT):**
   ```bash
   ./vendor/bin/sail artisan migrate:fresh --seed
   ```
   Perintah ini MENGHAPUS semua data uji dan mengembalikan data awal
   (5 bidang, 10 seksi, 3 akun, 4 mobil).
3. Buka file skenario (01–08) secara berurutan; isi kolom **Status**
   (✅ lancar / ❌ gagal) dan **Catatan**.
4. Menemukan bug? Catat di [bug-log.md](bug-log.md) selengkap
   mungkin (langkah reproduksi + tangkapan layar bila perlu).

## Akun Uji (setelah `migrate:fresh --seed`)

| Email | Password | Role | Fungsi utama di UAT |
|-------|----------|------|---------------------|
| `admin@karen.test` | `password` | Admin | user, organisasi, integrasi |
| `pengurus@karen.test` | `password` | Pengurus | kendaraan, maintenance, anggaran, event, laporan — **juga bisa meminjam mobil** |
| `pegawai@karen.test` | `password` | Pegawai | peminjaman & pengembalian |

> Password semua akun adalah `password`. Skenario 1 termasuk uji
> ganti password — jika Anda menggantinya, catat password baru Anda.

## Notasi Tanggal

Checklist memakai **H = hari uji**: H berarti hari ini, H+1 besok,
dst. Gunakan tanggal nyata di form.

## Daftar Skenario

| File | Skenario | Prioritas |
|------|----------|-----------|
| [01-inti-login-profil-peminjaman.md](01-inti-login-profil-peminjaman.md) | Login, profil (HP wajib), cari mobil, booking, **Selesai + keluhan**, riwayat | **P1 — wajib** |
| [02-admin-manajemen.md](02-admin-manajemen.md) | Manajemen user, impor CSV, bidang/seksi/kuota, halaman integrasi | P1 |
| [03-pengurus-kendaraan-maintenance.md](03-pengurus-kendaraan-maintenance.md) | CRUD kendaraan, status/kondisi manual, jadwal maintenance, penggantian mobil | P1 |
| [04-pengurus-anggaran-dokumen.md](04-pengurus-anggaran-dokumen.md) | Anggaran 4 pos per tahun, input nota ×1,13, bend26/draft nota/kartu inventaris | P1 |
| [05-event-armada.md](05-event-armada.md) | Event armada bidang: wizard, menabrak booking, konfirmasi, pembatalan | P2 |
| [06-monitoring-laporan-dashboard.md](06-monitoring-laporan-dashboard.md) | Monitoring, laporan + export CSV, dashboard 3 role | P2 |
| [07-responsive-mobile.md](07-responsive-mobile.md) | Tampilan mobile 360px: drawer, bottom-nav, modal, kartu | P2 |
| [08-scheduler-otomatis-opsional.md](08-scheduler-otomatis-opsional.md) | Pengembalian otomatis & penutupan event (perlu trik data) | P3 — opsional |

## Tips Mencari Bug

- Coba **urutan tidak wajar**: tekan tombol dua kali, kembali
  (back) di tengah form, refresh setelah submit.
- Coba **nilai batas**: tanggal kemarin, durasi tepat 3 vs 4 hari,
  kuota tepat penuh, nomor HP 8 digit vs 15 digit.
- Coba **dua tab sekaligus** (Booking mobil sama di dua akun).
- Perhatikan **pesan error**: harusnya berbahasa Indonesia dan
  menjelaskan alasannya.
