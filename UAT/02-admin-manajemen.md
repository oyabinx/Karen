# Skenario 02 — Admin: Manajemen User, Impor CSV, Organisasi & Kuota (P1)

**Akun**: `admin@karen.test` / `password`.
**Prasyarat**: skenario 01 selesai minimal bagian A–B (untuk data HP).

## A. Manajemen User

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Menu **Manajemen User** | Tabel user + filter (cari/role/bidang/status) | ok | |
| A2 | **+ Tambah User**: role **pegawai**, seksi **kosong** → simpan | Ditolak: seksi wajib untuk pegawai | ok | |
| A3 | Tambah pegawai lengkap (nama/email/password ≥8/seksi) | Tersimpan; logout → login akun baru sukses | ok | |
| A4 | Tambah user dengan email yang sudah dipakai | Ditolak: email sudah terdaftar | ok | |
| A5 | **Ubah** user: ganti role pegawai → pengurus (seksi tetap) | Berubah; menu user itu berubah setelah login ulang | ok | |
| A6 | **Nonaktifkan** user → coba login akun itu | Login ditolak; di tabel berstatus Nonaktif (keruh) | ok | |
| A7 | **Aktifkan** kembali → login | Berhasil | ok | |
| A8 | Nonaktifkan **akun admin sendiri** | Ditolak dengan pesan | ok | |
| A9 | Filter: cari nama / role=pengurus / bidang / status=nonaktif | Hasil sesuai filter | ok | |

## B. Impor Massal CSV

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | **Impor CSV → Unduh Template CSV** | File terunduh; header `nama;email;password;bidang;seksi;role` + 1 baris contoh | revisi | terkait field "Choose File" untuk deberikan warna pembeda karena tidak terlihat seperti tombol, begitu juga dengan nama file yang timbul setelah memilih file juga di berikan pembeda supaya jelas apakah file yang dipilih namanya terlihat dengan jelas |
| B2 | Isi template 5 baris valid (bidang & seksi sesuai seeder) → Unggah → Pratinjau | 5 baris ✅; BELUM ada user baru dibuat (dry-run) | ok | |
| B3 | Tambah 2 baris rusak: seksi tidak cocok dengan bidang; password `abc` (< 8) → unggah ulang | 5 ✅ / 2 ❌ dengan alasan per baris | revisi | untuk password tambahkan pengaman supaya untuk character spasi tidak bisa digunakan, posisi sekarang ketika password menggunakan spasi masih bisa dan juga untuk login juga masih valid |
| B4 | **Unduh Laporan Gagal** | CSV berisi 2 baris bermasalah + kolom alasan | ok | |
| B5 | **Impor N Baris Valid** | Sejumlah baris valid dibuat; pesan ringkasan; muncul di Manajemen User | ok | |
| B6 | Unggah ulang file yang SAMA | Semua baris ❌ (email sudah terdaftar) | ok | |
| B7 | File dengan header diubah sembarangan | Ditolak: header tidak sesuai template | ok | |
| B8 | CSV dengan kolom role kosong | User dibuat sebagai **pegawai** | ok | |

## C. Bidang & Seksi + Kuota

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Menu **Bidang & Seksi** | 5 bidang expandable; badge kuota & jumlah seksi | revisi | revisi terhadap tampilan default menu Bidang & Seksi terkait expand untuk nama bidang yang paling atas, buatkan default tampilan nya adalah nama semua bidang dengan posisi collapse supaya mudah untuk dipilih dan tidak terlalu banyak scroll  |
| C2 | Ubah **kuota** bidang menjadi `0` / `9` | Ditolak (rentang 1–5) | revisi | untuk kuota bidang saya akan rubah kesepakatan nya menjadi maksimal 9 saja, sehingga rentang yang diperbolehkan yaitu 1 sampai 9 |
| C3 | Ubah kuota bidang A menjadi **3** + ubah namanya | Tersimpan; cek pengaruhnya di skenario 01-E2 | ok | |
| C4 | **+ Seksi** dengan nama yang sudah ada di bidang sama | Ditolak: nama seksi harus unik dalam bidang | ok | |
| C5 | Tambah seksi baru → cek dropdown seksi di form Tambah User | Seksi baru muncul | ok | |
| C6 | **Hapus seksi** yang masih beranggota | Ditolak: masih ada anggota | ok | |
| C7 | **Hapus bidang** yang masih punya seksi | Ditolak: masih punya seksi | ok | |
| C8 | Tambah bidang baru (kosong) → hapus | Berhasil dibuat & dihapus | ok | |

## D. Integrasi Google (halaman — tanpa kunci nyata)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Menu **Integrasi Google** (admin) | Halaman: status, kredensial, Sheets, Drive, interval, log | ok | |
| D2 | Unggah file **bukan** JSON service account (mis. file text biasa) | Ditolak dengan pesan jelas | ok | |
| D3 | Isi URL spreadsheet `bukan-url` → simpan | Ditolak: URL tidak dikenali | ok | |
| D4 | Isi URL valid apa pun + tab → simpan; **Test Koneksi** (tanpa kunci) | Langkah 1 ❗gagal dengan pesan “belum diunggah”; hasil per langkah tampil | revisi | terdapat internal server error ketika klik tombol Test Koneksi, error exception app/Services/Google/SheetsBudgetSync.php:189 Trying to access array offset on null |
| D5 | Interval isi selain 5/15/30/60 (lewat devtools/ubah nilai) | Ditolak | ok | |
| D6 | Login pengurus → `/admin/integrasi/google` | 403 | ok | |
