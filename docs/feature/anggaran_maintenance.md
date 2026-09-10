# Fitur: Manajemen Anggaran Maintenance (Pengurus)

## Deskripsi
Anggaran maintenance kendaraan dibagi menjadi **4 pos** yang totalnya berbeda-beda **per mobil tergantung tahun pembuatannya**:

| Pos | Keterangan |
|-----|------------|
| `servis` | Anggaran servis |
| `suku_cadang` | Anggaran suku cadang |
| `ac` | Anggaran pemeliharaan AC |
| `pelumas` | Anggaran pelumas |

Alur: keluhan pengembalian muncul di dashboard pengurus → pengurus menjadwalkan maintenance → setelah perawatan selesai, pengurus menginput **rincian baris nota bengkel** (teks + nominal) dan memilahnya ke 4 pos → total tiap pos dihitung **otomatis dari jumlah rincian**, lalu dikalikan **koefisien pajak** (default 1,13; **dapat diatur admin** di menu Pengaturan Aplikasi) → sistem menggenerate **Bukti Pengeluaran Bendahara (lembar bend26)** dan **draft nota per pos** (1 nota bengkel dipecah menjadi 4 draft nota).

> **Koefisien pajak bersifat dinamis** *(skema baru UAT 04)*: nilai koefisien dibaca dari Pengaturan Aplikasi (rentang 1,00–2,00, default 1,13) — bukan lagi konstanta kode. Setiap nota menyimpan koefisien yang dipakai saat input (`koefisien_used`), sehingga **nota lama tidak ikut berubah** ketika admin mengubah kebijakan.

## Spesifikasi

### 1. Anggaran per Kendaraan
- Data kendaraan ditambah field **tahun pembuatan** (`year`).
- Pengurus menginput **total anggaran tiap pos per mobil** sekali (misalnya per tahun anggaran).
- **Alokasi tiap tahun INDEPENDEN** (mis. 2026 ≠ 2027): tersimpan per `(vehicle, pos, year)`; halaman anggaran memiliki **pemilih tahun** ( navigasi ◀/▶ atau isi kolom tahun) untuk melihat/mengelola tahun tertentu tanpa menimpa tahun lain.
- Sistem menampilkan sisa anggaran per pos: `total anggaran − realisasi (nilai nota × koefisien pajak)` — dihitung per tahun anggaran yang sedang dilihat.

### 2. Jadwal Maintenance dari Keluhan
- Keluhan yang masuk (dari form pengembalian) tampil di dashboard pengurus.
- Pengurus membuat jadwal maintenance dari keluhan tersebut (sudah termasuk fitur penggantian mobil bila menabrak booking — lihat [penggantian_mobil.md](penggantian_mobil.md)).
- Maintenance memiliki status: `terjadwal` → `selesai`.

### 3. Input Nota Bengkel
- Setelah perawatan selesai, pengurus membuka form **input nota** (dari kartu jadwal maintenance / menu Anggaran terpusat):
  - nama bengkel, nomor & tanggal nota (informatif);
  - **rincian baris per 4 pos** (servis / suku cadang / AC / pelumas): tiap baris berisi *deskripsi* (mis. "Ganti oli mesin") + *nominal*; baris dapat ditambah/dihapus.
- **Total tiap pos = akumulasi otomatis dari rincian** *(rev UAT 04)* — bukan input field manual. Form menampilkan subtotal (jumlah rincian) dan total "dikalikan koefisien pajak" secara live; **angka koefisien tidak ditampilkan** di form.
- Form **mobile-first** *(skema baru UAT 04)*: pos ditata vertikal di layar kecil, kontrol minimum 44px, nominal berpemisah ribuan otomatis, tombol + rincian lebar penuh.
- Sistem menyimpan rincian baris (`maintenance_cost_details`), nilai asli per pos (auto-sum), dan menghitung `nilai × koefisien` sebagai **realisasi anggaran**.
- **Atribusi tahun realisasi**: realisasi dihitung per tahun berdasarkan **tahun tanggal nota** (fallback: tahun tanggal mulai maintenance bila nota tanpa tanggal) — menentukan anggaran tahun mana yang dikurangi.
- Input nota bersifat **upsert**: identitas nota (bengkel/nomor/tanggal) yang tidak dikirim mempertahankan nilai lama; revisi rincian **mengganti** seluruh baris rincian lama — revisi nilai tidak menghapus identitas yang sudah tersimpan.

### 4. Generate Dokumen
Setelah input nota disimpan, sistem menggenerate (PDF, via library dompdf). **Master draft dokumen dikelola satu sistem di Karen** — folder `resources/draft_documents/` di dalam project berisi template draft (bend26, draft nota, kartu inventaris pemeliharaan kendaraan) yang dipakai `DocumentService` sebagai dasar generate; hasil generate tersimpan di `storage/app/documents/`. (Google Drive **tidak** dipakai sebagai tempat draft master — Google hanya untuk sinkronisasi data anggaran via Sheets API; arsip sekunder ke Drive dapat ditambahkan kemudian tanpa mengubah alur ini.)

Dokumen yang digenerate:

1. **Bukti Pengeluaran Bendahara (lembar bend26)** — berisi rincian 4 pos (nilai asli, pajak sesuai koefisien, nilai akhir), total, identitas mobil, nama bengkel, nomor maintenance, dan tanggal.
2. **Draft nota per pos** (maksimal 4 file) — tiap pos menjadi satu draft nota yang akan dibuat ulang oleh bengkel sesuai rincian tersebut; draft memuat rincian baris (deskripsi + nominal) bila tersedia.
3. **Kartu Inventaris Pemeliharaan Kendaraan** — rekap riwayat per mobil: seluruh maintenance (tanggal, keluhan asal, bengkel, rincian 4 pos × koefisien), akumulasi realisasi per pos vs anggaran, dan sisa anggaran. Digenerate per kendaraan kapan pun oleh pengurus.

Rumus per pos: `jumlah_rincian_pos × koefisien_pajak = nilai_bend26_pos`; total bend26 = jumlah keempatnya.

Dokumen tersimpan di server dan bisa diunduh ulang kapan pun; bila nota direvisi, dokumen di-generate ulang (versi lama diarsipkan).

### 4b. Menu Anggaran Terpusat & Realisasi Bulanan *(skema baru UAT 04)*
- **Menu "Anggaran" (terpusat)**: ringkasan seluruh armada per tahun anggaran (pemilih tahun) — anggaran vs realisasi vs sisa per pos tiap mobil, dengan pintasan atur anggaran per mobil dan generate kartu inventaris. Nota juga dapat diinput dari sini (tautan ke form nota maintenance selesai yang belum punya nota).
- **Menu "Realisasi Bulanan"** — tampilan **dua level** *(rev UAT 04)*:
  - **Level 1 (default): daftar mobil** yang punya realisasi (maintenance ber-nota) pada bulan terpilih — tiap kartu menampilkan nama, plat, jumlah maintenance, total realisasi (termasuk pajak), dan chip per pos bernilai; pemilih bulan 12 bulan terakhir.
  - **Level 2 (klik mobil)**: rincian per pos — subtotal sebelum pajak, total setelah koefisien, daftar nota per pos (bengkel, tanggal, nomor) dengan **rincian baris expandable** (deskripsi + nominal).
  - Ringkasan 4 pos seluruh armada (realisasi vs anggaran vs sisa) tetap tampil di atas pada kedua level.
  - **Ekspor CSV** (per bulan, mengikuti filter mobil bila ada): kolom tanggal, mobil, plat, bengkel, nota, pos, rincian, nilai, koefisien, total.
- Atribusi bulanan mengikuti tanggal nota (fallback tanggal mulai maintenance), konsisten dengan atribusi tahun.

### 5. Integrasi Google Sheets (final — satu-satunya mode)
Sistem berjalan di **intranet tanpa akses inbound dari internet**, sehingga Google Apps Script tidak dapat memanggil API Karen secara langsung. Integrasi dilakukan **satu arah keluar (outbound)** dari server Karen, yang telah dikonfirmasi memiliki akses outbound internet:

- Laravel membaca/menulis spreadsheet Google via **Google Sheets API v4 + Service Account**. Kredensial & URL spreadsheet dikonfigurasi **sepenuhnya lewat UI** di halaman admin Konfigurasi Integrasi Google (upload kunci JSON terenkripsi, test koneksi, log sync — lihat [integrasi_google.md](integrasi_google.md)); tidak ada kredensial Google di file server.
- Pengurus dapat menginput/mengolah data anggaran di Google Sheets; task terjadwal `budgets:sync-sheets` (interval dapat diatur admin, default 15 menit) menarik perubahan ke Karen dan mendorong realisasi terbaru keluar.
- Arsip sekunder PDF ke **folder Google Drive** dapat diaktifkan dari halaman konfigurasi yang sama (opsional).
- Kontrak data JSON tunggal (lihat bagian Kontrak JSON) dipakai pada payload sinkronisasi Sheets API.

> Mode alternatif ekspor/impor file JSON (fully offline) **dihapus** dari ruang lingkup — sinkronisasi Google Sheets adalah satu-satunya mekanisme integrasi.

## Perubahan Skema
- `vehicles` tambah `year SMALLINT` (tahun pembuatan).
- `vehicle_budgets`: `vehicle_id`, `post` ENUM('servis','suku_cadang','ac','pelumas'), `amount`, `year` (tahun anggaran) — UNIQUE(vehicle_id, post, year).
- `maintenances` tambah: `status` ENUM('terjadwal','selesai'), `workshop_name`, `nota_number`, `nota_date`.
- `maintenance_costs`: `maintenance_id`, `post`, `raw_amount` (auto-sum rincian), `taxed_amount` (raw × koefisien), `koefisien_used DECIMAL(4,2)` (koefisien saat input) — UNIQUE(maintenance_id, post).
- `maintenance_cost_details` *(skema baru UAT 04)*: `maintenance_cost_id`, `description` VARCHAR(255), `amount DECIMAL(12,2)` — baris rincian nota per pos.
- `generated_documents`: `maintenance_id` NULL (untuk kartu inventaris, diikat `vehicle_id`), `vehicle_id` NULL, `type` ENUM('bend26','draft_nota','kartu_inventaris'), `post` NULL (untuk draft_nota), `file_path`, `version`.
- `integration_settings`: key `koefisien_pajak` (rentang 1,00–2,00, default 1,13) — diatur admin via Pengaturan Aplikasi.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/pengurus/anggaran` | Menu anggaran terpusat seluruh armada (pemilih tahun) |
| GET/PUT | `/pengurus/vehicles/{vehicle}/budgets` | Lihat/atur total anggaran 4 pos per mobil |
| GET | `/pengurus/realisasi-bulanan` | Realisasi bulanan: level 1 list mobil; `?vehicle=X` level 2 rincian; `&export=1` CSV |
| POST | `/pengurus/maintenances` | Jadwalkan maintenance (dari keluhan) |
| PATCH | `/pengurus/maintenances/{m}/finish` | Tandai perawatan selesai |
| GET/PUT | `/pengurus/maintenances/{m}/costs` | Form input nota (rincian baris per 4 pos) |
| POST | `/pengurus/maintenances/{m}/generate` | Generate bend26 + draft nota per pos |
| POST | `/pengurus/vehicles/{vehicle}/generate-kartu-inventaris` | Generate kartu inventaris pemeliharaan kendaraan |
| GET | `/pengurus/documents/{doc}/download` | Unduh dokumen |
| (task) | `budgets:sync-sheets` | Sinkron Google Sheets (interval via UI admin, default 15 menit) |

## Kontrak JSON (payload sinkronisasi Google Sheets)
```json
{
  "vehicle": { "id": 1, "plate_number": "B 1234 XYZ", "year": 2020 },
  "budgets": [
    { "post": "servis", "amount": 5000000 },
    { "post": "suku_cadang", "amount": 8000000 },
    { "post": "ac", "amount": 2000000 },
    { "post": "pelumas", "amount": 1500000 }
  ],
  "realizations": [
    {
      "maintenance_id": 7,
      "workshop_name": "Bengkel Jaya",
      "nota_number": "INV/2026/081",
      "costs": [
        { "post": "servis", "raw_amount": 500000, "taxed_amount": 565000 },
        { "post": "pelumas", "raw_amount": 300000, "taxed_amount": 339000 }
      ]
    }
  ]
}
```

## Aturan Validasi
- Tahun pembuatan: wajib, 1980–tahun berjalan+1.
- Anggaran pos: wajib angka ≥ 0.
- Input nota: minimal **satu baris rincian bernilai > 0** di seluruh nota (nota kosong ditolak); deskripsi baris opsional (otomatis "(tanpa deskripsi)"); nominal dapat berupa angka atau teks berpemisah ribuan ("500.000").
- Koefisien pajak (admin): angka 1,00–2,00 (default 1,13); perubahan hanya berlaku untuk nota berikutnya.
- Realisasi kumulatif per pos **boleh melebihi anggaran** — sistem menampilkan peringatan (pos merah), tidak memblokir.

## Skenario Uji
1. Set anggaran 4 pos untuk mobil 2020 → beda dengan mobil 2015.
2. Keluhan → jadwalkan maintenance → tandai selesai → input nota: servis 2 rincian (300.000 + 200.000), pelumas 1 rincian 300.000.
3. Total otomatis: servis 500.000 → realisasi 565.000; pelumas 300.000 → 339.000 (×1,13) — tanpa menginput total manual.
4. Generate → bend26 menampilkan 565.000 + 339.000, total 904.000; tersimpan & bisa diunduh.
5. Draft nota terbit hanya untuk pos bernilai > 0 (2 dari 4) dan memuat rincian baris.
6. Realisasi Bulanan: bulan nota → list mobil → klik → rincian per pos + expandable rincian baris; ekspor CSV sesuai filter.
7. Admin ubah koefisien ke 1,15 → nota baru memakai 1,15; nota lama tetap 1,13 (koefisien_used).
8. Generate kartu inventaris mobil → memuat seluruh riwayat maintenance + akumulasi per pos vs anggaran.
9. Realisasi melebihi anggaran → badge peringatan merah di sisa anggaran.
10. Ubah anggaran di Google Sheets → task `budgets:sync-sheets` menarik perubahan ke Karen dalam ≤ 15 menit.
