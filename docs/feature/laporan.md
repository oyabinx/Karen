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
  - **realisasi anggaran maintenance per pos** (servis, suku cadang, AC, pelumas): anggaran vs realisasi (× koefisien pajak) vs sisa per mobil — sumber data [anggaran_maintenance.md](anggaran_maintenance.md).
- **Export CSV/Excel** sesuai filter aktif.

### Kartu Pemeliharaan Kendaraan *(rev UAT 04-B10)*
- Tombol **Generate Kartu Pemeliharaan** ada di menu Laporan (pilih mobil + tahun anggaran, default tahun berjalan) — satu-satunya lokasi generate (dihapus dari menu Dokumen & halaman anggaran kendaraan).
- PDF: judul center "Kartu Pemeliharaan Kendaraan / Tahun Anggaran {tahun} / {nama kendaraan} / {plat}"; tabel **Nomor | Tanggal | Jenis Perbaikan (Servis/Suku Cadang/Pelumas/Servis AC) | Rincian Pemeliharaan (satu cell: baris rincian + bengkel) | Biaya (setelah koefisien)** — satu baris per maintenance per pos; maintenance terbaru di atas.
- Regenerasi mobil+tahun sama → dokumen ditimpa di tempat + badge Diperbarui.

### Keluhan
- **Tab "Belum Selesai": kartu per KENDARAAN** *(rev user UAT 04)* — seluruh keluhan aktif unit terkumpul dalam satu kartu (isi, pelapor, waktu pengembalian), tombol **"Tandai Selesai"** per keluhan.
- Tombol **"🔧 Jadwalkan Maintenance"** pada kartu membuka **modal tanggal** (mulai/selesai, mulai ≥ hari ini) — kendaraan terisi otomatis dan **catatan terisi gabungan seluruh keluhan aktif** (`Keluhan: rem bermasalah (Galih, 12 Sep); setir berat (Pegawai Contoh, 11 Sep)` — boleh disunting, maks 255). Submit memakai alur maintenance existing (validasi rentang/overlap + pemeriksaan tabrakan booking → penggantian mobil).
- Tab "Selesai": daftar datar riwayat + "Buka kembali".
- Keluhan tidak otomatis selesai saat maintenance dijadwalkan (tetap manual oleh pengurus); tidak otomatis mengubah kondisi mobil.

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
