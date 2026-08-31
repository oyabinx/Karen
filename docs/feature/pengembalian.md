# Fitur: Pengembalian & Keluhan (Pegawai)

## Deskripsi
Setelah selesai menggunakan mobil, pegawai menekan tombol **"Selesai"** pada peminjaman aktifnya (dashboard atau halaman Peminjaman Saya). Sistem membuka **pop-up konfirmasi** dengan **textbox keluhan opsional** — boleh dikosongkan bila tidak ada keluhan. Setelah submit, status peminjaman menjadi `dikembalikan` dan mobil **segera tersedia** kembali untuk dipinjam.

> **Kesepakatan penting (revisi):** keluhan **hanya catatan/pelaporan** — TIDAK otomatis menyisihkan mobil, TIDAK mengubah kondisi/kendaraan menjadi maintenance, dan mobil **tetap bisa dipinjam**. Pengurus yang menilai dan memutuskan manual tindak lanjutnya: bila perlu, jadwalkan maintenance lewat fitur [manajemen_kendaraan.md](manajemen_kendaraan.md) (lengkap dengan penggantian mobil bila menabrak booking). Kondisi `perlu_diperiksa` hanya dapat diatur **manual** oleh pengurus.

## Spesifikasi

### Tombol "Selesai"
- Tampil pada: dashboard pegawai (kartu peminjaman aktif) dan halaman Peminjaman Saya — menonjol (warna aksen) agar mudah dijangkau di ponsel.
- Hanya untuk peminjaman berstatus `dipinjam` milik sendiri.
- Klik → **pop-up (modal)** berisi ringkasan booking (mobil, tanggal, alamat, keperluan).

### Pop-up Keluhan (opsional)
- Pertanyaan: *"Apakah ada keluhan terkait unit yang dipinjam?"*
- **Textbox bebas** — boleh dikosongkan (tidak ada keluhan) atau diisi uraian keluhan.
- Tombol utama: **"Selesai — Kembalikan Mobil"**; tombol batal menutup pop-up tanpa perubahan.
- Pengembalian dini (sebelum `end_date`) diperbolehkan.

### Proses (ReturnService)
1. Validasi: booking milik user yang login dan berstatus `dipinjam`.
2. Set `status = dikembalikan`, `returned_at = now()`.
3. Bila textbox terisi: simpan ke tabel `complaints` (`resolved = false`) — **murni catatan**, tanpa menyentuh kondisi/status kendaraan.
4. Mobil **langsung tersedia** kembali (ketersediaan berbasis tanggal & booking).

### Penindaklanjutan Keluhan (pengurus)
- Keluhan tampil di daftar **Keluhan Unit** pengurus: unit, pelapor (+ seksi/bidang), isi, tanggal, status `resolved`.
- Pengurus menilai manual: cukup ditandai **selesai**, atau — bila perlu perbaikan — membuat **jadwal maintenance** untuk unit tersebut (alur Fase 4, termasuk penggeseran booking bila menabrak).
- Bila mobil pernah **diganti** (maintenance/event), keluhan tercatat pada **mobil pengganti** yang terakhir dipakai (mengikuti `vehicle_id` booking).

### Pengembalian otomatis
- Lewat jatuh tempo tanpa tombol Selesai → scheduler menutup booking (lihat [pengembalian_otomatis.md](pengembalian_otomatis.md)). Tidak ada keluhan yang dicatat pada jalur otomatis.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| POST | `/pegawai/returns/{booking}` | Selesaikan peminjaman (body opsional: `complaint`) |

> Pop-up modal bermuara ke endpoint ini; tidak ada halaman terpisah.

## Aturan Validasi
- Booking: milik user login, status `dipinjam`.
- `complaint`: opsional, string, maks 1000 karakter.

## Skenario Uji
1. Selesai **tanpa** keluhan → status `dikembalikan`, `returned_at` terisi, mobil **langsung** bisa dipinjam orang lain.
2. Selesai **dengan** keluhan "ban depan aus" → keluhan tersimpan (belum selesai), muncul di daftar pengurus — namun mobil **tetap bisa dipinjam** dan **tetap berkondisi baik** (tidak masuk bengkel/maintenance).
3. Submit ulang untuk booking yang sudah `dikembalikan` → ditolak.
4. Mengakses endpoint booking milik pegawai lain → 403.
5. Booking `menunggu_penggantian` tidak bisa diselesaikan lewat tombol Selesai (tunggu keputusan pengganti).
6. Pengurus menandai keluhan selesai → keluar dari daftar "belum selesai".
7. Pengurus menandai unit `perlu_diperiksa` secara **manual** → unit tidak bisa dipinjam; set kembali `baik` → tersedia lagi.
