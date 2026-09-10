# Skenario 04 — Pengurus: Anggaran, Nota × Koefisien & Dokumen (P1)

**Akun**: `pengurus@karen.test` (atau `admin@karen.test` — kini juga berlaku admin).
**Prasyarat**: skenario 03 selesai (ada maintenance berstatus
terjadwal); siapkan kalkulator untuk cek koefisien (default 1,13 —
cek nilai aktual di Admin → Pengaturan Aplikasi).

## A. Anggaran 4 Pos per Tahun

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Data Kendaraan → **Anggaran** mobil A | Halaman anggaran: form 4 pos + tabel Realisasi & Sisa + pemilih tahun ◀ ▶ | ok | apakah tahun yang ditampilkan secara default adalah tahun berjalan? atau ketika saya membuka sistem pada tahun 2027 juga akan mengikuti tahun 2027? |
| A1b | **Jawaban A1**: default tahun = `now()->year` — ya, otomatis mengikuti tahun berjalan. Buka di 2027 → langsung tampil 2027 | ⬜ | |
| A2 | Isi tahun **2026**: servis 5.000.000, suku cadang 8.000.000, AC 2.000.000, pelumas 1.500.000 → Simpan | Tersimpan; tabel sisa = anggaran (belum ada realisasi) | ok | tambahan: untuk field input angka, kalau bisa diberikan pemisah ribuan, jadi ketika saya ketik 1000000 tampilan menunjukkan 1.000.000 sebelum menekan tombol simpan anggaran|
| A2b | **Verifikasi ulang A2**: ketik `1000000` | Tampil **1.000.000** secara live (pemisah ribuan otomatis) | ⬜ | |
| A3 | Tekan ▶ ke **2027** → isi nilai BERBEDA → Simpan; kembali ◀ 2026 | Nilai 2026 **tidak berubah** (tiap tahun independen) | ok | |
| A4 | Isi kuota/anggaran dengan teks/huruf | Ditolak (angka ≥ 0) | ok | secara tampilan sudah tidak bisa mengetik selain nomor |

## B. Input Nota (rincian → total otomatis → × koefisien)

> Form input nota direvisi *(rev UAT 04)*: input utama = **rincian
> baris** (deskripsi + nominal) per pos; **total per pos otomatis**
> dari jumlah rincian (readonly — bukan input field); **angka
> koefisien tidak ditampilkan** (cukup "dikalikan koefisien pajak").

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Jadwal Maintenance → pilih jadwal terjadwal → **Input Nota** (tautan/tombol menuju form) | Form: bengkel, nomor & tgl nota, 4 pos dengan kalkulasi ×1,13 live | revisi | pada menu jadwal maintenance di card mobil yang sedang di maintenance tidak ada tombol ataupun tautan Input Nota|
| B1b | **Verifikasi ulang B1 (implementasi selesai)**: buka Jadwal Maintenance | Kartu maintenance memiliki tombol **Input Nota** | ⬜ | |
| B2 | Isi semua rincian **0 / tanpa baris** → simpan | Ditolak: minimal satu rincian bernilai > 0 | ⬜ | |
| B3 | Isi: bengkel "Bengkel Jaya", nota `INV/001`; pos **Servis** tambah 2 rincian (Ganti oli 300.000 + Ganti kampas 200.000); pos **Pelumas** 1 rincian 300.000; perhatikan angka live | Subtotal servis otomatis **500.000**; total live **565.000** & **339.000** — **tanpa menginput total manual**, tanpa angka koefisien terlihat | ⬜ | |
| B4 | **Simpan Nota & Generate Dokumen** | Sukses; jadwal otomatis **Selesai** | ⬜ | |
| B5 | Menu **Dokumen** | Muncul: **BEND26** + **2 Draft Nota** (servis & pelumas saja — pos 0 tidak dibuatkan) | ⬜ | |
| B6 | Unduh BEND26 → buka PDF | Rincian 4 pos: nilai, pajak, × koefisien; total **904.000**; identitas mobil/bengkel/nota benar | ⬜ | |
| B7 | Unduh Draft Nota "pelumas" | Nilai **339.000**, label DRAFT, mobil & rujukan nota benar | ⬜ | |
| B8 | Kembali ke **Anggaran** mobil tsb (tahun sesuai) | Realisasi servis **565.000**; sisa berkurang; jika minus → merah + peringatan (tidak memblokir) | ⬜ | |
| B9 | **Input ulang/revisi nota** nilai berbeda → simpan | Realisasi mengikuti nilai terbaru; identitas nota lama **tidak hilang**; rincian lama diganti seluruhnya | ⬜ | |
| B10 | Menu Dokumen → **Generate Kartu Inventaris** mobil tsb | PDF kartu inventaris: riwayat + anggaran vs realisasi vs sisa per pos | ⬜ | |
| B11 | Regenerate dokumen (tombol generate ulang bila ada / input ulang) | Versi naik (v2); versi lama tetap tersimpan (arsip) | ⬜ | |
| B12 | Buka form nota dari **ponsel** (viewport kecil / devtools mobile) | Form **mobile-first**: pos vertikal, tombol + rincian lebar, nominal berpemisah ribuan, tombol ≥44px nyaman disentuh | ⬜ | |

## C. Batas & Keamanan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Login pegawai → `/pengurus/vehicles/1/budgets` | 403 | ⬜ | |
| C2 | Login pegawai → `/pengurus/documents` | 403 | ⬜ | |
| C3 | Unduh dokumen lewat URL langsung tanpa login | Ditolak (login) | ⬜ | |

## D. Realisasi Bulanan *(skema baru UAT 04 — rev terakhir: default list mobil)*

**Prasyarat**: minimal ada 1 nota tersimpan pada bulan berjalan (bagian B).

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Menu **Realisasi Bulanan** (sidebar pengurus) | **Default: daftar mobil** yang maintenance bulan itu — kartu per mobil: nama, plat, jumlah maintenance, total realisasi (termasuk pajak), chip per pos bernilai; ringkasan 4 pos armada di atas | ⬜ | |
| D2 | **Klik satu mobil** | Pindah ke rincian mobil: seksi per pos bernilai — subtotal (sebelum pajak) + total (setelah koefisien) + daftar nota per pos (bengkel, tanggal, nomor nota) | ⬜ | |
| D3 | Buka **expandable rincian** pada satu nota | Tampil rincian baris nota (deskripsi + nominal) sesuai yang diinput di bagian B | ⬜ | |
| D4 | Tekan **← Semua mobil** | Kembali ke list mobil | ⬜ | |
| D5 | Ganti **pemilih bulan** ke bulan tanpa maintenance | List kosong dengan pesan "Tidak ada mobil yang maintenance pada bulan ini"; ringkasan 0 | ⬜ | |
| D6 | Klik **⬇ CSV** (list & saat drill-down) | File CSV terunduh: kolom tanggal; mobil; plat; bengkel; nota; pos; rincian; nilai; koefisien; total — mengikuti bulan (dan mobil bila sedang drill-down) | ⬜ | |
| D7 | Cek bulan pemilihan | Dropdown berisi 12 bulan terakhir (bulan berjalan paling atas) | ⬜ | |

## E. Anggaran Terpusat & Koefisien Pajak *(skema baru UAT 04)*

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| E1 | Menu **Anggaran** (sidebar pengurus) | Ringkasan **seluruh armada** per tahun (pemilih tahun): anggaran vs realisasi vs sisa per pos tiap mobil; ada pintasan atur anggaran per mobil | ⬜ | |
| E2 | Login **admin** → Pengaturan Aplikasi → ubah **Koefisien Pajak** `1,15` → simpan | Tersimpan; rentang ditolak di luar 1,00–2,00 | ⬜ | |
| E3 | Pengurus input **nota baru** setelah koefisien berubah | Nota baru memakai **1,15** (cek realisasi); nota lama **tetap 1,13** | ⬜ | |
| E4 | Kembalikan koefisien ke `1,13` | Tersimpan | ⬜ | |
