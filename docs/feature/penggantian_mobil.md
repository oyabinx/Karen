# Fitur: Penggantian Mobil (Maintenance & Event) — Pengurus & Admin

## Deskripsi
Mekanisme penggantian mobil saat jadwal **maintenance** atau **event armada** menabrak peminjaman aktif. Fitur ini memiliki **menu mandiri "Penggantian Mobil"** di sidebar (skema baru UAT 03-D3) karena dipakai bersama oleh kedua fitur tersebut. Diakses oleh **pengurus dan admin**.
Apabila pengurus menjadwalkan maintenance untuk mobil yang **ternyata sudah memiliki booking aktif/mendatang** pada rentang tanggal maintenance, sistem akan:
1. Menandai booking terdampak sebagai `menunggu_penggantian`,
2. **Otomatis mencarikan mobil pengganti yang tersedia** pada rentang tanggal yang sama,
3. **Dikonfirmasi oleh pengurus** Mobil pengganti mana yang dipakai (atau membatalkan booking bila tidak ada pengganti yang cocok).

## Spesifikasi

### Pemicu
- Saat pengurus **membuat** jadwal maintenance dan rentangnya overlap dengan booking berstatus `dipinjam` (termasuk booking mendatang).
- Saat pengurus **mengubah** jadwal maintenance: setelah rentang berubah, sistem menilai **dua arah** — booking yang **masih** tertabrak ditandai `menunggu_penggantian`, dan booking yang **tidak lagi** tertabrak serta **belum** diganti otomatis kembali ke `dipinjam` (mobil semula). Booking yang sudah memakai mobil pengganti tetap di mobil penggantinya.
- Mobil dengan booking `menunggu_penggantian` dianggap **sudah tidak menahan ketersediaan mobil lama** terhadap maintenance.

> **Batasan revert**: pengembalian ke mobil semula hanya dilakukan bila mobil itu **benar-benar bebas** pada rentang booking (tanpa maintenance/event lain yang masih menahan, status `bisa_dipinjam`, kondisi `baik`). Bila mobil semula ternyata masih dikuasai blokir lain, booking **tetap** `menunggu_penggantian` dan diselesaikan manual lewat halaman penggantian — mencegah booking aktif di mobil yang tidak tersedia.

> Mekanisme konfirmasi pengganti ini **juga dipakai oleh fitur Event Armada Bidang** — pembuat event memilih pengganti untuk booking yang ditabrak armada event, lihat [event_bidang.md](event_bidang.md).

### Proses
1. Simpan jadwal maintenance.
2. Cari semua booking `dipinjam` pada mobil tersebut yang overlap rentang maintenance.
3. Set status booking → `menunggu_penggantian`. Selama menunggu keputusan pengurus: mobil lama **tetap terblokir** (tidak dapat dipinjam pihak lain) dan **jatah kuota bidang tetap terhitung** — keputusan belum final, bila jadwal maintenance dibatalkan pengurus, booking dapat dikembalikan ke mobil semula.
4. `AvailabilityService` mencari mobil pengganti yang **tersedia pada rentang tanggal booking** dengan kriteria sama seperti pencarian biasa (tanpa booking overlap, tanpa maintenance overlap, status `bisa_dipinjam`, kondisi `baik`).
5. Tampilkan ke pengurus daftar kandidat pengganti (kartu mobil + tanggal tersedia).
6. Pengurus memilih salah satu → `vehicle_id` booking diganti, status kembali `dipinjam`; mobil asli dicatat di `original_vehicle_id` untuk jejak audit.
7. Bila **tidak ada kandidat** atau pengurus memilih membatal → status booking `dibatalkan`, jatah kuota bidang dilepas.

### Aturan Tambahan
- Rentang pengganti = rentang booking semula (mulai–selesai tidak berubah).
- Mobil pengganti tidak boleh mobil yang sama dengan mobil maintenance, dan harus lolos semua cek ketersediaan + tidak menabrak kuota (kuota tidak berubah karena jumlah booking bidang tetap).
- Penggantian hanya boleh dilakukan oleh **pengurus**, bukan pegawai peminjam.
- Riwayat penggantian terlihat di detail booking (catatan: "Diganti dari [mobil lama] karena maintenance").

### Penggantian PARSIAL — skema baru (UAT 03-B7)
Bila tabrakan hanya menimpa **bagian rentang di tepi** (awal atau akhir) dan pemblokirnya **tunggal**, halaman penggantian menawarkan opsi **"Pengganti sebagian — hanya tanggal yang menabrak"**:
- Contoh: pegawai meminjam Mobil A tanggal 20–22; pengurus menjadwalkan maintenance Mobil A tanggal 22–24. Opsi parsial: tanggal 20–21 **tetap Mobil A**, tanggal 22 saja memakai mobil pengganti.
- Implementasi: booking asli **dipangkas** ke sisa tanggal (tetap mobil lama, status `dipinjam`), dan dibuat **booking baru** untuk tanggal yang menabrak (mobil pengganti, `original_vehicle_id` = mobil lama; alamat & keperluan disalin). Riwayat peminjam menampilkan dua peminjaman berurutan — bagian kedua berbadge "Diganti dari {unit}".
- Kandidat pengganti parsial diperiksa ketersediaannya pada **rentang parsial saja** (bisa lebih banyak daripada kandidat penggantian penuh).
- **Batasan**: parsial hanya untuk tabrakan di **tepi**; tabrakan di **tengah** rentang atau menimpa **seluruh** rentang → gunakan penggantian penuh (opsi parsial tidak tampil).
- Berlaku untuk pemblokir maintenance **maupun** event (halaman konflik event menawarkan opsi yang sama).

### Status Booking Baru
`bookings.status` menjadi ENUM: `dipinjam`, `menunggu_penggantian`, `dikembalikan`, `dibatalkan`.

### Notifikasi ke Peminjam
- Dashboard pegawai menampilkan banner pada booking terdampak: *"Mobil Anda dijadwalkan maintenance, sedang diproses penggantian oleh pengurus."* (notifikasi in-app; WhatsApp/SMS di luar scope v1)

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| POST | `/pengurus/maintenances` | Buat jadwal (memicu deteksi booking terdampak) |
| GET | `/pengurus/replacements` | Daftar booking `menunggu_penggantian` (menu mandiri "Penggantian Mobil") |
| GET | `/pengurus/replacements/{booking}` | Detail kandidat pengganti (penuh + parsial bila tersedia) |
| PATCH | `/pengurus/replacements/{booking}/assign` | Konfirmasi penggantian PENUH |
| PATCH | `/pengurus/replacements/{booking}/assign-partial` | Penggantian PARSIAL (UAT 03-B7) — split booking |
| PATCH | `/pengurus/replacements/{booking}/cancel` | Batalkan booking (tanpa pengganti) |
| PATCH | `/pengurus/events/{event}/conflicts/{booking}/partial` | Parsial dari konflik event (rentang = irisan event × booking) |

## Skenario Uji
1. Buat maintenance 10–12 Agustus untuk mobil yang dibooking 10–11 Agustus → booking jadi `menunggu_penggantian`, kandidat pengganti tampil.
2. Pengurus pilih pengganti → booking kembali `dipinjam` dengan mobil baru, `original_vehicle_id` terisi.
3. Tidak ada mobil tersedia → opsi satu-satunya membatalkan → booking `dibatalkan`, kuota bidang lepas.
4. Pegawai peminjam tidak bisa memilih/mengubah pengganti → 403.
5. Kandidat pengganti yang punya booking overlap rentang tidak muncul di daftar.
