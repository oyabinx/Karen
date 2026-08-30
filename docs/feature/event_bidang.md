# Fitur: Event Armada Bidang (Admin & Pengurus)

## Deskripsi
Untuk kegiatan yang membuat **satu bidang memakai banyak armada dalam satu waktu** — berpotensi **melebihi kuota peminjaman bidang** (2/3 mobil) — disediakan reservasi khusus bernama **Event**. Hanya **admin** dan **pengurus** yang dapat membuatnya. Pemakaian mobil untuk event **tidak dihitung dalam kuota bidang**.

Bila mobil yang dipilih untuk event **menabrak peminjaman yang sudah ada**, sistem menampilkan **mobil alternatif pengganti** untuk tiap booking yang ditabrak — pembuat event **mengonfirmasi dan memilih unit pengganti** satu per satu (mekanisme sama dengan [penggantian_mobil.md](penggantian_mobil.md)).

## Spesifikasi

### 1. Membuat Event (wizard)
Langkah 1 — informasi event:
- Nama event, bidang penyelenggara, rentang tanggal (mulai–selesai, hari penuh 00:00–24:00), catatan (opsional), **jumlah mobil dibutuhkan (N)**.
- **Durasi event fleksibel**: boleh **melebihi 3 hari** untuk kegiatan khusus (misal pelatihan/rapat multi-hari) karena hanya admin dan pengurus yang dapat membuatnya — batas 3 hari hanya berlaku untuk peminjaman biasa pegawai/pengurus. Validasi tetap dijalankan: `end_date >= start_date` dan `start_date >= today`.

Langkah 2 — pemilihan armada:
- Sistem menampilkan daftar mobil yang memenuhi syarat dasar (status `bisa_dipinjam`, kondisi `baik`, **bukan dalam jadwal maintenance**, **bukan terpakai event lain** pada rentang tersebut) dan menandai dua kelompok:
  - ✅ **Bebas** — tidak ada booking overlap pada rentang event;
  - ⚠️ **Menabrak** — ada booking aktif overlap (booking inilah yang akan digeser).
- Pembuat event memilih tepat N mobil (boleh campuran kedua kelompok).
- Mobil yang **sedang dipakai event lain** pada rentang sama **tidak bisa ditabrak** (event tidak boleh menggeser event).
- Aturan **dua arah**: event menolak kendaraan yang sedang maintenance, dan sebaliknya **pembuatan jadwal maintenance baru ditolak bila menabrak armada event terjadwal** (mobil tidak bisa di bengkel dan dipakai event sekaligus — lihat [manajemen_kendaraan.md](manajemen_kendaraan.md)).

Langkah 3 — penyelesaian tabrakan (bila ada mobil "menabrak"):
- Semua booking terdampak otomatis berstatus `menunggu_penggantian`.
- Untuk **tiap booking terdampak**, sistem mencarikan **kandidat mobil pengganti** yang tersedia pada rentang booking tersebut (kriteria ketersediaan biasa, **di luar N mobil event**).
- Pembuat event **memilih unit pengganti** per booking → booking kembali `dipinjam` dengan mobil baru (`original_vehicle_id` tercatat).
- Bila tidak ada kandidat untuk suatu booking, pembuat event harus memilih: **batalkan booking tersebut** atau **lepaskan mobil itu dari event dan pilih mobil event lain**.
- Halaman konflik event dapat menampilkan booking `menunggu_penggantian` lain pada mobil armada yang sama (mis. yang ditandai oleh maintenance) — hal ini disengaja karena booking tersebut sama-sama menunggu keputusan pengganti, dan penetapan pengganti dari halaman mana pun menyelesaikannya.

Langkah 4 — konfirmasi:
- **Armada terkunci sejak pembuatan event** (status `terjadwal` langsung memblokir ketersediaan mobil bagi non-event) — tombol **Konfirmasi** adalah *checkpoint* deklarasi bahwa seluruh konflik telah selesai, bukan saat penguncian. Konfirmasi ditolak selama masih ada konflik tanpa keputusan.
- Dashboard pegawai peminjam yang digeser menampilkan banner (sama seperti alur penggantian maintenance).

### 2. Aturan Kuota
- Mobil pada event **dikecualikan dari hitungan kuota bidang** — event adalah mekanisme resmi untuk pemakaian armada di atas kuota.
- Booking yang digeser tetap dihitung kuota pada bidang asal peminjamnya (jumlah booking mereka tidak berubah).

### 3. Selesai & Pembatalan
- Event berakhir otomatis setelah `end_date` lewat (scheduler menandai `selesai`; ketersediaan mobil lepas berbasis tanggal).
- Pembatalan event oleh admin/pengurus: mobil event langsung lepas. **Booking yang sudah digeser tetap memakai mobil penggantinya** (tidak dipindahkan balik) — tercatat di riwayat. Booking yang **belum** diganti dikembalikan ke status `dipinjam` dengan mobil semula **hanya bila mobil itu bebas penuh pada rentang booking** (aturan availability-aware yang sama dengan pembatalan maintenance — lihat [penggantian_mobil.md](penggantian_mobil.md) bagian Batasan revert); bila masih ditahan blokir lain, booking tetap menunggu penggantian.

### 4. Perubahan Skema
- `events`: `id`, `name`, `bidang_id` FK, `start_date`, `end_date`, `note` NULL, `status` ENUM('terjadwal','selesai','dibatalkan'), `created_by` FK users, `timestamps`.
- `event_vehicles`: `event_id` FK, `vehicle_id` FK, UNIQUE(event_id, vehicle_id).
- Query ketersediaan & pencarian pegawai ditambah pengecualian: mobil yang terikat event `terjadwal` dengan rentang overlap **tidak tersedia**.

## Endpoint
| Method | Path | Akses | Keterangan |
|--------|------|-------|------------|
| GET | `/pengurus/events` | admin, pengurus | Daftar event + status |
| GET/POST | `/pengurus/events/create` | admin, pengurus | Wizard langkah 1–2 (info + pilih armada) |
| GET | `/pengurus/events/{event}/conflicts` | admin, pengurus | Booking tertabrak + kandidat pengganti |
| PATCH | `/pengurus/events/{event}/conflicts/{booking}` | admin, pengurus | Pilih mobil pengganti untuk satu booking |
| PATCH | `/pengurus/events/{event}/confirm` | admin, pengurus | Finalisasi & kunci armada event |
| PATCH | `/pengurus/events/{event}/cancel` | admin, pengurus | Batalkan event |

> Secara internal, pemilihan pengganti memakai kembali `ReplacementService` (logika sama dengan penggantian karena maintenance).

## Aturan Validasi
- Nama event: wajib, maks 150.
- Rentang: `start_date >= today`, `end_date >= start_date`; **durasi fleksibel — boleh lebih dari 3 hari** (batas 3 hari hanya untuk peminjaman biasa).
- Jumlah mobil N: wajib, angka 1–jumlah armada layak; jumlah mobil terpilih harus tepat N sebelum konfirmasi.
- Semua konflik harus selesai (dipilih pengganti / dibatalkan) sebelum event dikonfirmasi.

## Skenario Uji
1. Buat event bidang A, 4 mobil, 3 hari → diterima walau kuota A hanya 2 (event bebas kuota).
2. Buat event khusus 7 hari → diterima (durasi event fleksibel, melebihi 3 hari).
3. Pilih 2 mobil bebas + 2 mobil yang menabrak booking → kedua booking jadi `menunggu_penggantian`, kandidat pengganti tampil per booking.
4. Konfirmasi pengganti tiap booking → event `terjadwal`, 4 mobil tidak muncul di pencarian pegawai pada rentang event.
5. Pegawai lain mencari mobil pada rentang event → tidak melihat armada event.
6. Satu booking tanpa kandidat pengganti → pembuat event wajib membatalkan booking itu atau mengganti pilihan mobil event.
7. Event kedua mencoba menabrak armada event pertama → mobil tersebut tidak bisa dipilih.
8. Batalkan event → mobil lepas; booking yang sudah digeser tetap di mobil penggantinya.
9. `end_date` lewat → scheduler menandai event `selesai` (idempoten).
10. Pegawai membuat event → 403 (hanya admin & pengurus).
