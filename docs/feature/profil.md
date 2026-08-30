# Fitur: Ubah Profil (Semua Role)

## Deskripsi
Setiap user (admin, pengurus, pegawai) dapat mengubah profilnya sendiri. **Nomor HP wajib diisi** setiap kali profil disimpan.

## Spesifikasi

### Form Ubah Profil
- Field: nama, email, **nomor HP (wajib)**, (read-only: role, bidang/seksi).
- Ganti password terpisah (password lama + baru + konfirmasi) — bawaan Breeze.

### Nomor HP
- Wajib (`required`) — profil tidak dapat disimpan tanpa nomor HP.
- Format nomor Indonesia: `^(\+62|62|0)8[1-9][0-9]{6,10}$` (08xx / 62xxx / +62xxx).
- Unique antar user.
- Kegunaan: kontak darurat saat koordinasi penggunaan kendaraan.

### Pengingat
- Dashboard menampilkan banner bila nomor HP belum diisi.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/profile` | Form profil |
| PATCH | `/profile` | Simpan perubahan |
| PUT | `/password` | Ganti password |

## Aturan Validasi
- Nama: wajib, maks 255.
- Email: wajib, format email, unique (kecuali milik sendiri).
- Phone: **wajib**, regex HP Indonesia, unique (kecuali milik sendiri).
- Password baru: minimal 8, konfirmasi cocok.

## Skenario Uji
1. Simpan profil tanpa nomor HP → ditolak dengan pesan error.
2. Nomor HP tidak valid ("12345") → ditolak.
3. Nomor HP sudah dipakai user lain → ditolak.
4. Simpan valid ("081234567890") → tersimpan; banner pengingat hilang.
