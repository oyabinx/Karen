# TASKLIST — Tindak Lanjut UAT 02 & 03 + Skema Baru Durasi Maksimal

Sumber: UAT/02 & UAT/03 ulangan user + permintaan skema baru.
Prinsip: eksekusi berurutan tanpa bolak-balik.

---

## KELOMPOK A — BUG: Penggantian Parsial Tidak Berjalan Benar (UAT 03 B7b/c/d)

### A1. Diagnosis B7b — kenapa booking 9–11 Sep + maintenance 11–13 Sep
   menghasilkan penggantian PENUH bukan parsial
   - Investigasi: apakah tombol parsial muncul? Apakah user klik tombol
     penuh? Atau logic partialRange salah menghitung?
   - Kemungkinan: user klik "Jadikan Pengganti" (penuh) padahal tombol
     parsial ada di bawahnya → perlu perbaikan UX agar pilihan parsial
     lebih jelas/tidak terlewat

### A2. Perbaikan UX halaman penggantian (B7b)
   - Tombol parsial dipromote: ditampilkan SEBELUM tombol penuh (urutan
     dibalik — parsial lebih dulu karena lebih disukai)
   - Tambah penanda visual "DIREKOMENDASIKAN" pada opsi parsial
   - Pesan jelas: "Tanggal 9–10 tetap Mobil A, tanggal 11 saja pakai pengganti"

### A3. Dukung parsial untuk tabrakan TENGAH (B7d + D3b)
   - Saat ini: tabrakan tengah → hanya penggantian penuh
   - Yang diinginkan user: maintenance/event 1 hari di tengah booking
     multi-hari → hari tengah pakai pengganti, hari sebelum & sesudah
     tetap mobil asli → menghasilkan 3 booking
   - Implementasi: partialRange() diperluas untuk middle-collision;
     assignPartial() mampu membuat booking di kedua sisi + booking tengah
   - Batasan praktis: maksimal 2 titik pembelah (booking bisa terpecah
     maksimal 3 bagian)

### A4. Test otomatis ulang semua skenario parsial
   - Edge-start, edge-end, middle-1-day, middle-multi-day
   - Dari maintenance DAN dari event
   - Verifikasi mobil asli tetap terisi di hari non-maintenance

---

## KELOMPOK B — BUG UI: Test Koneksi (UAT 02 D7)

### B1. Warna pesan hasil test koneksi
   - Gagal → background MERAH (bukan hijau)
   - Sukses → background HIJAU (tetap)

### B2. Perbaikan teks langkah 1
   - Sekarang: "Kunci service account terbaca & valid — belum diunggah"
     (kontradiktif)
   - Diperbaiki: pesan terpisah per kondisi:
     • Belum upload: "Kunci service account belum diunggah"
     • Upload tapi invalid: "Kunci terunggah tetapi tidak valid"
     • Upload dan valid: "Kunci valid: {email}"

---

## KELOMPOK C — UX: Posisi pesan error maintenance (UAT 03 B4b)

### C1. Pindahkan pesan "Tanggal mulai harus ≥ hari ini"
   - Dari: di bawah field Mulai (inline)
   - Ke: satu baris di bawah kotak "TAMBAH JADWAL" (banner)

---

## KELOMPOK D — SKEMA BARU: Durasi Maksimal Peminjaman Dapat Diatur Admin

### D1. Migrasi & model
   - Tabel `app_settings` (atau gunakan `integration_settings` yang ada
     dengan key `max_booking_days`)
   - Default: 3 hari (nilai saat ini)

### D2. BookingService membaca setting dinamis
   - Hapus konstanta MAX_DURASI_HARI = 3
   - rangeErrors() membaca dari setting (fallback 3 bila belum diatur)
   - Semua validasi (halaman pencarian, form request, service) otomatis
     mengikuti nilai setting

### D3. Halaman pengaturan admin
   - Menu baru "Pengaturan Aplikasi" di seksi Administrasi admin
   - Field: "Durasi Maksimal Peminjaman (hari)" — angka 1–30
   - Simpan → langsung berlaku (tanpa restart/hapus cache)

### D4. Update UI pencarian & form booking
   - Teks "Maksimal 3 hari" di halaman pencarian → dinamis
   - Placeholder & validasi mengikuti nilai setting

### D5. Test otomatis
   - Default 3 hari → booking 4 hari ditolak
   - Ubah ke 5 hari via admin → booking 5 hari diterima, 6 hari ditolak
   - Kembalikan ke 3 → booking 4 hari ditolak lagi
   - Test batas: 1 hari (minimal) dan 31 hari (ditolak)

---

## KELOMPOK E — Docs & UAT & Build & Commit

### E1. Docs: product.md (aturan baru), peminjaman.md (durasi dinamis),
   penggantian_mobil.md (parsial tengah), integrasi_google.md (UX)
### E2. UAT: update skenario 02 (D7), 03 (B7b-d), + baris baru untuk
   pengaturan durasi
### E3. Suite test penuh + build asset + log + commit + push

---

**Estimasi urutan eksekusi**: A1→A2→A3→A4 → B1→B2 → C1 → D1→D2→D3→D4→D5 → E1→E2→E3
