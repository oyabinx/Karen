# Fitur: Pengembalian & Keluhan (Pegawai)

## Deskripsi
Setelah selesai menggunakan mobil, pegawai menekan tombol **"Selesai"** pada peminjaman aktifnya (dashboard atau halaman Peminjaman Saya). Sistem membuka **pop-up konfirmasi** dengan **textbox keluhan opsional** — boleh dikosongkan bila tidak ada keluhan. Setelah submit, status peminjaman menjadi `dikembalikan` dan mobil **segera tersedia** kembali untuk dipinjam.

> **Kesepakatan penting (revisi):** keluhan **hanya catatan/pelaporan** — TIDAK otomatis menyisihkan mobil, TIDAK mengubah kondisi/kendaraan menjadi maintenance, dan mobil **tetap bisa dipinjam**. Pengurus yang menilai dan memutuskan manual tindak lanjutnya: bila perlu, jadwalkan maintenance lewat fitur [manajemen_kendaraan.md](manajemen_kendaraan.md) (lengkap dengan penggantian mobil bila menabrak booking). Kondisi `perlu_diperiksa` hanya dapat diatur **manual** oleh pengurus.

## Spesifikasi

### Tombol Aksi Adaptif (usulan user pasca-UAT 03)
| Kondisi | Tombol | Hasil |
|---------|--------|-------|
| hari ini **≥ tanggal mulai** | **Selesai — Kembalikan Mobil** (pop-up keluhan opsional) | status `dikembalikan` + waktu pengembalian |
| hari ini **< tanggal mulai** | **Batalkan Peminjaman** (pop-up konfirmasi, tanpa keluhan) | status `dibatalkan` + **waktu pembatalan** (`cancelled_at`) — kuota lepas, mobil langsung tersedia |

- Peminjaman yang belum dimulai **tidak bisa** "dikembalikan" (guard menolak) — mobil belum dipakai; pembatalan mandiri adalah jalurnya.
- Sebaliknya, peminjaman yang sudah dimulai **tidak bisa** dibatalkan sendiri — selesaikan dengan tombol Selesai.
- Booking `menunggu_penggantian` tidak memiliki tombol aksi (menunggu keputusan pengurus).

### Pop-up Keluhan (opsional — jalur Selesai)
- Pertanyaan: *"Apakah ada keluhan terkait unit yang dipinjam?"*
- **Textbox bebas** — boleh dikosongkan (tidak ada keluhan) atau diisi uraian keluhan.
- Tombol utama: **"Selesai — Kembalikan Mobil"**; tombol batal menutup pop-up tanpa perubahan.
- Pengembalian dini (sebelum `end_date`) diperbolehkan — mobil **langsung tersedia** untuk sisa hari yang tidak terpakai (ketersediaan berbasis tanggal & status, bukan rentang asli). Riwayat tetap menampilkan rentang asli + status Dikembalikan + jam aktual.

### Proses (ReturnService)
1. Validasi: booking milik user yang login, berstatus `dipinjam`, dan **sudah dimulai** (hari ini ≥ tanggal mulai).
2. Set `status = dikembalikan`, `returned_at = now()`.
3. Bila textbox terisi: simpan ke tabel `complaints` (`resolved = false`) — **murni catatan**, tanpa menyentuh kondisi/status kendaraan.
4. Mobil **langsung tersedia** kembali (ketersediaan berbasis tanggal & booking).
5. Jalur pembatalan mandiri (`cancelByBorrower`): hanya untuk booking **belum dimulai** → `status = dibatalkan`, `cancelled_at = now()`; kuota bidang lepas.

### Penindaklanjutan Keluhan (pengurus)
- Keluhan tampil di daftar **Keluhan Unit** pengurus: unit, pelapor (+ seksi/bidang), isi, tanggal, status `resolved`.
- Pengurus menilai manual: cukup ditandai **selesai**, atau — bila perlu perbaikan — membuat **jadwal maintenance** untuk unit tersebut (alur Fase 4, termasuk penggeseran booking bila menabrak).
- Bila mobil pernah **diganti** (maintenance/event), keluhan tercatat pada **mobil pengganti** yang terakhir dipakai (mengikuti `vehicle_id` booking).

### Pengembalian otomatis
- Lewat jatuh tempo tanpa tombol Selesai → scheduler menutup booking (lihat [pengembalian_otomatis.md](pengembalian_otomatis.md)). Tidak ada keluhan yang dicatat pada jalur otomatis.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| POST | `/pegawai/returns/{booking}` | Selesaikan peminjaman (body opsional: `complaint`) — hanya bila sudah dimulai |
| POST | `/pegawai/returns/{booking}/cancel` | Batalkan peminjaman sendiri — hanya bila BELUM dimulai |

> Pop-up modal bermuara ke endpoint ini; tidak ada halaman terpisah.

## Aturan Validasi
- Booking: milik user login, status `dipinjam`.
- `complaint`: opsional, string, maks 1000 karakter.

## Skenario Uji
1. Selesai **tanpa** keluhan → status `dikembalikan`, `returned_at` terisi, mobil **langsung** bisa dipinjam orang lain.
2. Selesai **dengan** keluhan "ban depan aus" → keluhan tersimpan (belum selesai), muncul di daftar pengurus — namun mobil **tetap bisa dipinjam** dan **tetap berkondisi baik** (tidak masuk bengkel/maintenance).
3. Booking MASA DEPAN → tombol **Batalkan Peminjaman**; konfirmasi → status `dibatalkan` + **waktu pembatalan** (bukan dikembalikan), kuota lepas, mobil bebas.
4. Submit Selesai pada booking belum mulai → **ditolak**; submit Batalkan pada booking hari ini → **ditolak** (guard silang).
5. Mengakses endpoint booking milik pegawai lain → 403.
6. Booking `menunggu_penggantian` tidak bisa diselesaikan/dibatalkan pegawai (tunggu keputusan pengurus).
7. Pengurus menandai keluhan selesai → keluar dari daftar "belum selesai".
8. Pengurus menandai unit `perlu_diperiksa` secara **manual** → unit tidak bisa dipinjam; set kembali `baik` → tersedia lagi.
