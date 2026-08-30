# Fitur: Autentikasi & Otorisasi

## Deskripsi
Login berbasis **email + password** untuk semua role. Otorisasi berbasis role (`admin`, `pengurus`, `pegawai`) via middleware; setiap role hanya dapat mengakses halamannya sendiri.

## Spesifikasi

### Login
- Halaman `/login` (layout guest): field email, password, checkbox "ingat saya".
- Validasi: email terdaftar + password cocok → masuk dashboard sesuai role.
- Gagal login → pesan error generik (tidak membocorkan email mana yang salah).
- Reset password via email (opsional, bawaan Breeze).

### Logout
- Tombol logout di navbar → hapus sesi → redirect ke `/login`.

### Registrasi
- **Dinonaktifkan**. Akun hanya dibuat oleh admin melalui manajemen user.

### Otorisasi
- Middleware `role:admin`, `role:pengurus`, `role:pegawai` pada grup route terkait.
- User tanpa akses → 403.
- Policy tambahan: pegawai hanya boleh melihat/mengembalikan **booking miliknya sendiri**.

## Alur
```
/login → cek kredensial → dashboard sesuai role
    admin   → /dashboard (view admin)
    pengurus → /dashboard (view pengurus)
    pegawai → /dashboard (view pegawai)
```

## Endpoint
| Method | Path | Akses | Keterangan |
|--------|------|-------|------------|
| GET | `/login` | guest | Form login |
| POST | `/login` | guest | Proses login |
| POST | `/logout` | auth | Keluar |

## Aturan Validasi
- Email: wajib, format email.
- Password: wajib.

## Skenario Uji
1. Login admin/pengurus/pegawai → dashboard masing-masing.
2. Pegawai akses `/admin/users` → 403.
3. Pegawai akses halaman return booking orang lain → 403.
4. Password salah → pesan error, tetap di halaman login.
