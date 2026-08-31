# Fitur: Dashboard per Role

## Deskripsi
Setelah login, tiap role diarahkan ke dashboardnya sendiri di `/dashboard` (view berbeda per role).

## Admin
- Ringkasan: total user aktif, jumlah per role, jumlah bidang & seksi.
- Tabel user per bidang (jumlah anggota).
- Shortcut: kelola user, kelola bidang & seksi.

## Pengurus
- Ringkasan kendaraan: total, **tersedia**, sedang dipinjam, `tidak_bisa_dipinjam`, `perlu_diperiksa`, dalam maintenance aktif.
- Daftar peminjaman aktif hari ini (mobil, peminjam + bidang/seksi, tanggal, keperluan).
- Daftar keluhan belum ditindaklanjuti (dengan pintasan ke daftar Keluhan Unit; keluhan TIDAK otomatis menyisihkan mobil — tindak lanjut manual oleh pengurus).
- Ringkasan anggaran maintenance: total & sisa per pos (servis, suku cadang, AC, pelumas) dan maintenance yang menunggu input nota.
- Daftar event armada mendatang/berjalan (nama, bidang, tanggal, jumlah mobil) — admin juga melihat bagian ini.
- Shortcut: kelola kendaraan, jadwalkan maintenance, kelola anggaran, buat event, lihat laporan.

## Pegawai
- Kartu **peminjaman aktif** (bila ada): mobil, tanggal, alamat, tombol **"Selesai"** (pop-up keluhan opsional → lihat [pengembalian.md](pengembalian.md)).
- Tombol pintar: **"Pinjam Mobil"** → form cari ketersediaan.
- Riwayat singkat (5 terakhir) dengan badge status (termasuk "Dikembalikan Otomatis").
- Info profil: nomor HP belum diisi → banner pengingat wajib isi.

## Endpoint
| Method | Path | Akses |
|--------|------|-------|
| GET | `/dashboard` | auth (view sesuai role) |

## Skenario Uji
1. Login tiap role → dashboard masing-masing tampil benar.
2. Pegawai punya booking aktif → kartu + tombol Kembalikan tampil.
3. Nomor HP kosong → banner pengingat muncul untuk semua role.
