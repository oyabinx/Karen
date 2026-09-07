# Fitur: Manajemen User (Admin)

## Deskripsi
Admin mengelola akun pengguna sistem: membuat, melihat, mengubah, menonaktifkan, dan mengatur role serta penempatan bidang/seksi. Tidak ada registrasi mandiri.

## Spesifikasi

### Daftar User
- Tabel: nama, email, no. HP, role, bidang/seksi, status (aktif/nonaktif).
- Pencarian nama/email; filter per role dan per bidang.

### Tambah User
- Field: nama, email, password (awal, min 8 karakter **tanpa spasi**), role, seksi (**wajib untuk pegawai dan pengurus**; hanya admin yang boleh tanpa seksi), no. HP (opsional saat pembuatan — **wajib diisi user saat edit profil**).
- Password awal di-hash; admin bisa mencentang "wajib ganti password saat login pertama" (opsional).

### Ubah User
- Semua field dapat diubah, termasuk role dan seksi.

### Nonaktifkan (Soft Delete)
- User dinonaktifkan, tidak bisa login, tidak muncul di daftar aktif.
- Riwayat peminjamannya **tetap tersimpan** (tidak ikut terhapus).
- User dapat diaktifkan kembali.

### Impor Massal via CSV (Bulk Upload)
Supaya admin tidak menginput pegawai satu per satu:

- **Unduh Template CSV** — tombol "Unduh Template CSV" menyediakan file draft berformat tetap sehingga admin tidak perlu membuat file sendiri. **Pemisah kolom memakai titik koma (`;`)** agar kompatibel dengan locale Excel Indonesia (yang menggunakan koma sebagai desimal):
  ```csv
  nama;email;password;bidang;seksi;role
  Budi Santoso;budi@kantor.go.id;Password123;Bidang Umum;Seksi Kepegawaian;pegawai
  ```
  (kolom `role` opsional — kosong = `pegawai`; template berisi 1 baris contoh + komentar header yang diabaikan sistem)
- **Unggah CSV** → sistem menampilkan **pratinjau (dry-run)**: seluruh baris divalidasi tanpa menulis ke database; baris valid ditandai ✅, baris bermasalah ditandai ❌ beserta alasannya per baris (email duplikat di file/database, seksi tidak ditemukan di bawah bidang tersebut, password < 8 karakter ATAU mengandung spasi, format email salah, nama kosong).
- **Konfirmasi Impor** — hanya baris ✅ yang dibuat; ringkasan hasil ditampilkan ("berhasil X, gagal Y"); admin dapat mengunduh laporan baris gagal (CSV) untuk diperbaiki dan diunggah ulang.
- Aturan: `bidang` + `seksi` harus cocok (seksi terdaftar di bawah bidang); email harus unik di database maupun di dalam file; password default akan di-hash; pegawai hasil impor tetap wajib melengkapi nomor HP saat edit profil.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/admin/users` | Daftar + filter |
| POST | `/admin/users` | Simpan user baru |
| GET | `/admin/users/{user}/edit` | Form ubah |
| PUT | `/admin/users/{user}` | Proses ubah |
| DELETE | `/admin/users/{user}` | Nonaktifkan (soft delete) |
| PATCH | `/admin/users/{id}/restore` | Aktifkan kembali — parameter `{id}` dengan pencarian manual `withTrashed()` karena user nonaktif tersembunyi dari route-model binding |
| GET | `/admin/users/template` | Unduh template CSV impor massal |
| POST | `/admin/users/import` | Unggah CSV → pratinjau validasi (dry-run) |
| POST | `/admin/users/import/commit` | Konfirmasi: tulis baris valid ke database |

## Aturan Validasi
- Nama: wajib, maks 255.
- Email: wajib, format email, unique.
- Password: minimal 8 karakter TANPA SPASI (saat tambah wajib; saat ubah opsional = tidak diganti).
- Role: salah satu dari `admin`, `pengurus`, `pegawai`.
- Seksi: **wajib untuk role `pegawai` dan `pengurus`** (kuota peminjaman dihitung pada bidang tempat seksi terdaftar); opsional hanya untuk `admin`.

## Skenario Uji
1. Tambah user pegawai dengan seksi → muncul di daftar, bisa login.
2. Email duplikat → validasi gagal.
3. Nonaktifkan user → login ditolak, riwayat bookingnya tetap ada.
4. Ubah role pegawai → pengurus → menu & dashboard berubah setelah login ulang.
5. Unduh template CSV → isi 10 pegawai → unggah → pratinjau menampilkan 9 ✅ / 1 ❌ (seksi tidak cocok dengan bidang) → konfirmasi → 9 user dibuat, laporan 1 baris gagal dapat diunduh.
6. Unggah ulang file yang sama tanpa baris gagal → semua baris ditolak (email sudah ada).
7. Kolom `role` kosong di CSV → user dibuat sebagai `pegawai`.
