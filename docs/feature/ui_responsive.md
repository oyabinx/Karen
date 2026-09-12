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


## App-Shell (rev UAT 04-B12)
- Layout aplikasi memakai kerangka **app-shell**: `body` `h-dvh overflow-hidden`; area konten scroll **di dalam kontainernya sendiri** (`main` flex-1 `overflow-y-auto`).
- Bar navigasi bawah mobile **berada dalam alur dokumen** (bukan `fixed` menimpa) — secara fisik **tidak mungkin menutupi konten**; topbar mobile statis di atas area scroll; sidebar desktop menjadi kolom flex (tidak lagi `fixed`).
- Konsekuensi: padding bawah besar (pb-36) tidak diperlukan lagi; sticky form pencarian memakai `top-0` (header di luar scroll container).

## Sistem Tombol (rev UAT 04 — "tema tombol Karen")
Semua tombol aksi mengikuti hierarki tiga tier; **aksi utama selalu LEBIH BESAR dan LEBIH TEBAL daripada aksi sekunder** yang berdampingan dengannya:

| Tier | Bentuk | Kapan dipakai |
|------|--------|---------------|
| **Primer** | solid indigo `min-h-[48px]` `text-sm font-semibold` `rounded-lg` rata tengah (`x-primary-button` / `bg-indigo-600`) | Simpan/Cari/Filter/Terapkan, "+ Tambah X", Generate dokumen, CTA "Pinjam Mobil Ini", label unggah file |
| **Sekunder** | outline `min-h-[40px]` `text-sm` abu (`border-gray-300 text-gray-600 hover:bg-gray-50`) | Batal, Unduh Template/PDF, Test Koneksi |
| **Kompak** | 44px, `text-sm`/`text-xs` | aksi per-baris di tabel/daftar padat (Ubah, Pilih, Ganti unit, Tandai selesai), pill tab filter, pagination |

- **Warna semantik** menggantikan indigo TAPI mempertahankan geometri tier-nya: **emerald** = selesai/generate dokumen (Selesai—Kembalikan, Simpan Jadwal, Export CSV), **amber** = kondisi edit/jadwal (Selesai Edit, Atur Pengganti, Sedang Maintenance), **red** = destruktif (Batalkan Peminjaman).
- Pemosisian: pasangan Batal + aksi utama memakai `flex flex-col-reverse sm:flex-row sm:justify-end` — mobile: utama full-width DI ATAS, Batal di bawah; desktop sebaris rata kanan.
- Komponen `resources/views/components/primary-button.blade.php` = sumber kebenaran tier primer (indigo 48px); timpa warna via class `bg-*`/`hover:bg-*` di call site.
- Pengecualian yang disengaja: tombol keluar menu, ikon, dan input tetap 44px (touch target navigasi, bukan aksi).
