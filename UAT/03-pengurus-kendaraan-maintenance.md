# Skenario 03 — Pengurus: Kendaraan, Maintenance & Penggantian Mobil (P1)

**Akun**: `pengurus@karen.test` / `password` + `pegawai@karen.test`
untuk membuat booking yang akan ditabrak.

## A. Data Kendaraan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Data Kendaraan** | Grid 4 mobil seeder; badge status & kondisi; tab filter | ⬜ | |
| A2 | **+ Tambah**: tahun `1975` | Ditolak (1980–tahun berjalan+1) | ⬜ | |
| A3 | Tambah lengkap + **foto** (>2 MB) | Ditolak: maks 2 MB | ⬜ | |
| A4 | Tambah lengkap + foto valid (jpg/png kecil) | Tersimpan; foto tampil di kartu | ⬜ | |
| A5 | Tambah dengan plat yang sudah ada | Ditolak: plat unik | ⬜ | |
| A6 | **Ubah** mobil → ganti foto | Foto baru menggantikan lama | ⬜ | |
| A7 | **Blokir peminjaman** satu mobil | Badge berubah “Tidak bisa dipinjam”; mobil TIDAK muncul di pencarian pegawai | ⬜ | |
| A8 | **Izinkan dipinjam** kembali | Muncul lagi di pencarian | ⬜ | |
| A9 | **Tandai perlu diperiksa** (manual) | Badge merah; mobil tidak bisa dipinjam; lalu **Set kondisi baik** → pulih | ⬜ | |
| A10 | **Nonaktifkan** (hapus) mobil | Menghilang dari daftar tab Tersedia; riwayat booking lama tetap ada di monitoring | ⬜ | |

## B. Jadwal Maintenance

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Menu **Jadwal Maintenance** → tambah jadwal mobil A: **H+20..H+22** | Tersimpan “Terjadwal” | ⬜ | |
| B2 | Cari mobil A di rentang H+21..H+23 (pegawai) | Mobil A tidak tersedia; rentang H+23+ tersedia | ⬜ | |
| B3 | Tambah jadwal mobil A lagi yang **bertumpuk** (H+22..H+24) | Ditolak: jadwal bertumpuk | ⬜ | |
| B4 | Selesai < mulai | Ditolak | ⬜ | |
| B5 | **Ubah tanggal** jadwal menjauh (H+25..H+26) → cek ketersediaan H+20..H+22 | Mobil A **tersedia kembali** di rentang lama | ⬜ | |
| B6 | **Tandai selesai** jadwal | Status Selesai; mobil bebas di rentangnya | ⬜ | |

## C. Penggantian Mobil (menabrak booking)

Siapkan: pegawai membuat booking mobil A **H+20..H+21** (3 hari
maks). Mobil B & C bebas.

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Buat jadwal maintenance mobil A **H+20..H+22** | Pesan “1 peminjaman terdampak”; otomatis dibawa ke halaman **Penggantian Mobil** | ⬜ | |
| C2 | Lihat halaman Penggantian Mobil | Booking pegawai berstatus **menunggu pengganti**; kandidat pengganti tampil (B/C) **tanpa mobil A** | ⬜ | |
| C3 | Login pegawai → dashboard | Kartu aktif menampilkan “mobil sedang diproses penggantian” (tanpa tombol Selesai) | ⬜ | |
| C4 | Kembali pengurus → pilih **mobil B** sebagai pengganti | Booking kembali **Dipinjam** dengan mobil B | ⬜ | |
| C5 | Riwayat pegawai | Badge **“Diganti dari {mobil A}”** | ⬜ | |
| C6 | Cari mobil B rentang H+20..H+21 (user lain) | Mobil B tidak tersedia (dipakai booking bergeser) | ⬜ | |
| C7 | Ulangi dengan kasus **tidak ada kandidat**: buat semua mobil lain sibuk/diblokir lalu jadwalkan maintenance pada mobil tersisa yang ada bookingnya → halaman konflik | Tampil peringatan “tidak ada kandidat”; tombol **Batalkan Peminjaman** tersedia | ⬜ | |
| C8 | Tekan **Batalkan Peminjaman** | Booking **Dibatalkan**; kuota bidang pegawai lepas (bisa booking lagi) | ⬜ | |
| C9 | **Hapus jadwal** maintenance saat masih ada booking menunggu (buat kasus baru) | Pesan “N peminjaman dikembalikan ke mobil semula”; booking kembali Dipinjam di mobil lama | ⬜ | |

## D. Maintenance vs Event (dua arah)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Buat **event** (skenario 05) mobil X H+30..H+31 → coba buat maintenance mobil X H+31..H+32 | Ditolak: menabrak armada event | ⬜ | |
| D2 | Buat maintenance mobil Y H+40..H+41 → coba masukkan mobil Y ke event H+40..H+42 (skenario 05) | Mobil Y **tidak layak**/tidak muncul sebagai opsi | ⬜ | |
