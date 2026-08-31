# Fitur: UI Responsive — Desktop & Mobile

## Deskripsi
Karen adalah **satu codebase web responsive** (tidak ada aplikasi mobile terpisah). Antarmuka menyesuaikan lebar layar dengan dua pola tampilan utama: **desktop** (layar lebar, PC kantor) dan **mobile** (HP pegawai/pengurus di lapangan — saat mengemudi/melapor keluhan). Implementasi memakai **Tailwind CSS v4 breakpoint utilities** (mobile-first) tanpa JavaScript framework tambahan (Alpine.js hanya untuk interaksi ringan: drawer, dropdown, accordion).

## Breakpoint (Tailwind v4)
| Token | Lebar | Perangkat target |
|-------|-------|------------------|
| default | < 640px | HP kecil |
| `sm` | ≥ 640px | HP besar |
| `md` | ≥ 768px | Tablet |
| `lg` | ≥ 1024px | **Desktop kantor (pola desktop aktif)** |
| `xl` | ≥ 1280px | Monitor lebar |

## Perbedaan Pola Tampilan

### Navigasi
- **Desktop (≥ lg)**: sidebar tetap di kiri — menu dikelompokkan per fungsi; dapat di-collapse jadi ikon saja.
- **Mobile (< lg)**: navbar atas ringkas (logo + notifikasi badge + avatar) + **menu drawer hamburger**; aksi paling sering juga tersedia via **bottom navigation bar** (maks 4 item) agar satu jempol terjangkau — implementasi awal: Beranda · Profil · Keluar; slot "Cari Mobil" & "Peminjaman Aktif" aktif saat Fase 5 (peminjaman) tersedia.

### Tabel Data (user, kendaraan, booking, laporan)
- **Desktop**: tabel penuh dengan kolom lengkap + filter di atas.
- **Mobile**: tabel berubah menjadi **daftar kartu bertumpuk** (setiap baris jadi kartu: judul = nama/nomor plat, subjudul = info penting, badge status tetap terlihat); kolom yang jarang dipakai disembunyikan atau masuk "detail".

### Grid & Form
- **Grid mobil & kartu dashboard**: desktop 3–4 kolom → tablet 2 → mobile 1–2 kolom (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`).
- **Form** (booking, input nota, wizard event): desktop 2 kolom sejajar → mobile 1 kolom berurutan; tombol utama full-width di mobile.
- **Input tanggal** memakai `type="date"` native agar picker HP terpakai (lebih cepat daripada custom picker).
- **Touch target** minimum 44×44px untuk semua tombol/ikon yang bisa disentuh.

### Halaman Khusus
- **Cari mobil (pegawai)**: form tanggal sticky di atas hasil saat scroll di mobile; kartu mobil menampilkan foto, plat, kapasitas + tombol "Pilih" yang langsung terlihat tanpa scroll.
- **Tombol "Selesai"** (penyelesaian peminjaman + pop-up keluhan opsional): selalu tampak menonjol (warna aksen) di dashboard mobile — ini aksi lapangan paling penting.
- **Form keluhan**: textarea lebar penuh, mudah diisi satu tangan.
- **PDF dokumen (bend26/nota/kartu inventaris)**: mobile menampilkan tombol unduh (bukan preview inline); desktop boleh preview iframe.

## Rencana Uji Visual
- Device emulation di browser (DevTools): **360×800** (HP), **768×1024** (tablet), **1440×900** (desktop).
- Checklist per halaman: navigasi terjangkau, tabel tidak overflow horizontal, form tidak terpotong, badge terbaca, tombol ≥ 44px.
- Smoke test di HP fisik via jaringan intranet/WiFi kantor sebelum rilis.

## Konvensi Implementasi
- Semua layout ditulis **mobile-first** (default = mobile, lalu `lg:` untuk desktop) — konsisten dengan Tailwind.
- Komponen Blade partial (`resources/views/components/`) menerima kedua pola; dilarang membuat halaman mobile terpisah.
- Ikon memakai satu set (Heroicons bawaan Breeze), ukuran seragam.
