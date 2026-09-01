# Fitur: Konfigurasi Integrasi Google (Admin)

## Deskripsi
Halaman khusus **admin** untuk mengonfigurasi integrasi Karen dengan lingkungan Google **tanpa perlu mengedit file atau folder di server**. Semua pengaturan runtime tersimpan di database (nilai sensitif dienkripsi) — tidak ada kredensial Google di `.env`.

Karena Karen berjalan di intranet tanpa inbound internet, arah integrasi **selalu keluar (outbound)**: Google Sheets API untuk sinkronisasi anggaran, dan (opsional) Google Drive API untuk arsip dokumen. **Google Apps Script tidak dipakai untuk memanggil Karen** sehingga tidak ada input URL Apps Script.

## Spesifikasi

### Halaman `/admin/integrasi/google`
Bagian **Kredensial**:
- Upload **kunci JSON Service Account** (file dari Google Cloud Console). Setelah upload, sistem menampilkan email service account (hasil parsing) sebagai konfirmasi.
- Kunci disimpan **terenkripsi di database** (Laravel Crypt, tabel `integration_settings`) — **tidak ada file kunci di server dan tanpa fallback `.env`**; kunci lama ditimpa; tidak pernah ditampilkan ulang dalam bentuk asli.

Bagian **Google Sheets** (sinkronisasi anggaran — lihat [anggaran_maintenance.md](anggaran_maintenance.md)):
- Input **URL Spreadsheet** anggaran → sistem mengekstrak Spreadsheet ID otomatis (validasi format URL `docs.google.com/spreadsheets/d/...`).
- Input **nama tab/sheet** untuk data anggaran (default: `Anggaran`) dan realisasi (default: `Realisasi`).
- Petunjuk statis: *"Bagikan spreadsheet ke email service account di atas dengan akses Editor."*

Bagian **Google Drive** (opsional — arsip dokumen):
- Toggle aktif/nonaktif.
- Input **URL folder Drive** → ekstrak Folder ID; hasil generate PDF (bend26, draft nota, kartu inventaris) diunggah ke folder tersebut sebagai arsip sekunder.

Bagian **Jalankan**:
- Toggle **integrasi aktif/nonaktif** (nonaktif = task sync dilewati).
- Input **interval polling** (pilihan: 5/15/30/60 menit; default 15).
- Tombol **"Sinkron Sekarang"** — menjalankan `budgets:sync-sheets` manual.
- Tombol **"Test Koneksi"** — memverifikasi bertahap: (1) kunci terbaca & valid, (2) kredensial diterima Google (token), (3) spreadsheet dapat diakses, (4) tab ditemukan, (5) folder Drive dapat diakses (bila aktif). Hasil tiap langkah tampil ✅/❌ dengan pesan error.

Bagian **Log Sinkronisasi**:
- Tabel 20 eksekusi terakhir: waktu, status (`sukses`/`gagal`), durasi, pesan (mis. "403 FORBIDDEN — spreadsheet tidak dibagikan ke service account").
- Status terakhir juga tampil sebagai badge di dashboard pengurus (konsisten dengan alert sync).

### Alur Kerja Admin (first-time setup)
1. Google Cloud Console (di luar Karen): buat project → aktifkan Sheets API (+ Drive API bila perlu arsip) → buat Service Account → unduh kunci JSON.
2. Buat spreadsheet anggaran → bagikan ke email service account (Editor).
3. Di Karen: upload kunci → paste URL spreadsheet → set nama tab → **Test Koneksi** sampai semua ✅ → aktifkan integrasi → **Sinkron Sekarang**.

### Uji dengan Spreadsheet STAGING (sebelum produksi)
1. Buat spreadsheet uji terpisah (JANGAN spreadsheet produksi) dengan dua tab bernama sesuai konfigurasi (mis. `Anggaran`, `Realisasi`).
2. Bagikan ke email service account staging.
3. Tab `Anggaran` isi contoh baris mulai sel A2: `plat;pos;nominal;tahun` — nilai dipisah **titik koma**? TIDAK — Sheets API membaca per sel: isi `B 1234 XYZ` | `servis` | `5000000` | `2026` pada kolom A–D.
4. Di Karen (masih environment dev/staging): simpan konfigurasi → **Test Koneksi** (5 langkah hijau) → **Sinkron Sekarang** → periksa: anggaran masuk halaman Anggaran kendaraan; tab `Realisasi` terisi baris kontrak (plat, id maintenance, bengkel, nota, tanggal, pos, raw, ×1,13).
5. Ubah satu nilai di tab `Anggaran` → tunggu interval (atau klik Sinkron Sekarang) → nilai di Karen ikut berubah; log Sinkronisasi mencatat `sukses`.
6. Setelah lulus semua: ganti konfigurasi ke spreadsheet produksi (URL baru → Test Koneksi ulang).

### Keamanan
- Halaman & endpoint hanya untuk role **admin** (middleware `role:admin`); pengurus hanya melihat status/log hasil.
- Nilai sensitif (kunci JSON, token) dienkripsi saat disimpan; log sinkronisasi **tidak boleh** mencatat isi kunci/token.
- Upload kunci divalidasi: wajib JSON, maks 50 KB, memuat field `type=service_account` dan `client_email`.
- Konfigurasi runtime **seluruhnya di database**; `.env` hanya memuat konfigurasi inti aplikasi (database, `APP_KEY`, `APP_URL`) yang didefinisikan **sekali saat deploy** oleh petugas IT — bukan oleh pengguna aplikasi (admin/pengurus/pegawai tidak pernah membuka file server).

## Perubahan Skema
- `integration_settings`: `setting_key` VARCHAR(100) UNIQUE (kolom `key` tidak dipakai — reserved word MySQL), `value` TEXT NULL (terenkripsi), `timestamps`. Kunci yang dipakai: `google_enabled`, `google_service_account_json`, `google_spreadsheet_id`, `google_sheet_anggaran`, `google_sheet_realisasi`, `google_drive_enabled`, `google_drive_folder_id`, `google_poll_minutes`.
- `integration_logs`: `id`, `provider` ('google'), `task` ('sheets_sync'/'drive_upload'), `status` ('sukses','gagal'), `message` TEXT NULL, `duration_ms`, `ran_at`. Retensi 90 hari (purge otomatis oleh scheduler mingguan).

## Endpoint
| Method | Path | Akses | Keterangan |
|--------|------|-------|------------|
| GET | `/admin/integrasi/google` | admin | Halaman konfigurasi + log |
| POST | `/admin/integrasi/google` | admin | Simpan konfigurasi (termasuk upload kunci) |
| DELETE | `/admin/integrasi/google/key` | admin | Hapus kunci yang tersimpan |
| POST | `/admin/integrasi/google/test` | admin | Test koneksi bertahap |
| POST | `/admin/integrasi/google/sync` | admin | Jalankan sinkronisasi manual |

## Aturan Validasi
- URL spreadsheet: wajib saat Sheets aktif; format `https://docs.google.com/spreadsheets/d/{id}/...` → ID diekstrak, disimpan.
- Nama tab: wajib, maks 100 karakter, tanpa karakter kontrol.
- URL folder Drive: format `https://drive.google.com/drive/folders/{id}` (opsional, wajib bila Drive aktif).
- Kunci JSON: valid JSON `service_account`, maks 50 KB.
- Interval polling: salah satu dari 5/15/30/60 menit.

## Skenario Uji
1. Upload kunci valid → email service account tampil.
2. Paste URL spreadsheet → ID terekstrak; URL salah format → pesan error.
3. Test koneksi saat spreadsheet belum dibagikan → langkah 3 ❌ dengan pesan 403 yang jelas.
4. Setelah dibagikan → semua ✅ → aktifkan → "Sinkron Sekarang" sukses → log tercatat.
5. Ubah anggaran di Sheets → sync berikutnya menarik perubahan sesuai interval.
6. Drive aktif + folder valid → PDF generate terbaru muncul di folder Drive.
7. Integrasi dinonaktifkan → task sync dilewati, badge dashboard "nonaktif".
8. Pengurus membuka `/admin/integrasi/google` → 403.
9. Kunci dihapus → sync berikutnya gagal dengan pesan "kredensial belum dikonfigurasi" (tidak crash).
