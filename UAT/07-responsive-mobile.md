# Skenario 07 — Responsive / Mobile (P2)

**Cara**: buka aplikasi di browser → DevTools (F12) → toggle
device toolbar → pilih **360×800** (atau HP fisik via WiFi kantor:
`http://<ip-laptop>`). Uji sebagai `pegawai@karen.test`.

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| 1 | Buka dashboard | Tanpa scroll horizontal; kartu menumpuk 1–2 kolom | ⬜ | |
| 2 | **Topbar**: tombol hamburger (☰) | Ukuran sentuh ≥ 44px; drawer menu terbuka dari kiri | ⬜ | |
| 3 | Drawer: klik menu → tutup | Menutup via pilih menu / X / klik overlay / tombol back HP | ⬜ | |
| 4 | **Bottom navigation** | 4 item: Beranda · Cari Mobil · Pinjaman · Profil; item aktif berwarna | ⬜ | |
| 5 | Bottom-nav **admin** | 3 item (Beranda · Profil · Keluar) — admin tidak meminjam | ⬜ | |
| 6 | Cari Mobil: isi tanggal → hasil | Form tanggal memakai **date picker native HP**; hasil kartu 1–2 kolom | ⬜ | |
| 7 | Form tanggal saat menggulir hasil | Form tetap terlihat (sticky) | ⬜ | |
| 8 | Manajemen User (admin, mobile) | Tabel berubah menjadi **kartu bertumpuk** (tanpa scroll horizontal) | ⬜ | |
| 9 | Tombol **Selesai** di dashboard/peminjaman | Menonjol; modal pop-up terjangkau; textarea nyaman satu tangan; tombol full lebar | ⬜ | |
| 10 | Semua tombol umum (simpan/filter/pilih) | Ukuran sentuh cukup (≥ 44px) — coba dengan jempol | ⬜ | |
| 11 | Tablet 768×1024 | Layout menengah nyaman (2 kolom, drawer tetap) | ⬜ | |
| 12 | Desktop 1440px | Sidebar tetap kiri; grid 3–4 kolom | ⬜ | |
