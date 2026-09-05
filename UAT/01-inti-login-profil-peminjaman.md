# Skenario 01 — Inti: Login, Profil, Peminjaman & Pengembalian (P1)

**Tujuan**: memastikan alur inti self-service bekerja: login, profil
dengan nomor HP wajib, cari mobil, booking ≤3 hari, tombol Selesai
dengan keluhan opsional, dan riwayat.

**Akun**: `pegawai@karen.test` / `password` (lalu `pengurus@karen.test`).

> Sebelum mulai: `./vendor/bin/sail artisan migrate:fresh --seed`
> lalu login. Ganti **H** dengan tanggal hari uji.

## A. Login & Logout

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Buka `http://localhost` tanpa login → klik apapun menu | Diarahkan ke halaman Masuk berjudul “Selamat datang di Karen” | revisi | tampilan localhost tanpa login masih menampilkan tampilan laravel default, langsung saja ketika akses ke localhost diarahkan langsung ke localhost/login, dan langsung diarahkan ke dashboard apabila sesi masih aktif |
| A2 | Login `pegawai@karen.test` + password SALAH | Tetap di halaman masuk, pesan error (tidak menyebut email/password mana yang salah) | ok | |
| A3 | Login benar `pegawai@karen.test` / `password` | Masuk **Dashboard Pegawai**; muncul kartu **Kuota Peminjaman** bidang Anda | ok | |
| A4 | Klik menu (sidebar kiri): Profil Saya, Cari Mobil, Peminjaman Saya | Semua terbuka; menu sesuai role | ok | |
| A5 | Klik **Keluar** | Kembali ke halaman masuk; menekan back tidak bisa masuk dashboard lagi | revisi | ketika posisi sudah logout ketika menekan back di browser, masih bisa melihat tampilan dashboard namun tombol menu tidak bisa digunakan, kalau bisa ketika user menekan tombol back diarahkan langsung ke halaman login ketika sesi sudah terlogout |
| A6 | Login `admin@…` → buka `/pegawai/search` langsung di URL | **403 Forbidden** | revisi | ceritakan ke saya kenapa admin tidak bisa menggunakan search, bukankah admin bisa menggunakan semua fitur? |
| A7 | Login `pegawai@…` → buka `/admin/users` langsung di URL | **403 Forbidden** | ok | |

## B. Profil — Nomor HP Wajib

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Login pegawai → lihat dashboard | Jika HP kosong: banner kuning **“Nomor HP belum diisi”** | ok | |
| B2 | Buka **Profil Saya** → kosongkan/isi HP dengan `12345` → Simpan | Ditolak dengan pesan error Indonesia (format HP) | ok | |
| B3 | Isi HP `08123456789012` (14 digit, valid) → Simpan | Tersimpan; banner di dashboard hilang | ok | |
| B4 | Login pengurus → profil → isi HP SAMA dengan pegawai | Ditolak: nomor HP sudah dipakai | revisi | saya login menggunakan credential pegawai dan mengganti no hp milik pegawai, dan kemudian login menggunakan credential pengurus dan mengubah no hp saya samakan dengan yang saya rubah dengan credential pegawai masih bisa |
| B5 | Ganti nama & email → Simpan; lalu bagian **Ganti Kata Sandi** (lama salah) | Nama/email tersimpan; ganti sandi dengan sandi lama salah → ditolak | ok | |
| B6 | Cari tombol/link hapus akun sendiri | **Tidak ada** (hanya admin yang menonaktifkan) | ok | |

## C. Cari Mobil & Booking (pegawai)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Buka **Cari Mobil Tersedia** tanpa isi tanggal | Tidak ada hasil dulu; ada kartu kuota bidang (sisa ≥ 0) | ok | |
| C2 | Isi Mulai = **H+10**, Selesai = **H+9** (terbalik) → Cari | Pesan error: tanggal selesai sebelum mulai | ok | |
| C3 | Isi Mulai = **H+10**, Selesai = **H+13** (4 hari) → Cari | Pesan error: durasi maksimal 3 hari | ok | |
| C4 | Isi Mulai = **H+10**, Selesai = **H+12** (3 hari) → Cari | Daftar mobil tersedia tampil (4 unit seeder) + tombol **Pinjam Mobil Ini** | ok | |
| C5 | Klik **Pinjam Mobil Ini** pada satu mobil | Form Peminjaman: ringkasan mobil + tanggal (3 hari, 00:00–24:00) | ok | |
| C6 | Kosongkan Alamat → simpan | Ditolak: alamat wajib | ok | |
| C7 | Isi Alamat & Keperluan → **Pinjam Sekarang** | Sukses → diarahkan ke Peminjaman Saya; booking berstatus **Dipinjam** | ok | |
| C8 | Saat masih punya booking aktif → cari mobil lagi | Tombol pinjam NONAKTIF dengan alasan “Masih ada peminjaman aktif” | ok | |
| C9 | Cari rentang yang sama dengan mobil yang barusan dipinjam | Mobil itu **tidak muncul** di hasil pencarian | ok | |
| C10 | Booking kedua via tab kedua / ulang | Tetap ditolak (satu peminjaman aktif per user) | ok | |
| C11 | Cari Mulai = **H-1** (kemarin) | Ditolak: tanggal mulai tidak boleh masa lalu | ok | |

## D. Tombol “Selesai” + Keluhan (inti kesepakatan)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Di Peminjaman Saya / Dashboard, tekan **Selesai — Kembalikan Mobil** | **Pop-up** muncul: ringkasan + pertanyaan keluhan + textbox (boleh kosong) | ok | |
| D2 | Tutup pop-up (Batal) | Tidak ada perubahan status | ok | |
| D3 | Login **pegawai lain** (buat dulu lewat admin, skenario 02) → coba selesaikan booking pegawai pertama lewat URL `POST`/halaman | Tidak diizinkan (403 / error) | ok | |
| D4 | Kembali pegawai pertama → **Selesai** dengan textbox **kosong** | Sukses: status **Dikembalikan**, mobil **langsung** bisa dipinjam orang lain (C9 ulang → mobil muncul lagi) | ok | |
| D5 | Buat booking baru (H..H) → **Selesai** dengan keluhan: “Ban depan aus” | Sukses + pesan “Keluhan Anda tercatat”; login pengurus → **Keluhan Unit**: keluhan tampil, status **belum selesai** | ok | |
| D6 | **PENTING** — periksa mobil yang dikeluhkan di Data Kendaraan (pengurus) | Mobil **tetap** berkondisi **baik**, status **bisa dipinjam**, **TIDAK ADA** jadwal maintenance baru; mobil masih bisa dicari & dipinjam | ok | |
| D7 | Pengurus → Keluhan Unit → **Tandai selesai** | Keluhan pindah ke tab Selesai; bisa dibuka kembali | ok | |

## E. Pengurus sebagai peminjam

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| E1 | Login `pengurus@karen.test` → Cari Mobil | Bisa: pengurus juga peminjam; dashboard pengurus menampilkan kartu kuota bidangnya | ok | |
| E2 | Buat booking sampai kuota bidang pengurus penuh (2 atau 3 sesuai bidang) → coba booking lagi (user lain segabang) | Ditolak: “Kuota peminjaman {bidang} sudah penuh (maks N mobil)” | ok | |
| E3 | Selesaikan salah satu booking → coba booking lagi | Berhasil (kuota lepas) | ok | |

## F. Riwayat

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| F1 | Buka Peminjaman Saya | Semua booking tampil: badge status, alamat/keperluan, waktu kembali | revisi | semua booking sudah tampil namun ada perbedaan jam antara jam ketika pengembalian dan yang ditampilkan di riwayat peminjaman, pastikan semua format datetime menggunakan pendekatan epoch time millis untuk meminimalizir perbedaan timezone antara front end dan backend |
| F2 | Perhatikan booking yang pernah **diganti mobilnya** (skenario 03) | Badge **“Diganti dari {unit}”** | revisi | skema pergantian mobil, belum berjalan, saya sudah coba ketika mobil yang sedang dipinjam, lalu pengurus membuat jadwal maintenance diantara mobil yang sudah dipinjam, tidak terdapat peringatan apapun, namun untuk jadwal maintenance yang sebelumnya sudah dijadwalkan oleh pengurus, kemudian dilakukan peminjaman dengan rentang waktu yang menabrak jadwal maintenance sudah benar bahwa mobil tidak tampil di daftar mobil yang dapat dipinjam pegawai |
| F3 | Dashboard pegawai → **Riwayat Terakhir** | Maks 5 terakhir + tautan Semua riwayat | ok | |
