# Skenario 03 — Pengurus: Kendaraan, Maintenance & Penggantian Mobil (P1)

**Akun**: `pengurus@karen.test` / `password` + `pegawai@karen.test`
untuk membuat booking yang akan ditabrak.

## A. Data Kendaraan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Data Kendaraan** | Grid 4 mobil seeder; badge status & kondisi; tab filter | ok | |
| A2 | **+ Tambah**: tahun `1975` | Ditolak (1980–tahun berjalan+1) | ok | |
| A3 | Tambah lengkap + **foto** (>2 MB) | Ditolak: maks 2 MB | revisi | untuk peringatan mohon diganti dari "Foto maksimal berukuran 2048 kilobita" menjadi "Foto maksimal berukuran 2 MB"|
| A4 | Tambah lengkap + foto valid (jpg/png kecil) | Tersimpan; foto tampil di kartu | ok | |
| A5 | Tambah dengan plat yang sudah ada | Ditolak: plat unik | ok | |
| A6 | **Ubah** mobil → ganti foto | Foto baru menggantikan lama | ok | |
| A7 | **Blokir peminjaman** satu mobil | Badge berubah “Tidak bisa dipinjam”; mobil TIDAK muncul di pencarian pegawai | ok | |
| A8 | **Izinkan dipinjam** kembali | Muncul lagi di pencarian | ok | |
| A9 | **Tandai perlu diperiksa** (manual) | Badge merah; mobil tidak bisa dipinjam; lalu **Set kondisi baik** → pulih | ok | |
| A10 | **Nonaktifkan** (hapus) mobil | Menghilang dari daftar tab Tersedia; riwayat booking lama tetap ada di monitoring | ok | |
| A11 | **Input skema baru** data kurang Lengkap | Data input untuk Nama / Unit, Plat nomor, Tahun Pembuatan, Kapasitas (kursi), foto kendaraan | baru | input data yang wajib adalah 5 field tersebut, namun untuk bisa ditambahkan terkait data sekunder nya yang bisa dilengkapi (tidak wajib di input ketika menambah data kendaraan) yaitu : Nomor Rangka, Nomor Mesin, Tahun Pembuatan, Tanggal Pajak Tahunan, Tanggal Pajak 5 Tahunan. Data sekunder tersebut akan terlihat ketika menekan tombol detail kendaraan di halaman Data Kendaraan, selain itu tambah notifikasi terhadap user Pengurus apabila date now adalah 3 minggu sebelum jatuh tempo tanggal pajak tahunan ataupun tanggal pajak 5 tahunan |
| A12 | **input skema baru** dengan role pegawai, ketika pemilihan kendaraan pada rentang tanggal x sampai tanggal y | semua mobil yang di maintenance tetap ditampilkan namun tidak bisa diklik atau dipilih dengan memberikan keterangan sedang Maintenance, sehingga pegawai tidak perlu bertanya kepada pengurus kenapa mobil A tidak keluar di list, jadi ketika maintenance tetap dimunculkan dalam list namun tidak bisa dipilih, dan ketika dipinjam oleh pegawai lain maka mobil tersebut tidak muncul dalam pencarian mobil| baru | untuk disesuaikan skema baru |

## B. Jadwal Maintenance

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Menu **Jadwal Maintenance** → tambah jadwal mobil A: **H+20..H+22** | Tersimpan “Terjadwal” | ok | |
| B2 | Cari mobil A di rentang H+21..H+23 (pegawai) | Mobil A tidak tersedia; rentang H+23+ tersedia | ok | |
| B3 | Tambah jadwal mobil A lagi yang **bertumpuk** (H+22..H+24) | Ditolak: jadwal bertumpuk | ok | mobil A sudah tidak tampil dalam mobil yang bisa dipinjam, karena H+20 .. H+22 terjadwal maintenance |
| B4 | Selesai < mulai | Ditolak | revisi | penjadwalan maintenance masih bisa memilih sebelum date now |
| B5 | **Ubah tanggal** jadwal menjauh (H+25..H+26) → cek ketersediaan H+20..H+22 | Mobil A **tersedia kembali** di rentang lama | ok | |
| B6 | **Tandai selesai** jadwal | Status Selesai; mobil bebas di rentangnya | ok | |
| B7 | **input skema baru** peminjaman yang ditabrak oleh jadwal maintenance | Pegawai meminjam Mobil A dengan jadwal pinjam pada tanggal 20 sampai 22, tapi jadwal maintenance mobil A di input oleh pengurus tanggal 22 sampai tanggal 24, buat peminjaman oleh pegawai tanggal 20 - 21 tetap menggunakan Mobil A, dan untuk tanggal 22 nya menggunakan mobil pengganti yang lain (menggunakan skema penggantian mobil) | baru | |

## C. Penggantian Mobil (menabrak booking)

Siapkan: pegawai membuat booking mobil A **H+20..H+21** (3 hari
maks). Mobil B & C bebas.

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Buat jadwal maintenance mobil A **H+20..H+22** | Pesan “1 peminjaman terdampak”; otomatis dibawa ke halaman **Penggantian Mobil** | ok | |
| C2 | Lihat halaman Penggantian Mobil | Booking pegawai berstatus **menunggu pengganti**; kandidat pengganti tampil (B/C) **tanpa mobil A** | ok | |
| C3 | Login pegawai → dashboard | Kartu aktif menampilkan “mobil sedang diproses penggantian” (tanpa tombol Selesai) | ok | |
| C4 | Kembali pengurus → pilih **mobil B** sebagai pengganti | Booking kembali **Dipinjam** dengan mobil B | ok | |
| C5 | Riwayat pegawai | Badge **“Diganti dari {mobil A}”** | ok | |
| C6 | Cari mobil B rentang H+20..H+21 (user lain) | Mobil B tidak tersedia (dipakai booking bergeser) | ok | |
| C7 | Ulangi dengan kasus **tidak ada kandidat**: buat semua mobil lain sibuk/diblokir lalu jadwalkan maintenance pada mobil tersisa yang ada bookingnya → halaman konflik | Tampil peringatan “tidak ada kandidat”; tombol **Batalkan Peminjaman** tersedia | ok | |
| C8 | Tekan **Batalkan Peminjaman** | Booking **Dibatalkan**; kuota bidang pegawai lepas (bisa booking lagi) | ok | |
| C9 | **Hapus jadwal** maintenance saat masih ada booking menunggu (buat kasus baru) | Pesan “N peminjaman dikembalikan ke mobil semula”; booking kembali Dipinjam di mobil lama | ok | |

## D. Maintenance vs Event (dua arah)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Buat **event** (skenario 05) mobil X H+30..H+31 → coba buat maintenance mobil X H+31..H+32 | Ditolak: menabrak armada event | ok | |
| D2 | Buat maintenance mobil Y H+40..H+41 → coba masukkan mobil Y ke event H+40..H+42 (skenario 05) | Mobil Y **tidak layak**/tidak muncul sebagai opsi | ok | daripada tidak terlihat lebih baik tetap dimunculkan tetapi checkbox nya tidak bisa diklik dan ada tulisan Maintenance pada tanggal x sampai tanggal y |
| D3 | Buat event dan memilih kendaraan yang terjadwal dipinjam oleh pegawai lain di tanggal yang sama | sudah sesuai tetap muncul di penggantian mobil | baru | karena fitur penggantian mobil digunakan tidak hanya untuk jadwal maintenance tetapi juga untuk fitur event armada, maka tombol atau menu penggantian mobil sebaiknya tidak di tempatkan di dalam halaman jadwal maintenance tetapi dibuatkan menu baru menurut saya |
