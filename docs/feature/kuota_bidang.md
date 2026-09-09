# Fitur: Kuota Peminjaman per Bidang (Admin)

## Deskripsi
Setiap bidang memiliki **jatah maksimal mobil yang dapat dipinjam secara bersamaan** oleh anggotanya. Default kuota adalah **2 mobil per bidang**, dengan **satu bidang khusus yang boleh maksimal 3 mobil**. Nilai kuota dikonfigurasi oleh admin per bidang.

## Spesifikasi

### Pengaturan Kuota
- Halaman kelola bidang (admin) menambahkan field **"Kuota Peminjaman Maksimal"** per bidang.
- Nilai default: `2`; bidang khusus diset `3` (nama bidang khusus disesuaikan kantor saat setup).
- Batas nilai yang dapat diisi admin: **1–9** *(direvisi dari 1–5 — kesepakatan UAT C2)*.

### Definisi "Bersamaan" (Cara Hitung Kuota)
- Kuota dihitung berdasarkan **jumlah mobil BERBEDA yang dipakai BERSAMAAN pada satu tanggal** — bukan jumlah record booking. Ini adalah Opsi A (kesepakatan user): booking hasil split parsial (2–3 record berurutan) pada satu titik waktu hanya memakai 1 mobil → dihitung **1 slot kuota**.
- **Pengecekan kuota per tanggal rentang booking baru**: sistem menghitung untuk setiap tanggal dalam rentang yang diajukan, berapa mobil akan dipakai bersamaan oleh seluruh anggota bidang. Bila ada satu tanggal saja yang melampaui kuota → ditolak. Booking hari ini tidak menghalangi booking minggu depan bila kuota tersedia pada tanggal tersebut.
- Booking `dikembalikan` / `dibatalkan` **melepaskan** jatah.
- **Peminjaman oleh pegawai maupun pengurus sama-sama diizinkan selama kuota bidang masih tersedia**; pengurus dihitung pada kuota bidang tempat **seksi** pengurus terdaftar (karena itu seksi wajib diisi untuk pegawai dan pengurus).
- **Pengecualian kuota**: pemakaian mobil melalui **event armada bidang** (dibuat admin/pengurus) tidak dihitung dalam kuota — event adalah jalur resmi untuk pemakaian armada di atas kuota, lihat [event_bidang.md](event_bidang.md).
- **Guard "1 peminjaman aktif"** juga berbasis overlap tanggal: pegawai dengan booking hasil split yang aktif hari ini **tetap bisa** booking untuk minggu depan (non-overlap); tapi **ditolak** bila rentangnya bertabrakan dengan booking yang sedang berjalan.

### Validasi Saat Booking
- Sebelum menyimpan booking baru, `BookingService` menghitung:
  ```
  jumlah booking status='dipinjam' milik user pada bidang terkait
  ```
- Bila sudah ≥ kuota → booking **ditolak** dengan pesan: *"Kuota peminjaman bidang Anda sudah penuh (maks X mobil)."*
- Pesan error menampilkan sisa kuota saat ini.

### Transparansi di UI
- Dashboard pegawai/pengurus: kartu info **"Sisa kuota bidang: X dari Y mobil"**; tombol "Pinjam Mobil" disable bila kuota habis.
- Dashboard pengurus/admin: rekap pemakaian kuota semua bidang.

## Perubahan Skema
- Tabel `bidang` tambah kolom: `max_active_bookings TINYINT DEFAULT 2`.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| PUT | `/admin/bidang/{bidang}` | Ubah bidang (termasuk field kuota) |

(validasi tambahan: `max_active_bookings` wajib, angka, 1–5)

## Skenario Uji
1. Bidang A (kuota 2): dua anggotanya aktif meminjam → anggota ketiga mencoba booking → ditolak.
2. Salah satu mobil dikembalikan → anggota ketiga bisa booking.
3. Bidang B diset kuota 3 → tiga anggota bisa meminjam bersamaan.
4. Booking mendatang (bulan depan) yang belum berjalan tetap mengunci kuota.
5. Pengurus dengan seksi terdaftar di bidang A meminjam → terhitung kuota bidang A; bila kuota penuh, pengurus juga ditolak.
