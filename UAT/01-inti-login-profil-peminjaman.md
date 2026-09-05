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
| A1 | Buka `http://localhost` tanpa login → klik apapun menu | Diarahkan ke halaman Masuk berjudul “Selamat datang di Karen” | ⬜ | |
| A2 | Login `pegawai@karen.test` + password SALAH | Tetap di halaman masuk, pesan error (tidak menyebut email/password mana yang salah) | ⬜ | |
| A3 | Login benar `pegawai@karen.test` / `password` | Masuk **Dashboard Pegawai**; muncul kartu **Kuota Peminjaman** bidang Anda | ⬜ | |
| A4 | Klik menu (sidebar kiri): Profil Saya, Cari Mobil, Peminjaman Saya | Semua terbuka; menu sesuai role | ⬜ | |
| A5 | Klik **Keluar** | Kembali ke halaman masuk; menekan back tidak bisa masuk dashboard lagi | ⬜ | |
| A6 | Login `admin@…` → buka `/pegawai/search` langsung di URL | **403 Forbidden** | ⬜ | |
| A7 | Login `pegawai@…` → buka `/admin/users` langsung di URL | **403 Forbidden** | ⬜ | |

## B. Profil — Nomor HP Wajib

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Login pegawai → lihat dashboard | Jika HP kosong: banner kuning **“Nomor HP belum diisi”** | ⬜ | |
| B2 | Buka **Profil Saya** → kosongkan/isi HP dengan `12345` → Simpan | Ditolak dengan pesan error Indonesia (format HP) | ⬜ | |
| B3 | Isi HP `08123456789012` (14 digit, valid) → Simpan | Tersimpan; banner di dashboard hilang | ⬜ | |
| B4 | Login pengurus → profil → isi HP SAMA dengan pegawai | Ditolak: nomor HP sudah dipakai | ⬜ | |
| B5 | Ganti nama & email → Simpan; lalu bagian **Ganti Kata Sandi** (lama salah) | Nama/email tersimpan; ganti sandi dengan sandi lama salah → ditolak | ⬜ | |
| B6 | Cari tombol/link hapus akun sendiri | **Tidak ada** (hanya admin yang menonaktifkan) | ⬜ | |

## C. Cari Mobil & Booking (pegawai)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Buka **Cari Mobil Tersedia** tanpa isi tanggal | Tidak ada hasil dulu; ada kartu kuota bidang (sisa ≥ 0) | ⬜ | |
| C2 | Isi Mulai = **H+10**, Selesai = **H+9** (terbalik) → Cari | Pesan error: tanggal selesai sebelum mulai | ⬜ | |
| C3 | Isi Mulai = **H+10**, Selesai = **H+13** (4 hari) → Cari | Pesan error: durasi maksimal 3 hari | ⬜ | |
| C4 | Isi Mulai = **H+10**, Selesai = **H+12** (3 hari) → Cari | Daftar mobil tersedia tampil (4 unit seeder) + tombol **Pinjam Mobil Ini** | ⬜ | |
| C5 | Klik **Pinjam Mobil Ini** pada satu mobil | Form Peminjaman: ringkasan mobil + tanggal (3 hari, 00:00–24:00) | ⬜ | |
| C6 | Kosongkan Alamat → simpan | Ditolak: alamat wajib | ⬜ | |
| C7 | Isi Alamat & Keperluan → **Pinjam Sekarang** | Sukses → diarahkan ke Peminjaman Saya; booking berstatus **Dipinjam** | ⬜ | |
| C8 | Saat masih punya booking aktif → cari mobil lagi | Tombol pinjam NONAKTIF dengan alasan “Masih ada peminjaman aktif” | ⬜ | |
| C9 | Cari rentang yang sama dengan mobil yang barusan dipinjam | Mobil itu **tidak muncul** di hasil pencarian | ⬜ | |
| C10 | Booking kedua via tab kedua / ulang | Tetap ditolak (satu peminjaman aktif per user) | ⬜ | |
| C11 | Cari Mulai = **H-1** (kemarin) | Ditolak: tanggal mulai tidak boleh masa lalu | ⬜ | |

## D. Tombol “Selesai” + Keluhan (inti kesepakatan)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Di Peminjaman Saya / Dashboard, tekan **Selesai — Kembalikan Mobil** | **Pop-up** muncul: ringkasan + pertanyaan keluhan + textbox (boleh kosong) | ⬜ | |
| D2 | Tutup pop-up (Batal) | Tidak ada perubahan status | ⬜ | |
| D3 | Login **pegawai lain** (buat dulu lewat admin, skenario 02) → coba selesaikan booking pegawai pertama lewat URL `POST`/halaman | Tidak diizinkan (403 / error) | ⬜ | |
| D4 | Kembali pegawai pertama → **Selesai** dengan textbox **kosong** | Sukses: status **Dikembalikan**, mobil **langsung** bisa dipinjam orang lain (C9 ulang → mobil muncul lagi) | ⬜ | |
| D5 | Buat booking baru (H..H) → **Selesai** dengan keluhan: “Ban depan aus” | Sukses + pesan “Keluhan Anda tercatat”; login pengurus → **Keluhan Unit**: keluhan tampil, status **belum selesai** | ⬜ | |
| D6 | **PENTING** — periksa mobil yang dikeluhkan di Data Kendaraan (pengurus) | Mobil **tetap** berkondisi **baik**, status **bisa dipinjam**, **TIDAK ADA** jadwal maintenance baru; mobil masih bisa dicari & dipinjam | ⬜ | |
| D7 | Pengurus → Keluhan Unit → **Tandai selesai** | Keluhan pindah ke tab Selesai; bisa dibuka kembali | ⬜ | |

## E. Pengurus sebagai peminjam

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| E1 | Login `pengurus@karen.test` → Cari Mobil | Bisa: pengurus juga peminjam; dashboard pengurus menampilkan kartu kuota bidangnya | ⬜ | |
| E2 | Buat booking sampai kuota bidang pengurus penuh (2 atau 3 sesuai bidang) → coba booking lagi (user lain segabang) | Ditolak: “Kuota peminjaman {bidang} sudah penuh (maks N mobil)” | ⬜ | |
| E3 | Selesaikan salah satu booking → coba booking lagi | Berhasil (kuota lepas) | ⬜ | |

## F. Riwayat

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| F1 | Buka Peminjaman Saya | Semua booking tampil: badge status, alamat/keperluan, waktu kembali | ⬜ | |
| F2 | Perhatikan booking yang pernah **diganti mobilnya** (skenario 03) | Badge **“Diganti dari {unit}”** | ⬜ | |
| F3 | Dashboard pegawai → **Riwayat Terakhir** | Maks 5 terakhir + tautan Semua riwayat | ⬜ | |
