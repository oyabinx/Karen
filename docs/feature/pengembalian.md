# Fitur: Pengembalian & Keluhan (Pegawai)

## Deskripsi
Setelah selesai menggunakan mobil, pegawai menekan tombol **"Kembalikan"** pada peminjaman aktifnya. Sistem menanyakan keluhan terkait unit, lalu mengubah status peminjaman menjadi `dikembalikan` dan mobil kembali **tersedia** untuk dipinjam.

## Spesifikasi

### Tombol Kembalikan
- Tampil pada: dashboard pegawai (peminjaman aktif) dan halaman riwayat.
- Klik → halaman konfirmasi pengembalian berisi ringkasan booking (mobil, tanggal, alamat, keperluan).

### Form Keluhan
- Pertanyaan: *"Apakah ada keluhan terkait unit yang dipinjam?"*
- Dua pilihan:
  - **Tidak ada keluhan** → submit langsung.
  - **Ada keluhan** → textarea isi keluhan (wajib diisi bila opsi ini dipilih).
- Bisa diisi kapan pun selama pengembalian manual — termasuk lebih awal dari `end_date` (pengembalian dini diperbolehkan).

### Proses (ReturnService)
1. Validasi booking milik user yang login dan berstatus `dipinjam`.
2. Set `status = dikembalikan`, `returned_at = now()`.
3. Bila ada keluhan: simpan ke tabel `complaints`; set kondisi mobil `perlu_diperiksa` (mobil **tidak** muncul di pencarian sampai pengurus menyatakan `baik`).
4. Tanpa keluhan: mobil langsung tersedia kembali (kondisi tetap `baik`).

### Catatan
- Pengembalian **otomatis** (lewat jatuh tempo) diurus fitur terpisah: [pengembalian_otomatis.md](pengembalian_otomatis.md).
- Bila mobil pernah **diganti** karena maintenance (lihat [penggantian_mobil.md](penggantian_mobil.md)), yang dikembalikan dan dapat diberi keluhan adalah **mobil pengganti** yang terakhir tercatat pada booking.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/pegawai/returns/{booking}` | Form konfirmasi + keluhan |
| POST | `/pegawai/returns/{booking}` | Proses pengembalian |

## Aturan Validasi
- Booking: milik user login, status `dipinjam`.
- Keluhan: bila opsi "ada keluhan" dipilih, teks wajib (maks 1000 karakter).

## Skenario Uji
1. Kembalikan tanpa keluhan → status `dikembalikan`, mobil langsung bisa dicari pegawai lain.
2. Kembalikan dengan keluhan "ban depan aus" → keluhan tersimpan, mobil berbadge `perlu_diperiksa`, tidak muncul di pencarian, keluhan muncul di daftar pengurus.
3. Akses halaman return booking milik pegawai lain → 403.
4. Submit ulang return untuk booking yang sudah `dikembalikan` → ditolak.
