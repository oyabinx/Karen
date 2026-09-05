# Skenario 02 — Admin: Manajemen User, Impor CSV, Organisasi & Kuota (P1)

**Akun**: `admin@karen.test` / `password`.
**Prasyarat**: skenario 01 selesai minimal bagian A–B (untuk data HP).

## A. Manajemen User

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Manajemen User** | Tabel user + filter (cari/role/bidang/status) | ⬜ | |
| A2 | **+ Tambah User**: role **pegawai**, seksi **kosong** → simpan | Ditolak: seksi wajib untuk pegawai | ⬜ | |
| A3 | Tambah pegawai lengkap (nama/email/password ≥8/seksi) | Tersimpan; logout → login akun baru sukses | ⬜ | |
| A4 | Tambah user dengan email yang sudah dipakai | Ditolak: email sudah terdaftar | ⬜ | |
| A5 | **Ubah** user: ganti role pegawai → pengurus (seksi tetap) | Berubah; menu user itu berubah setelah login ulang | ⬜ | |
| A6 | **Nonaktifkan** user → coba login akun itu | Login ditolak; di tabel berstatus Nonaktif (keruh) | ⬜ | |
| A7 | **Aktifkan** kembali → login | Berhasil | ⬜ | |
| A8 | Nonaktifkan **akun admin sendiri** | Ditolak dengan pesan | ⬜ | |
| A9 | Filter: cari nama / role=pengurus / bidang / status=nonaktif | Hasil sesuai filter | ⬜ | |

## B. Impor Massal CSV

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | **Impor CSV → Unduh Template CSV** | File terunduh; header `nama;email;password;bidang;seksi;role` + 1 baris contoh | ⬜ | |
| B2 | Isi template 5 baris valid (bidang & seksi sesuai seeder) → Unggah → Pratinjau | 5 baris ✅; BELUM ada user baru dibuat (dry-run) | ⬜ | |
| B3 | Tambah 2 baris rusak: seksi tidak cocok dengan bidang; password `abc` (< 8) → unggah ulang | 5 ✅ / 2 ❌ dengan alasan per baris | ⬜ | |
| B4 | **Unduh Laporan Gagal** | CSV berisi 2 baris bermasalah + kolom alasan | ⬜ | |
| B5 | **Impor N Baris Valid** | Sejumlah baris valid dibuat; pesan ringkasan; muncul di Manajemen User | ⬜ | |
| B6 | Unggah ulang file yang SAMA | Semua baris ❌ (email sudah terdaftar) | ⬜ | |
| B7 | File dengan header diubah sembarangan | Ditolak: header tidak sesuai template | ⬜ | |
| B8 | CSV dengan kolom role kosong | User dibuat sebagai **pegawai** | ⬜ | |

## C. Bidang & Seksi + Kuota

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Menu **Bidang & Seksi** | 5 bidang expandable; badge kuota & jumlah seksi | ⬜ | |
| C2 | Ubah **kuota** bidang menjadi `0` / `9` | Ditolak (rentang 1–5) | ⬜ | |
| C3 | Ubah kuota bidang A menjadi **3** + ubah namanya | Tersimpan; cek pengaruhnya di skenario 01-E2 | ⬜ | |
| C4 | **+ Seksi** dengan nama yang sudah ada di bidang sama | Ditolak: nama seksi harus unik dalam bidang | ⬜ | |
| C5 | Tambah seksi baru → cek dropdown seksi di form Tambah User | Seksi baru muncul | ⬜ | |
| C6 | **Hapus seksi** yang masih beranggota | Ditolak: masih ada anggota | ⬜ | |
| C7 | **Hapus bidang** yang masih punya seksi | Ditolak: masih punya seksi | ⬜ | |
| C8 | Tambah bidang baru (kosong) → hapus | Berhasil dibuat & dihapus | ⬜ | |

## D. Integrasi Google (halaman — tanpa kunci nyata)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Menu **Integrasi Google** (admin) | Halaman: status, kredensial, Sheets, Drive, interval, log | ⬜ | |
| D2 | Unggah file **bukan** JSON service account (mis. file text biasa) | Ditolak dengan pesan jelas | ⬜ | |
| D3 | Isi URL spreadsheet `bukan-url` → simpan | Ditolak: URL tidak dikenali | ⬜ | |
| D4 | Isi URL valid apa pun + tab → simpan; **Test Koneksi** (tanpa kunci) | Langkah 1 ❗gagal dengan pesan “belum diunggah”; hasil per langkah tampil | ⬜ | |
| D5 | Interval isi selain 5/15/30/60 (lewat devtools/ubah nilai) | Ditolak | ⬜ | |
| D6 | Login pengurus → `/admin/integrasi/google` | 403 | ⬜ | |
