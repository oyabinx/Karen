# Skenario 10 — Integrasi Google Sheets (P3, tutorial lengkap)

**Akun**: `admin@karen.test`. **Prasyarat**: PC/laptop dengan akses
internet (untuk Google Cloud & Google Sheets — aplikasi Karen sendiri
tetap di intranet; integrasi hanya jalan **keluar/outbound**), akun
Google apa pun (Gmail pribadi maupun Google Workspace kantor), dan
satu browser.

> Ikuti berurutan A → D. Bagian E adalah tabel masalah umum.
> **PENTING:** gunakan spreadsheet **STAGING (uji)** dulu — jangan
> spreadsheet anggaran asli kantor — sampai semua langkah hijau,
> baru ganti ke spreadsheet produksi (langkah D6).

## A. Mendapatkan Kunci JSON (Google Cloud Console) — sekali saja

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Buka https://console.cloud.google.com → login akun Google | Dashboard Google Cloud tampil | ok | gratis, tanpa kartu kredit untuk kebutuhan ini |
| A2 | Klik selector project (bar atas, di samping logo) → **NEW PROJECT** → nama mis. `karen-sync` → **Create** → buka project itu | Project `karen-sync` aktif (nama terlihat di bar atas) | ok | satu project cukup dipakai selamanya |
| A3 | Menu ☰ → **APIs & Services → Library** → cari **Google Sheets API** → **Enable** | Sheets API berstatus Enabled | ok | |
| A4 | Ulangi A3 untuk **Google Drive API** (dipakai bila nanti mengaktifkan arsip PDF ke Drive; boleh sekalian saja) | Drive API Enabled | ok | opsional tapi disarankan |
| A5 | Menu ☰ → **IAM & Admin → Service Accounts** → **+ Create Service Account** → nama mis. `karen-sync` → **Create and Continue** → (role tidak perlu diisi) → **Done** | Daftar memuat service account baru ber-email panjang `karen-sync@karen-sync….iam.gserviceaccount.com` | ok |  |
| A6 | Klik service account itu → tab **KEYS** → **ADD KEY → Create new key** → pilih **JSON** → **Create** | File `.json` terunduh (mis. `karen-sync-….json`) — **ini kuncinya, simpan baik-baik** | ok | |
| A7 | Buka file JSON itu dengan text editor (Notepad) → cari baris `"client_email"` | Tercatat email service account — **copy alamat ini**, dibutuhkan di langkah B4 | ok | file berisi `"type": "service_account"` — jangan dibagikan ke siapa pun di luar keperluan konfigurasi |

## B. Menyiapkan Spreadsheet STAGING

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Di Google Sheets buat spreadsheet baru, beri nama mis. `Karen Anggaran STAGING` | Spreadsheet kosong terbentuk | ok | JANGAN pakai spreadsheet produksi |
| B2 | Pastikan ada 2 tab bernama persis `Anggaran` dan `Realisasi` (rename/ tambah tab) | Dua tab ada; nama harus PERSIS (huruf besar-kecil berpengaruh) dan sama dengan yang diisi di Karen (C4) | ok | |
| B3 | Di tab `Anggaran`, isi mulai **baris 2**, kolom A–D: `plat` · `pos` · `nominal` · `tahun`. Contoh 2 baris uji (plat HARUS sama persis dengan kendaraan terdaftar di Karen): `B 1234 XYZ` · `servis` · `5000000` · `2026` dan `B 1234 XYZ` · `pelumas` · `1000000` · `2026` | 2 baris contoh masuk; baris 1 boleh diberi judul kolom (tidak dibaca sistem — pembacaan mulai A2) | ok | pos valid hanya: `servis`, `suku_cadang`, `ac`, `pelumas`; nominal angka tanpa titik |
| B4 | Klik **Share/Bagikan** → tempel email service account dari A7 → beri akses **Editor** → Send | Service account tercantum sebagai Editor | ok | tanpa ini koneksi akan 403 (lihat E) |
| B5 | (Opsional, untuk arsip Drive) buat folder di Google Drive mis. `Karen - Arsip Dokumen` → **Share** ke email service account (Editor) → copy URL folder | URL folder berformat `https://drive.google.com/drive/folders/{id}` siap dipakai di C5 | ok | |

## C. Konfigurasi di Karen (admin)

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Login `admin@karen.test` → sidebar **Administrasi → Integrasi Google** (`/admin/integrasi/google`) | Halaman konfigurasi tampil (Kredensial, Sheets, Drive, Jalankan, Log Sinkronisasi) | ok | pengurus membuka URL ini → 403 |
| C2 | Bagian Kredensial: **pilih file** JSON unduhan A6 → **Simpan Konfigurasi** | Tersimpan; **email service account tampil** sebagai konfirmasi (hasil parsing); kunci tersimpan **terenkripsi di database** — tidak ada file kunci di server | ok | file harus `.json` ≤ 50 KB bertipe service_account |
| C3 | Bagian Google Sheets: tempel **URL spreadsheet** staging dari B1 (`https://docs.google.com/spreadsheets/d/…/edit`) → Simpan | URL diterima; ID spreadsheet terekstrak otomatis | ok | format URL lain → pesan validasi |
| C4 | Isi **nama tab** anggaran `Anggaran` dan realisasi `Realisasi` (default) → Simpan | Tersimpan | ok | harus persis sama dengan nama tab di B2 |
| C5 | (Opsional) Bagian Drive: aktifkan toggle + tempel URL folder dari B5 → Simpan | Tersimpan | ok | |
| C6 | Tekan **🔌 Test Koneksi** | **5 langkah bertahap hijau ✅**: kunci terbaca → kredensial diterima Google → spreadsheet terakses → tab ditemukan → folder Drive terakses (bila aktif). Langkah yang gagal menampilkan ❌ + pesan sebabnya | gagal | kalau ada ❌ lihat tabel E |
| C7 | Bagian Jalankan: aktifkan **integrasi**; interval biarkan 15 menit → Simpan | Badge status integrasi "aktif" | ⬜ | |
| C8 | Tekan **🔄 Sinkron Sekarang** | Pesan sukses sinkronisasi; baris baru muncul di **Log Sinkronisasi** berstatus `sukses` beserta durasinya | ⬜ | |

## D. Verifikasi Hasil Sinkronisasi

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Di Karen: menu **Kendaraan** → buka **Anggaran** unit plat `B 1234 XYZ` tahun 2026 | Nilai pos **servis Rp 5.000.000** & **pelumas Rp 1.000.000** tampil — persis dari tab `Anggaran` (upsert per plat+pos+tahun) | ⬜ | |
| D2 | Buka tab `Realisasi` di spreadsheet | Terisi baris nota otomatis dari Karen — kolom: `plat` · `id maintenance` · `bengkel` · `no. nota` · `tanggal` · `pos` · `nilai raw` · `nilai × koefisien pajak`. Ditulis ulang penuh tiap sinkronisasi | ⬜ | hanya maintenance yang sudah input nota |
| D3 | Ubah satu nilai di tab `Anggaran` (mis. servis jadi `6000000`) → kembali ke Karen → **Sinkron Sekarang** → refresh halaman Anggaran unit | Nilai di Karen ikut berubah menjadi Rp 6.000.000 | ⬜ | di produksi perubahan ditarik otomatis tiap interval polling oleh scheduler |
| D4 | Cek **Log Sinkronisasi** & dashboard pengurus | Log mencatat `sukses`; badge status sinkron terakhir tampil di dashboard pengurus | ⬜ | |
| D5 | Nonaktifkan toggle integrasi → **Sinkron Sekarang** | Task dilewati / badge "nonaktif" — tidak ada error | ⬜ | aktifkan kembali setelah uji |
| D6 | **Lulus semua?** Ganti ke spreadsheet PRODUKSI: paste URL spreadsheet asli kantor → bagikan ke email service account (Editor) → pastikan tab & formatnya sama → **Test Koneksi** ulang sampai hijau → **Sinkron Sekarang** | Konfigurasi menunjuk produksi; anggaran kantor tampil di Karen | ⬜ | spreadsheet staging boleh dihapus setelahnya |

## E. Masalah Umum & Solusi

| Gejala (pesan Test Koneksi / sync) | Sebab | Solusi |
|------------------------------------|-------|--------|
| `Class "Google\Client" not found` (langkah 2 gagal) | Paket `google/apiclient` belum/keliru versi (v1.x tidak punya kelas `Google\Client`) | SUDAH DIPERBAIKI 2026-09-14: proyek kini memakai `google/apiclient` v2.19 — cukup muat ulang halaman & tekan Test Koneksi ulang. Di server produksi nanti: jalankan `composer install` seperti biasa (tercakup di lock) |
| Langkah 3 ❌ `403 FORBIDDEN — spreadsheet tidak dibagikan…` | Spreadsheet belum di-share ke service account, atau aksesnya bukan Editor | Ulangi B4 dengan email service account yang **persis sama** dengan yang tampil di C2 |
| Langkah 5 ❌ `404 File not found` (folder Drive) | (a) Folder belum di-share ke service account — Google menyamarkan folder privat sebagai "tidak ada"; (b) **bug scope (fixed 2026-09-14)**: Karen lama memakai scope sempit `drive.file` yang tak bisa melihat folder walau sudah di-share — kini memakai scope `drive` + `supportsAllDrives` | Pastikan share **Editor** ke email service account (bisa perlu beberapa menit sampai berlaku), perbarui Karen ke revisi terakhir, tekan Test Koneksi ulang. Belum butuh arsip Drive? Matikan toggle-nya |
| Langkah 4 ❌ tab tidak ditemukan | Nama tab di spreadsheet ≠ nama tab di konfigurasi C4 | Samakan persis (huruf besar-kecil, tanpa spasi berlebih) |
| Langkah 1–2 ❌ kunci ditolak / tidak valid | File bukan kunci JSON service account, rusak, atau > 50 KB | Unduh ulang kunci dari langkah A6; jangan file kunci tipe lain |
| Sync gagal `kredensial belum dikonfigurasi` | Kunci dihapus dari konfigurasi | Upload ulang (C2) |
| Baris anggaran tidak masuk padahal sync sukses | Plat di sheet tidak sama persis dengan plat di Karen, atau pos salah | Samakan plat (spasi/penulisan) & gunakan pos: servis/suku_cadang/ac/pelumas |
| Nominal terbaca aneh | Sel berformat teks/rupiah dengan pemisah | Isi angka polos (mis. `5000000`) |

> Catatan: cron/scheduler sungguhan (penarik otomatis tiap interval
> polling) hanya berjalan di server produksi (Fase 10). Saat UAT di
> laptop, tombol **Sinkron Sekarang** menggantikan perannya.
