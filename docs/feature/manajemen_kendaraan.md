# Fitur: Manajemen Kendaraan & Maintenance (Pengurus)

## Deskripsi
Pengurus mengelola data mobil dinas: menambah/mengubah unit, mengatur apakah unit boleh dipinjam, menandai kondisi unit, dan menjadwalkan maintenance.

## Spesifikasi

### Data Kendaraan
- Field: nama/unit (misal "Avanza B 1234 XYZ"), plat nomor, **tahun pembuatan** (menentukan besaran anggaran 4 pos — lihat [anggaran_maintenance.md](anggaran_maintenance.md)), kapasitas kursi, foto (opsional).
- CRUD lengkap; hapus = soft delete (riwayat booking tetap utuh).

### Status Kendaraan
- `bisa_dipinjam` / `tidak_bisa_dipinjam` — toggle oleh pengurus (misal mobil rusak atau dipakai keperluan kantor).
- Kondisi: `baik` / `perlu_diperiksa` — otomatis menjadi `perlu_diperiksa` saat pegawai mengajukan keluhan; pengurus mengembalikan ke `baik` setelah diperiksa.

### Jadwal Maintenance
- Field: kendaraan, tanggal mulai, tanggal selesai, catatan. Status maintenance: `terjadwal` → `selesai` (input nota bengkel & anggaran saat selesai — lihat [anggaran_maintenance.md](anggaran_maintenance.md)).
- Kendaraan dengan maintenance yang overlap rentang pencarian **tidak muncul** sebagai tersedia.
- **Bila mobil yang dijadwalkan maintenance sudah memiliki booking pada rentang yang tabrakan**: booking terdampak berubah status menjadi `menunggu_penggantian`, sistem otomatis mencarikan mobil pengganti yang tersedia, dan pengurus mengonfirmasi pilihan penggantinya — alur lengkap ada di [penggantian_mobil.md](penggantian_mobil.md).
- Maintenance yang sudah lewat dapat ditandai selesai / diarsipkan otomatis.

### UI
- Kartu/grid mobil: foto, nama, plat, badge status & kondisi, jumlah hari terjadwal maintenance aktif.
- Tab: Semua | Tersedia | Tidak Bisa Dipinjam | Perlu Diperiksa.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/pengurus/vehicles` | Daftar + filter |
| POST | `/pengurus/vehicles` | Tambah (dengan upload foto) |
| GET | `/pengurus/vehicles/{vehicle}/edit` | Form ubah |
| PUT | `/pengurus/vehicles/{vehicle}` | Proses ubah |
| DELETE | `/pengurus/vehicles/{vehicle}` | Soft delete |
| PATCH | `/pengurus/vehicles/{vehicle}/status` | Toggle bisa/tidak bisa dipinjam |
| PATCH | `/pengurus/vehicles/{vehicle}/condition` | Set kondisi kembali `baik` |
| GET/POST | `/pengurus/maintenances` | Daftar / tambah jadwal maintenance |
| PUT/DELETE | `/pengurus/maintenances/{maintenance}` | Ubah / hapus jadwal |
| PATCH | `/pengurus/maintenances/{maintenance}/finish` | Tandai perawatan selesai (input nota & anggaran menyusul di [anggaran_maintenance.md](anggaran_maintenance.md)) |

## Aturan Validasi
- Nama: wajib, maks 100.
- Plat nomor: wajib, maks 15, unique.
- Tahun pembuatan: wajib, angka 1980–tahun berjalan+1.
- Kapasitas: wajib, angka 1–20.
- Foto: jpg/png/webp, maks 2 MB (opsional).
- Maintenance: `end_date >= start_date`, wajib pilih kendaraan, **satu kendaraan tidak boleh memiliki dua jadwal maintenance yang overlap** (rentang bertumpuk ditolak sejak validasi — mencegah data ambigu saat menghitung ketersediaan), dan **rentang maintenance tidak boleh menabrak armada event terjadwal** (aturan dua arah: event juga menolak kendaraan yang sedang maintenance — mobil tidak bisa berada di bengkel dan dipakai event sekaligus).

## Skenario Uji
1. Tambah mobil dengan foto → muncul di daftar dan bisa dicari pegawai.
2. Toggle `tidak_bisa_dipinjam` → mobil tidak muncul di pencarian pegawai.
3. Tambah maintenance 10–12 Agustus → mobil tidak tersedia untuk rentang yang overlap (misal 11–13 Agustus).
4. Mobil dengan keluhan berbadge `perlu_diperiksa` → setelah pengurus set `baik`, muncul kembali di pencarian.
5. Jadwalkan maintenance untuk mobil yang sedang dibooking pada rentang tabrakan → booking menjadi `menunggu_penggantian` dan muncul di daftar penggantian.
