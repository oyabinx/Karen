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

## C2b. Keluhan Unit — per Kendaraan + Jadwalkan Langsung *(rev user pasca-UAT 04)*

**Skenario**: pegawai A kembalikan mobil A dengan keluhan "setir tidak center"; hari berikutnya pegawai B pinjam mobil A yang sama lalu kembalikan dengan keluhan "rem bermasalah".

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| K1 | Menu **Keluhan Unit** (tab Belum Selesai) | **Satu kartu per kendaraan**: nama + plat + jumlah keluhan aktif; semua keluhan unit tercantum (isi, pelapor, waktu pengembalian) + tombol Tandai selesai per keluhan | ⬜ | |
| K2 | Cek kartu mobil A | **Kedua keluhan tergabung dalam satu kartu** ("setir tidak center" oleh pegawai A + "rem bermasalah" oleh pegawai B) — badge "2 keluhan aktif" | ⬜ | |
| K3 | Tekan **🔧 Jadwalkan Maintenance** | Modal: tanggal mulai/selesai (mulai ≥ hari ini) + **catatan terisi otomatis gabungan**: "Keluhan: rem bermasalah (Pegawai B, …); setir tidak center (Pegawai A, …)" — tanpa perlu memilih mobil | ⬜ | |
| K4 | Isi tanggal → **Simpan Jadwal** | Tersimpan; menjalankan alur existing: bila menabrak booking aktif → diarahkan ke Penggantian Mobil; cek Jadwal Maintenance → jadwal baru dengan catatan gabungan, **mobil sudah terpilih otomatis** | ⬜ | |
| K5 | Kembali ke Keluhan Unit setelah jadwalkan | Keluhan tetap tampil (ditandai selesai **manual** oleh pengurus setelah tindak lanjut) | ⬜ | |
| K6 | Sunting catatan di modal sebelum simpan | Perubahan catatan ikut tersimpan (catatan bisa diedit) | ⬜ | |
| K7 | Refresh Keluhan Unit setelah jadwalkan (unit masih terjadwal) | Tombol hijau **hilang**, diganti **"🛠 Sedang Maintenance (rentang tanggal) →"** yang membuka halaman Jadwal Maintenance; setelah jadwal ditandai **selesai**, tombol jadwalkan kembali tampil | ⬜ | |
