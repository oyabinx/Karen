# Fitur: Pengembalian Otomatis (Scheduler)

## Deskripsi
Jika masa pinjam telah selesai (`end_date` terlewat) dan pegawai **tidak menekan tombol kembalikan**, sistem otomatis menutup peminjaman dan mengembalikan ketersediaan mobil.

## Spesifikasi

### Jadwal
- Laravel Scheduler berjalan **setiap hari pukul 00:01** (`dailyAt('00:01')`).
- Scheduler Laravel butuh **pemicu eksternal** yang dipasang sekali oleh petugas IT (cron / systemd `schedule:work` / Windows Task Scheduler — pilihan lengkap di [tech.md §7](../tech.md)); aplikasi tidak bisa memicu dirinya sendiri tengah malam di intranet.

### Logika (ReturnService::autoReturn)
Untuk setiap booking dengan:
```
status = 'dipinjam' AND end_date < tanggal hari ini
```
lakukan:
1. `status` → `dikembalikan`
2. `auto_returned` → `true` (penanda pengembalian oleh sistem)
3. `returned_at` → waktu eksekusi

Contoh: booking 5–7 Agustus, pegawai tidak mengembalikan → pada 8 Agustus 00:01 scheduler menutup booking sebagai "dikembalikan otomatis".

### Pembatalan Otomatis Booking `menunggu_penggantian`
Scheduler yang sama juga menutup booking yang menggantung karena pengurus belum memutuskan pengganti:
```
status = 'menunggu_penggantian' AND end_date < tanggal hari ini
→ status = 'dibatalkan', auto_returned = true (penanda ditutup sistem)
```
- Tujuan: kuota bidang tidak terkunci selamanya dan mobil lama tidak terblokir tanpa batas karena kelalaian keputusan.
- Jika pengurus ternyata sudah mengganti mobil, status sudah menjadi `dipinjam` dan mengikuti aturan auto-return biasa di atas.

### Dampak Ketersediaan
- Query ketersediaan berbasis tanggal, sehingga mobil sudah tidak overlap untuk hari berikutnya secara alami; update status tetap dilakukan agar data konsisten dan riwayat akurat.

### Tampilan
- Riwayat pegawai & monitoring pengurus: booking auto-return berbadge **"Dikembalikan Otomatis"**.
- Tidak ada keluhan yang dicatat untuk pengembalian otomatis (pegawai tidak diajak berinteraksi).

## Endpoint
Tidak ada endpoint HTTP — tugas terjadwal (`routes/console.php`).

## Skenario Uji
1. Buat booking dengan `end_date` kemarin (via seed/test) → jalankan `autoReturn()` → status `dikembalikan`, `auto_returned = true`.
2. Booking `end_date` = hari ini → **tidak** di-auto-return (masih berlaku sampai 24:00).
3. Booking sudah `dikembalikan` manual → tidak tersentuh scheduler.
4. Booking `menunggu_penggantian` lewat `end_date` tanpa keputusan pengurus → otomatis `dibatalkan`, kuota bidang lepas.
5. Jalankan scheduler dua kali → idempoten (tidak ada perubahan ganda).
