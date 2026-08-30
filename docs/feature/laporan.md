# Fitur: Laporan Penggunaan Kendaraan (Pengurus)

## Deskripsi
Monitoring dan laporan seluruh peminjaman kendaraan untuk pengurus: peminjaman berjalan, riwayat lengkap, dan export.

## Spesifikasi

### Monitoring Peminjaman
- Tabel semua peminjaman: mobil, peminjam (+ bidang/seksi), tanggal, alamat, keperluan, status (`dipinjam` / `dikembalikan` / auto).
- Filter: rentang tanggal, mobil, bidang, status.

### Laporan
- Rekap per rentang tanggal (default bulan berjalan):
  - jumlah peminjaman total & per mobil;
  - hari pakai per mobil (akumulasi durasi);
  - daftar keluhan beserta status penanganan;
  - pengembalian terlambat (auto-return) per pegawai;
  - **realisasi anggaran maintenance per pos** (servis, suku cadang, AC, pelumas): anggaran vs realisasi (× 1,13) vs sisa per mobil — sumber data [anggaran_maintenance.md](anggaran_maintenance.md).
- **Export CSV/Excel** sesuai filter aktif.

### Keluhan
- Daftar keluhan (dari form pengembalian): mobil, pelapor, isi, tanggal, status `resolved`.
- Tombol "Tandai Selesai" setelah ditindaklanjuti (tidak otomatis mengubah kondisi mobil — kondisi diatur di manajemen kendaraan).

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/pengurus/bookings` | Monitoring semua peminjaman + filter |
| GET | `/pengurus/complaints` | Daftar keluhan |
| PATCH | `/pengurus/complaints/{complaint}/resolve` | Tandai selesai |
| GET | `/pengurus/reports` | Rekap laporan + filter |
| GET | `/pengurus/reports/export` | Download CSV sesuai filter |

## Skenario Uji
1. Filter rentang tanggal → hanya peminjaman overlap rentang tampil.
2. Export CSV → isi sesuai filter aktif.
3. Tandai keluhan selesai → keluar dari daftar "belum ditindaklanjuti".
4. Pegawai akses `/pengurus/reports` → 403.
