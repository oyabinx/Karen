# Skenario 09 — Akses Admin Penuh & Log Aktivitas (P1)

**Akun**: `admin@karen.test` + dua akun pengurus (buat duplikat lewat
admin: `pengurus2@karen.test`) untuk uji "pengurus mana yang mengubah".

> Mengikuti keputusan pasca-UAT 03: admin membuka seluruh menu sisi
> pengurus (dukungan komplain) + log aktivitas menjawab "siapa
> mengubah apa, kapan".

## A. Admin membuka seluruh menu pengurus

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Login admin → sidebar | Muncul seksi **Kendaraan** & **Monitoring** lengkap + Administrasi (…, **Log Aktivitas**) | ⬜ | |
| A2 | Buka Keluhan Unit (`/pengurus/complaints`) | 200 — daftar keluhan tampil | ⬜ | |
| A3 | Buka Monitoring (`/pengurus/bookings`) | 200 — filter & daftar peminjaman | ⬜ | |
| A4 | Buka Laporan (`/pengurus/reports`) + Export CSV | 200; CSV terunduh | ⬜ | |
| A5 | Coba `Tandai selesai` satu keluhan (admin) | Berfungsi (admin kini ikut bisa menindaklanjuti) | ⬜ | |
| A6 | Login admin → `/pegawai/search` | **Tetap 403** — admin tidak meminjam | ⬜ | |

## B. Log Aktivitas — siapa mengubah apa

Siapkan: pengurus1 & pengurus2 (dua akun berbeda).

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Login **pengurus1** → tambah kendaraan "Log Uji 1" | Tersimpan | ⬜ | |
| B2 | Logout → login **pengurus2** → ubah status "Log Uji 1" jadi tidak bisa dipinjam | Tersimpan | ⬜ | |
| B3 | Login **admin** → **Log Aktivitas** | Dua entri terbaru: "pengurus1 Menambah Kendaraan Log Uji 1" & "pengurus2 Mengubah Kendaraan Log Uji 1 (kolom: status)" | ⬜ | |
| B4 | Buka **Detail perubahan** entri pengurus2 | Tabel: status `bisa_dipinjam` → `tidak_bisa_dipinjam` | ⬜ | |
| B5 | Filter **pelaku = pengurus1** | Hanya entri pengurus1 | ⬜ | |
| B6 | Filter aksi = Menghapus / objek = Vehicle / cari "Log Uji" / rentang tanggal | Hasil sesuai | ⬜ | |
| B7 | Pegawai melakukan booking + Selesai berkeluhan | Log aktivitas mencatat Menambah Peminjaman, Mengubah (status), Menambah Keluhan | ⬜ | |
| B8 | Jalankan autoReturn (lihat skenario 08 langkah 2) | Entri baru berpelaku **"Sistem (otomatis)"** | ⬜ | |
| B9 | Login pegawai → `/admin/activity-logs` | 403 | ⬜ | |

## C. Keamanan log

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Admin ubah user (nama + password baru sekaligus) → lihat log | `name` tercatat; **password tidak pernah muncul** (nilainya maupun kolomnya) | ⬜ | |
| C2 | Upload kunci service account di Integrasi Google → periksa log terkait | Isi kunci **tidak pernah tercatat** | ⬜ | |
| C3 | Nonaktifkan lalu aktifkan kembali satu user | Log Menghapus + perubahan `deleted_at` (aktifkan kembali) | ⬜ | |
