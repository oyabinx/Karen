# Skenario 06 — Monitoring, Laporan & Dashboard (P2)

**Akun**: `pengurus@karen.test` **atau admin** (monitoring/laporan),
semua role untuk dashboard.

## A. Monitoring Semua Peminjaman

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Semua Peminjaman** | Daftar semua booking + badge status + penanda (Ditutup otomatis / Diganti dari unit / keluhan) | ⬜ | |
| A2 | Filter: nama peminjam | Hanya booking miliknya | ⬜ | |
| A3 | Filter: mobil tertentu | Hanya mobil itu | ⬜ | |
| A4 | Filter: bidang | Hanya anggota bidang itu | ⬜ | |
| A5 | Filter: status `menunggu_penggantian` | Hanya yang menunggu | ⬜ | |
| A6 | Filter rentang tanggal yang memotong booking (mis. from=tengah booking) | Booking yang **menyentuh** rentang tetap tampil | ⬜ | |
| A7 | Kombinasi filter + pagination | Konsisten (query string terjaga); **halaman 1 tanpa tombol Sebelumnya, halaman terakhir tanpa tombol Berikutnya** (hilang, bukan disabled — revisi pasca-UAT) | ⬜ | |
| A8 | **Chip waktu pembatalan**: buat booking masa depan lalu Batalkan (skenario 01-D9) → lihat monitoring | Booking tampil berstatus **Dibatalkan** + chip **"Dibatalkan {tanggal & jam}"** (bukan "dikembalikan") | ⬜ | |

## B. Laporan & Export CSV

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Menu **Laporan** | Default bulan berjalan: kartu totals + Rekap per Mobil + Realisasi Anggaran per Pos | ⬜ | |
| B2 | Cek **Total Hari Pakai** vs booking manual (contoh 1–3 Sep = 3 hari) | Angka cocok | ⬜ | |
| B3 | Rekap per Mobil | Jumlah & hari pakai sesuai data; terurut terbesar | ⬜ | |
| B4 | Anggaran per pos: cocokkan dengan halaman Anggaran (×1,13) | Konsisten | ⬜ | |
| B5 | Ubah rentang ke bulan lalu (kosong) | Totals 0 / tabel kosong tanpa error | ⬜ | |
| B6 | **Export CSV** → buka di Excel/LibreOffice | Terbuka rapi (BOM UTF-8, pemisah `;`): 11 kolom; baris sesuai filter; bagian anggaran menyertai | ⬜ | |
| B7 | Export dengan filter bidang tertentu | CSV hanya berisi bidang itu | ⬜ | |

## C. Dashboard per Role

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Dashboard **admin** | Chip user per role; tabel per bidang (kuota/seksi/anggota); kartu **Kesehatan Scheduler** (info waktu terakhir; di dev tak ada peringatan merah) | ⬜ | |
| C2 | Dashboard **pengurus** | 5 kartu (termasuk Menunggu Pengganti & Keluhan) + kartu **kuota pribadi** + daftar Peminjaman Berjalan Hari Ini + anggaran per pos + event terjadwal | ⬜ | |
| C3 | Dashboard **pegawai** | Banner HP (bila kosong), kartu kuota, kartu peminjaman aktif + **tombol adaptif** (Selesai bila hari ini ≥ mulai / Batalkan bila belum mulai), Riwayat Terakhir (5) | ⬜ | |
| C4 | Buat keluhan baru (skenario 01-D5) → refresh dashboard pengurus | Kartu Keluhan naik +1 | ⬜ | |
| C5 | Dashboard **pengurus** dengan pajak jatuh tempo ≤3 minggu (skenario 03-A11b) | Banner **"⚠ Peringatan Pajak Kendaraan"** dengan nama unit + jenis + sisa hari / "LEWAT n hari" | ⬜ | |
