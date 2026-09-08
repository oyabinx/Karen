# Skenario 05 — Event Armada Bidang (P2)

**Akun**: `pengurus@karen.test` **atau** `admin@karen.test`
(keduanya boleh membuat event); `pegawai@karen.test` untuk booking
yang akan ditabrak.

## A. Pembuatan Event Wizard

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Event Armada** → **+ Buat Event** | Form info (nama, bidang, tanggal, jumlah mobil N) | ⬜ | |
| A2 | Isi tanggal **H+15..H+21 (7 hari)** → **Lihat Armada Tersedia** | Pratinjau armada muncul — durasi >3 hari DITERIMA (fleksibel khusus event) | ⬜ | |
| A3 | Perhatikan pengelompokan | ✅ **Bebas** (tanpa booking) dan ⚠ **Menabrak** (ada booking + jumlahnya) | ⬜ | |
| A3b | **Kelompok maintenance (baru)**: buat jadwal maintenance satu mobil pada rentang event → lihat wizard | Kelompok ketiga **"🔧 Sedang Maintenance"** tampil — mobil **nonaktif** (checkbox tidak bisa diklik) dengan keterangan **rentang maintenance-nya**; mobil itu TIDAK bisa dipilih meski dicoba submit (ditolak validasi) | ⬜ | |
| A4 | Set **N = 2** tapi centang **1** mobil → buat | Ditolak: jumlah terpilih harus tepat N | ⬜ | |
| A5 | Pilih 2 mobil **bebas** → Buat Event | Sukses “armada terkunci (bebas konflik)”; muncul di daftar status Terjadwal | ⬜ | |
| A6 | Pegawai cari mobil terpilih rentang H+16..H+17 | Kedua mobil **tidak tersedia** | ⬜ | |
| A7 | Pegawai cek dashboard kuota bidangnya | **Tidak berubah** — event tidak mengonsumsi kuota | ⬜ | |
| A8 | Buat event kedua mencoba mobil yang sama (rentang overlap) | Ditolak: mobil tidak layak (dipakai event lain) | ⬜ | |

## B. Event Menabrak Booking → Konfirmasi

Siapkan: pegawai booking mobil X **H+15..H+16**; mobil Y bebas.

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Buat event (bidang bebas) H+15..H+17 dengan armada = mobil X (kelompok Menabrak) | Dibuat; diarahkan ke halaman **Konflik & Pengganti**; booking pegawai **menunggu pengganti** | ⬜ | |
| B2 | Halaman konflik: kandidat pengganti | Menampilkan mobil Y; **tidak** menyertakan armada event (mobil X) | ⬜ | |
| B3 | Di daftar event tekan **Konfirmasi** (sebelum konflik selesai) | Ditolak: masih ada konflik tanpa keputusan | ⬜ | |
| B4 | Pilih **mobil Y** sebagai pengganti | Booking pegawai kembali **Dipinjam** dengan mobil Y (+badge Diganti dari X) | ⬜ | |
| B5 | **Konfirmasi** event | Sukses; status tetap Terjadwal (armada terkunci sejak awal) | ⬜ | |
| B6 | **Batalkan Event** (buat event baru uji) | Status **Dibatalkan**; armada lepas (bisa dipinjam lagi); booking **sudah diganti tetap di mobil Y**; yang **belum** diganti kembali Dipinjam di mobil semula | ⬜ | |

## C. Peran & Batasan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Login `pegawai` → `/pengurus/events` | 403 | ⬜ | |
| C2 | Login admin → buat & batalkan event | **Boleh** (admin + pengurus) | ⬜ | |
