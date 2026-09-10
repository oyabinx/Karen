# Skenario 04 — Pengurus: Anggaran, Nota ×1,13 & Dokumen (P1)

**Akun**: `pengurus@karen.test` (atau `admin@karen.test` — kini juga berlaku admin).
**Prasyarat**: skenario 03 selesai (ada maintenance berstatus
terjadwal); siapkan kalkulator untuk cek ×1,13.

## A. Anggaran 4 Pos per Tahun

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Data Kendaraan → **Anggaran** mobil A | Halaman anggaran: form 4 pos + tabel Realisasi & Sisa + pemilih tahun ◀ ▶ | ok | apakah tahun yang ditampilkan secara default adalah tahun berjalan? atau ketika saya membuka sistem pada tahun 2027 juga akan mengikuti tahun 2027? |
| A1b | **Jawaban A1**: default tahun = `now()->year` — ya, otomatis mengikuti tahun berjalan. Buka di 2027 → langsung tampil 2027 | ⬜ | |
| A2 | Isi tahun **2026**: servis 5.000.000, suku cadang 8.000.000, AC 2.000.000, pelumas 1.500.000 → Simpan | Tersimpan; tabel sisa = anggaran (belum ada realisasi) | ok | tambahan: untuk field input angka, kalau bisa diberikan pemisah ribuan, jadi ketika saya ketik 1000000 tampilan menunjukkan 1.000.000 sebelum menekan tombol simpan anggaran|
| A3 | Tekan ▶ ke **2027** → isi nilai BERBEDA → Simpan; kembali ◀ 2026 | Nilai 2026 **tidak berubah** (tiap tahun independen) | ok | |
| A4 | Isi kuota/anggaran dengan teks/huruf | Ditolak (angka ≥ 0) | ok | secara tampilan sudah tidak bisa mengetik selain nomor |

## B. Input Nota ×1,13 → Generate Dokumen

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Jadwal Maintenance → pilih jadwal terjadwal → **Input Nota** (tautan/tombol menuju form) | Form: bengkel, nomor & tgl nota, 4 pos dengan kalkulasi ×1,13 live | revisi | pada menu jadwal maintenance di card mobil yang sedang di maintenance tidak ada tombol ataupun tautan Input Nota|
| B2 | Isi semua pos **0** → simpan | Ditolak: minimal satu pos > 0 | ⬜ | |
| B3 | Isi: bengkel “Bengkel Jaya”, nota `INV/001`, servis **500.000**, pelumas **300.000**, lain 0 → perhatikan angka live | Live: 565.000 & 339.000 (×1,13) | ⬜ | |
| B4 | **Simpan Nota & Generate Dokumen** | Sukses; jadwal otomatis **Selesai** | ⬜ | |
| B5 | Menu **Dokumen** | Muncul: **BEND26** + **2 Draft Nota** (servis & pelumas saja — pos 0 tidak dibuatkan) | ⬜ | |
| B6 | Unduh BEND26 → buka PDF | Rincian 4 pos: nilai, pajak 13%, ×1,13; total **904.000**; identitas mobil/bengkel/nota benar | ⬜ | |
| B7 | Unduh Draft Nota “pelumas” | Nilai **339.000**, label DRAFT, mobil & rujukan nota benar | ⬜ | |
| B8 | Kembali ke **Anggaran** mobil tsb (tahun sesuai) | Realisasi servis **565.000**; sisa berkurang; jika minus → merah + peringatan (tidak memblokir) | ⬜ | |
| B9 | **Input ulang/revisi nota** nilai berbeda → simpan | Realisasi mengikuti nilai terbaru; identitas nota lama **tidak hilang** | ⬜ | |
| B10 | Menu Dokumen → **Generate Kartu Inventaris** mobil tsb | PDF kartu inventaris: riwayat + anggaran vs realisasi vs sisa per pos | ⬜ | |
| B11 | Regenerate dokumen (tombol generate ulang bila ada / input ulang) | Versi naik (v2); versi lama tetap tersimpan (arsip) | ⬜ | |

## C. Batas & Keamanan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Login pegawai → `/pengurus/vehicles/1/budgets` | 403 | ⬜ | |
| C2 | Login pegawai → `/pengurus/documents` | 403 | ⬜ | |
| C3 | Unduh dokumen lewat URL langsung tanpa login | Ditolak (login) | ⬜ | |
