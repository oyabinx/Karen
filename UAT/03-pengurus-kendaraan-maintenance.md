# Skenario 03 — Pengurus: Kendaraan, Maintenance & Penggantian Mobil (P1)

**Akun**: `pengurus@karen.test` / `password` + `pegawai@karen.test`
untuk membuat booking yang akan ditabrak.

> Pembaruan: skenario ini kini juga dapat dijalankan dengan akun ADMIN
> (seluruh menu sisi pengurus dibuka untuk admin — lihat skenario 09).

## A. Data Kendaraan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Data Kendaraan** | Grid 4 mobil seeder; badge status & kondisi; tab filter | ok | |
| A2 | **+ Tambah**: tahun `1975` | Ditolak (1980–tahun berjalan+1) | ok | |
| A3 | Tambah lengkap + **foto** (>2 MB) | Ditolak: maks 2 MB | revisi | untuk peringatan mohon diganti dari "Foto maksimal berukuran 2048 kilobita" menjadi "Foto maksimal berukuran 2 MB"|
| A3b | **Verifikasi ulang A3**: unggah foto >2 MB | Pesan: **"Foto maksimal berukuran 2 MB."** (bukan "2048 kilobita") | ok | |
| A4 | Tambah lengkap + foto valid (jpg/png kecil) | Tersimpan; foto tampil di kartu | ok | |
| A5 | Tambah dengan plat yang sudah ada | Ditolak: plat unik | ok | |
| A6 | **Ubah** mobil → ganti foto | Foto baru menggantikan lama | ok | |
| A7 | **Blokir peminjaman** satu mobil | Badge berubah “Tidak bisa dipinjam”; mobil TIDAK muncul di pencarian pegawai | ok | |
| A8 | **Izinkan dipinjam** kembali | Muncul lagi di pencarian | ok | |
| A9 | **Tandai perlu diperiksa** (manual) | Badge merah; mobil tidak bisa dipinjam; lalu **Set kondisi baik** → pulih | ok | |
| A10 | **Nonaktifkan** (hapus) mobil | Menghilang dari daftar tab Tersedia; riwayat booking lama tetap ada di monitoring | ok | |
| A11 | **Input skema baru** data kurang Lengkap | Data input untuk Nama / Unit, Plat nomor, Tahun Pembuatan, Kapasitas (kursi), foto kendaraan | baru | input data yang wajib adalah 5 field tersebut, namun untuk bisa ditambahkan terkait data sekunder nya yang bisa dilengkapi (tidak wajib di input ketika menambah data kendaraan) yaitu : Nomor Rangka, Nomor Mesin, Tahun Pembuatan, Tanggal Pajak Tahunan, Tanggal Pajak 5 Tahunan. Data sekunder tersebut akan terlihat ketika menekan tombol detail kendaraan di halaman Data Kendaraan, selain itu tambah notifikasi terhadap user Pengurus apabila date now adalah 3 minggu sebelum jatuh tempo tanggal pajak tahunan ataupun tanggal pajak 5 tahunan |
| A11b | **Uji skema A11 (implementasi selesai)**: Ubah kendaraan → buka **Data Sekunder (opsional)** → isi Nomor Rangka, Nomor Mesin, Pajak Tahunan **H+10** | Tersimpan; tombol **Detail** pada kartu membuka **modal** berisi semua data primer + sekunder; **notifikasi pajak "10 hari lagi"** muncul di dashboard pengurus & Data Kendaraan | ok | |
| A11c | **Uji lewat tempo**: ubah Pajak Tahunan ke **H-3** | Peringatan **merah "LEWAT 3 hari"** di dashboard & Data Kendaraan; tanggal merah di modal Detail | ok | |
| A11d | **Uji jauh dari tempo**: ubah Pajak Tahunan ke **H+60** | Tidak muncul peringatan apa pun | ok | |
| A12 | **input skema baru** dengan role pegawai, ketika pemilihan kendaraan pada rentang tanggal x sampai tanggal y | semua mobil yang di maintenance tetap ditampilkan namun tidak bisa diklik atau dipilih dengan memberikan keterangan sedang Maintenance, sehingga pegawai tidak perlu bertanya kepada pengurus kenapa mobil A tidak keluar di list, jadi ketika maintenance tetap dimunculkan dalam list namun tidak bisa dipilih, dan ketika dipinjam oleh pegawai lain maka mobil tersebut tidak muncul dalam pencarian mobil| baru | untuk disesuaikan skema baru |
| A12b | **Uji skema A12 (implementasi selesai)**: pegawai cari mobil pada rentang yang menabrak jadwal maintenance | Mobil maintenance **TETAP TAMPIL** pada seksi khusus "Sedang Maintenance" — kartu abu, **tidak dapat dipilih**, dengan keterangan **rentang tanggal maintenance-nya**; mobil yang **dipinjam orang lain** tetap tersembunyi | ok | |

## B. Jadwal Maintenance

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Menu **Jadwal Maintenance** → tambah jadwal mobil A: **H+20..H+22** | Tersimpan “Terjadwal” | ok | |
| B2 | Cari mobil A di rentang H+21..H+23 (pegawai) | Mobil A tampil pada seksi **"Sedang Maintenance"** nonaktif dengan keterangan rentang (H+20..H+22); rentang pencarian H+23+ mobil A tersedia normal | ok | |
| B3 | Tambah jadwal mobil A lagi yang **bertumpuk** (H+22..H+24) | Ditolak: jadwal bertumpuk | ok | mobil A sudah tidak tampil dalam mobil yang bisa dipinjam, karena H+20 .. H+22 terjadwal maintenance |
| B4 | Selesai < mulai | Ditolak | revisi | penjadwalan maintenance masih bisa memilih sebelum date now |
| B4b | **Verifikasi ulang B4**: isi tanggal mulai **kemarin** | **Ditolak**: "tanggal mulai tidak boleh sebelum hari ini" | ok | catatan nya untuk notifikasi Tanggal mulai harus berisi tanggal setelah atau sama dengan today. diubah menjadi satu baris di bawah kotak "TAMBAH JADWAL" jangan diletakkan di bawah field Mulai |
| B5 | **Ubah tanggal** jadwal menjauh (H+25..H+26) → cek ketersediaan H+20..H+22 | Mobil A **tersedia kembali** di rentang lama | ok | |
| B6 | **Tandai selesai** jadwal | Status Selesai; mobil bebas di rentangnya | ok | |
| B7 | **input skema baru** peminjaman yang ditabrak oleh jadwal maintenance | Pegawai meminjam Mobil A dengan jadwal pinjam pada tanggal 20 sampai 22, tapi jadwal maintenance mobil A di input oleh pengurus tanggal 22 sampai tanggal 24, buat peminjaman oleh pegawai tanggal 20 - 21 tetap menggunakan Mobil A, dan untuk tanggal 22 nya menggunakan mobil pengganti yang lain (menggunakan skema penggantian mobil) | baru | |
| B7b | **Uji skema B7 — penggantian PARSIAL (implementasi selesai)**: pegawai booking mobil A **H+20..H+21**; pengurus buat maintenance mobil A **H+21..H+22** (menabrak hari terakhir saja) → buka halaman Penggantian Mobil | Tampil seksi biru **"Pengganti sebagian — hanya tanggal yang menabrak"**: "H+21 pakai pengganti, sisa tanggal tetap Mobil A" + kandidat parsial | revisi | setelah di coba dengan skema seperti ini : pegawai@karen.test meminjam mobil dengan plat nomor AB 1608 UH pada tanggal 9 - 11 september, sedangkan pengurus@karen.test menjadwalkan maintenance terhadap mobil plat AB 1608 UH pada tanggal 11 - 13 september, nah yang terjadi adalah peminjaman oleh pegawai@karen.test terganti dari tanggal 9 - 11 september dengan mobil plat AB 1609 UH, dan ketika saya cek dengan user pegawai lain yaitu galih@karen.test untuk mobil AB 1608 UH pada tanggal 9-10 september posisi bisa dipakai padahal harusnya masih bisa dipakai oleh user pegawai@karen.test. yang saya harapkan adalah peminjaman oleh pegawai@karen.test menjadi 2 peminjaman, yang pertama adalah peminjaman AB 1608 UH pada tanggal 9-10 september akibat maintenance oleh pengurus pada tanggal 11 - 13 september, dan juga peminjaman kedua adalah pada tanggal 11 september dengan mobil pengganti AB 1609 UH, sehingga pegawai@karen.test masih dapat memakai kendaraan AB 1608 UH pada tanggal 9-10 september dan status tidak bisa dipinjam oleh pegawai lain pada tanggal tersebut |
| B7c | Pilih kandidat parsial (mis. Mobil B) → cek riwayat pegawai | **Dua peminjaman**: (1) Mobil A H+20..H+20 status Dipinjam; (2) Mobil B H+21..H+21 status Dipinjam + badge **"Diganti dari Mobil A"** | revisi | merujuk pada unit test kode B7b yang sudah saya jelaskan di catatan|
| B7d | **Uji batasan tengah**: pegawai booking mobil A H+20..H+22; maintenance H+21..H+21 (menabrak TENGAH) | Opsi parsial **TIDAK tampil** — hanya penggantian penuh (tabrakan tengah tidak didukung) | revisi | merujuk pada unit test kode B7b begitu juga apabila jadwal maintenance hanya berada di tengah peminjaman, peminjaman yang diganti hanyalah pada tanggal maintenance walaupun cuma 1 hari |

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
| D2b | **Uji skema D2 (implementasi selesai)**: buka wizard event pada rentang yang menabrak maintenance mobil Y | Kelompok ketiga **"🔧 Sedang Maintenance"** tampil — mobil Y tampak **nonaktif** (checkbox tidak bisa diklik) dengan keterangan **rentang maintenance-nya**; mobil layak lain tetap normal | ok | |
| D3 | Buat event dan memilih kendaraan yang terjadwal dipinjam oleh pegawai lain di tanggal yang sama | sudah sesuai tetap muncul di penggantian mobil | baru | karena fitur penggantian mobil digunakan tidak hanya untuk jadwal maintenance tetapi juga untuk fitur event armada, maka tombol atau menu penggantian mobil sebaiknya tidak di tempatkan di dalam halaman jadwal maintenance tetapi dibuatkan menu baru menurut saya |
| D3b | **Uji skema D3 (implementasi selesai)**: cek sidebar pengurus | **Menu mandiri "Penggantian Mobil"** tampil di seksi Kendaraan (terpisah dari Jadwal Maintenance); booking yang ditabrak event juga muncul di sana; halaman konflik event menawarkan **penggantian parsial** (biru) bila hanya tepi rentang yang menabrak | ok | tambahan nya adalah penggantian parsial juga berlaku dengan event armada yang hanya 1 hari saja walupun letaknya di tengah-tengah peminjaman yang 1 hari |
