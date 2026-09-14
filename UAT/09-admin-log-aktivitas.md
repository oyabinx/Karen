# Skenario 09 — Akses Admin Penuh & Log Aktivitas (P1)

**Akun**: `admin@karen.test` + dua akun pengurus (buat duplikat lewat
admin: `pengurus2@karen.test`) untuk uji "pengurus mana yang mengubah".

> Mengikuti keputusan pasca-UAT 03: admin membuka seluruh menu sisi
> pengurus (dukungan komplain) + log aktivitas menjawab "siapa
> mengubah apa, kapan".

## A. Admin membuka seluruh menu pengurus

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Login admin → sidebar | Muncul seksi **Kendaraan** & **Monitoring** lengkap + Administrasi (…, **Log Aktivitas**) | ok | |
| A2 | Buka Keluhan Unit (`/pengurus/complaints`) | 200 — daftar keluhan tampil | ok | |
| A3 | Buka Monitoring (`/pengurus/bookings`) | 200 — filter & daftar peminjaman | ok | |
| A4 | Buka Laporan (`/pengurus/reports`) + Export CSV | 200; CSV terunduh | ok | |
| A5 | Coba `Tandai selesai` satu keluhan (admin) | Berfungsi (admin kini ikut bisa menindaklanjuti) | ok | |
| A6 | Login admin → `/pegawai/search` | **Tetap 403** — admin tidak meminjam | ok | |

## B. Log Aktivitas — siapa mengubah apa

Siapkan: pengurus1 & pengurus2 (dua akun berbeda).

> **PENJELASAN B1 (dari pertanyaan UAT):** "Log Uji 1" hanyalah **nama
> contoh kendaraan** yang dipakai saat uji — bebas diganti nama lain.
> Pengurus **tidak perlu membuka log** (memang tidak boleh); pengurus
> hanya membuat/ mengubah datanya seperti biasa lewat menu Kendaraan.
> Yang membuka halaman Log Aktivitas adalah **admin** (langkah B3).
> Alur: pengurus1 menambah kendaraan → pengurus2 mengubahnya → admin
> membuka Log Aktivitas dan melihat keduanya tercatat.

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Login **pengurus1** → menu Kendaraan → Tambah Kendaraan, isi nama bebas (mis. "Log Uji 1") | Tersimpan (muncul di daftar kendaraan) | ok | nama "Log Uji 1" hanya contoh — pengurus tidak membuka log |
| B2 | Logout → login **pengurus2** → di halaman Kendaraan ubah status "Log Uji 1" jadi tidak bisa dipinjam | Tersimpan | ok | |
| B3 | Login **admin** → **Log Aktivitas** | Dua entri terbaru: "pengurus1 Menambah Kendaraan Log Uji 1" & "pengurus2 Mengubah Kendaraan Log Uji 1 (kolom: status)" | ok | |
| B4 | Buka **Detail perubahan** entri pengurus2 | Tabel: status `bisa_dipinjam` → `tidak_bisa_dipinjam` | ok | |
| B5 | Filter **pelaku = pengurus1** | Hanya entri pengurus1 | ok | |
| B6 | Filter aksi = Menghapus / objek = Vehicle / cari "Log Uji" / rentang tanggal; lalu (baru) **admin** hapus kendaraan uji → cek monitoring | Hasil sesuai; tombol **Hapus** muncul **hanya untuk admin**; kendaraan dihapus **soft delete**; booking/riwayat kendaraan itu **tetap tampil & bisa difilter** di Semua Peminjaman (berlabel "(nonaktif)") | revisi → uji ulang | diimplementasikan 2026-09-14: tombol Hapus admin-only di halaman Kendaraan; pengurus 403; relasi booking withTrashed; filter mobil monitoring memuat unit nonaktif |
| B7 | Pegawai melakukan booking + Selesai berkeluhan | Log aktivitas mencatat Menambah Peminjaman, Mengubah (status), Menambah Keluhan | ok | |
| B7b | **Pembatalan mandiri**: pegawai batalkan booking masa depan (skenario 01-D9) | Log mencatat **Mengubah Peminjaman (kolom: status, cancelled_at)** dengan pelaku pegawai tersebut | ok | |
| B8 | Jalankan autoReturn (lihat skenario 08 langkah 2) | Entri baru berpelaku **"Sistem (otomatis)"** | ok | |
| B9 | Login pegawai → `/admin/activity-logs` | 403 | ok | |

## C. Keamanan log

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Admin klik **🔑 Reset Sandi** pada user → lihat log | Kata sandi baru acak **ditampilkan SEKALI** di pesan sukses (untuk disampaikan ke pegawai, lalu diganti sendiri di menu Profil); log tercatat "Mereset kata sandi …" **tanpa nilai sandi** | revisi → uji ulang | diimplementasikan 2026-09-14. Catatan desain: kata sandi lama mustahil "ditampilkan" (hash satu arah) — alur resmi lupa sandi = minta admin reset; skema lupa password TIDAK ditambahkan |
| C2 | Upload kunci service account di Integrasi Google → periksa log terkait | Isi kunci **tidak pernah tercatat** | ok | |
| C3 | Nonaktifkan lalu aktifkan kembali satu user | Log **"Menonaktifkan Pengguna {nama}"** + aktifkan kembali tercatat sebagai perubahan `deleted_at` | revisi → uji ulang | diimplementasikan 2026-09-14 (label kendaraan tetap "Menghapus Kendaraan") |
