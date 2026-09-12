# Fitur: Dashboard per Role

## Deskripsi
Setelah login, tiap role diarahkan ke dashboardnya sendiri di `/dashboard` (view berbeda per role).

## Panel Bersama: "Armada Hari Ini" (semua role)
Panel `dashboard/partials/armada-hari-ini` tampil di dashboard **admin, pengurus, dan pegawai** — menjawab pertanyaan rutin kantor *"mobil X hari ini siapa yang pakai?"* (tuker unit, barang tertinggal, dsb.) tanpa harus bertanya ke pengurus.

- **Dipakai** (aksen indigo): mobil + plat, peminjam + bidang, **nomor HP peminjam sebagai link `tel:`** (muncul bila terisi), "s.d. {tanggal} · {tujuan}", pill "menunggu pengganti" bila statusnya demikian. Status yang dihitung: `dipinjam` dan `menunggu_penggantian`.
- **Di bengkel** (aksen amber): unit dengan jadwal maintenance `terjadwal` yang mencakup hari ini + catatan singkat.
- **Armada event** (aksen abu): unit terpasang pada event `terjadwal` yang mencakup hari ini (nama event + bidang).
- Satu unit hanya muncul sekali (prioritas: dipakai > bengkel > event). Di dashboard pengurus, panel ini menggantikan section "Peminjaman Berjalan Hari Ini" lama (kini ikut menampilkan menunggu-pengganti + kontak, tanpa limit).

## Admin
- Ringkasan: total user aktif, jumlah per role, jumlah bidang & seksi.
- Tabel user per bidang (jumlah anggota).
- Shortcut: kelola user, kelola bidang & seksi.

## Pengurus
- Ringkasan kendaraan: total, **tersedia**, sedang dipinjam, `tidak_bisa_dipinjam`, `perlu_diperiksa`, dalam maintenance aktif.
- **Armada Hari Ini** (panel bersama, lihat atas) + tautan "Semua peminjaman".
- Daftar keluhan belum ditindaklanjuti (dengan pintasan ke daftar Keluhan Unit; keluhan TIDAK otomatis menyisihkan mobil — tindak lanjut manual oleh pengurus).
- Ringkasan anggaran maintenance: total & sisa per pos (servis, suku cadang, AC, pelumas) dan maintenance yang menunggu input nota.
- Daftar event armada mendatang/berjalan (nama, bidang, tanggal, jumlah mobil) — admin juga melihat bagian ini.
- Shortcut: kelola kendaraan, jadwalkan maintenance, kelola anggaran, buat event, lihat laporan.

## Pegawai
- Kartu **peminjaman aktif** (bila ada): mobil, tanggal, alamat, tombol **"Selesai"** (pop-up keluhan opsional → lihat [pengembalian.md](pengembalian.md)).
- **Armada Hari Ini** (panel bersama, lihat atas).
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
4. Ada booking hari ini / maintenance terjadwal hari ini / event hari ini → ketiga role melihat barisnya di "Armada Hari Ini" (peminjam + no. HP + info bengkel/event).
5. Tidak ada aktivitas hari ini → panel menampilkan pesan "Tidak ada armada yang dipakai hari ini".

