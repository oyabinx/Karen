# Fitur: Organisasi — Bidang & Seksi (Admin)

## Deskripsi
Kantor memiliki **5 bidang**, masing-masing dibagi menjadi **beberapa seksi**. Admin mengelola data ini; penempatan pegawai mengacu pada seksi.

## Spesifikasi

### Bidang
- Seeder awal membuat 5 bidang (nama disesuaikan kantor saat setup).
- Admin dapat: menambah bidang baru, mengubah nama, menghapus bidang (hanya bila **tidak memiliki seksi dan tidak ada user terkait**).

### Seksi
- Seksi selalu milik satu bidang.
- Admin dapat: menambah seksi ke bidang tertentu, mengubah nama, memindah bidang, menghapus seksi (hanya bila **tidak ada user terkait**).
- Nama seksi unik dalam satu bidang.

### UI
- Halaman daftar bidang → tiap bidang expandable menampilkan daftar seksi + jumlah anggota.
- Dropdown berjenjang (bidang → seksi) dipakai di form user.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/admin/bidang` | Daftar bidang + seksi |
| POST | `/admin/bidang` | Tambah bidang |
| PUT | `/admin/bidang/{bidang}` | Ubah nama bidang |
| DELETE | `/admin/bidang/{bidang}` | Hapus bidang (bila kosong) |
| POST | `/admin/bidang/{bidang}/seksi` | Tambah seksi pada bidang |
| PUT | `/admin/seksi/{seksi}` | Ubah / pindah bidang seksi |
| DELETE | `/admin/seksi/{seksi}` | Hapus seksi (bila kosong) |

## Aturan Validasi
- Nama bidang: wajib, maks 100, unique.
- Nama seksi: wajib, maks 100, unique dalam bidang.

## Skenario Uji
1. Ubah nama salah satu dari 5 bidang seeder → tersimpan.
2. Tambah seksi ke bidang → muncul di dropdown form user.
3. Hapus bidang yang masih memiliki seksi → ditolak dengan pesan.
4. Hapus seksi yang masih memiliki user → ditolak dengan pesan.
