# Fitur: Peminjaman / Booking (Pegawai & Pengurus)

## Deskripsi
Fitur inti self-service: user memilih rentang tanggal, sistem menampilkan mobil yang **tidak sedang dipinjam** pada rentang tersebut, lalu user melakukan booking dengan alamat tujuan dan keperluan. **Tanpa approval** — langsung terkonfirmasi.

> **Pengguna fitur ini: pegawai DAN pengurus.** Sejatinya pengurus adalah user pegawai juga — pengurus dapat mencari, meminjam, dan mengembalikan mobil dengan alur yang sama, **selama kuota bidang masih tersisa**, dan terikat kuota bidang tempat **seksi** pengurus terdaftar.

## Aturan Durasi (penting)
- Satuan peminjaman = **hari penuh, 00:00–24:00** (tidak ada jam).
- Durasi maksimal **3 hari kalender, termasuk Sabtu & Minggu**.
- Booking pada hari yang sama dengan hari ini (hari-H) tetap dihitung **mulai 00:00 hari ini**.
- Contoh: booking hari ini s.d. besok = 2 hari (valid). Hari ini s.d. 3 hari ke depan = 4 hari (ditolak).
- `start_date` tidak boleh di masa lalu.

## Spesifikasi

### 1. Cari Mobil Tersedia
- Form: tanggal mulai + tanggal selesai.
- Validasi rentang sebelum query (lihat atas).
- Hasil: grid mobil tersedia — foto, nama, plat, kapasitas.
- Mobil **tidak ditampilkan** bila:
  - ada booking `dipinjam` yang overlap rentang;
  - ada jadwal maintenance yang overlap rentang;
  - status `tidak_bisa_dipinjam`;
  - kondisi `perlu_diperiksa`.
- Overlap ditentukan dengan: `start_date <= akhir AND end_date >= mulai`.

### 2. Form Booking
- Setelah pilih mobil: tampilkan ringkasan mobil + rentang tanggal, isi **alamat tujuan** dan **keperluan**.
- Konfirmasi → booking dibuat.

### 3. Pembuatan Booking (BookingService)
- Dalam `DB::transaction` + `lockForUpdate` pada baris kendaraan → cek ulang ketersediaan → simpan.
- Cek: user tidak punya peminjaman aktif lain (`status = dipinjam` atau `menunggu_penggantian`).
- **Cek kuota bidang**: jumlah booking aktif bidang peminjam < `bidang.max_active_bookings` (default 2; satu bidang khusus 3 — lihat [kuota_bidang.md](kuota_bidang.md)).
- Status awal: `dipinjam`.

### 4. Riwayat Peminjaman
- Daftar peminjaman pribadi: mobil, tanggal, alamat, keperluan, status.
- Peminjaman aktif menampilkan tombol **"Selesai"** (pop-up keluhan opsional — lihat [pengembalian.md](pengembalian.md)).
- Pengembalian otomatis diberi penanda "dikembalikan otomatis".

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/pegawai/search?start_date=&end_date=` | Hasil mobil tersedia |
| GET | `/pegawai/bookings/create?vehicle_id=&start_date=&end_date=` | Form booking |
| POST | `/pegawai/bookings` | Simpan booking |
| GET | `/pegawai/bookings` | Riwayat peminjaman |

## Aturan Validasi
- `start_date`: wajib, date, `>= today`.
- `end_date`: wajib, date, `>= start_date`, durasi ≤ 3 hari.
- `address`: wajib, maks 255.
- `purpose`: wajib, maks 255.

## Skenario Uji
1. Cari 1–2 Agustus → hanya mobil tanpa overlap tampil.
2. Booking dengan durasi 4 hari → ditolak (maks 3 hari).
3. Booking Sabtu–Senin (3 hari, termasuk akhir pekan) → diterima.
4. Dua user submit booking mobil sama rentang overlap hampir bersamaan → hanya satu berhasil (lock).
5. User dengan peminjaman aktif mencoba booking lagi → ditolak.
6. Booking hari ini → `start_date` tercatat hari ini (penuh 00:00–24:00), bukan jam saat submit.
7. Bidang kuota 2 yang sudah punya 2 booking aktif → booking anggota ketiga ditolak dengan pesan kuota.
8. Pengurus (dengan seksi di bidang tersebut) mencoba booking saat kuota bidang penuh → juga ditolak.
