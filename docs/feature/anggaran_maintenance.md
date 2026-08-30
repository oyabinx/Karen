# Fitur: Manajemen Anggaran Maintenance (Pengurus)

## Deskripsi
Anggaran maintenance kendaraan dibagi menjadi **4 pos** yang totalnya berbeda-beda **per mobil tergantung tahun pembuatannya**:

| Pos | Keterangan |
|-----|------------|
| `servis` | Anggaran servis |
| `suku_cadang` | Anggaran suku cadang |
| `ac` | Anggaran pemeliharaan AC |
| `pelumas` | Anggaran pelumas |

Alur: keluhan pengembalian muncul di dashboard pengurus → pengurus menjadwalkan maintenance → setelah perawatan selesai, pengurus menginput rincian nota bengkel dan memilah ke 4 pos → sistem mengalikan tiap pos dengan **koefisien 1,13 (pajak)** → sistem menggenerate **Bukti Pengeluaran Bendahara (lembar bend26)** dan **draft nota per pos** (1 nota bengkel dipecah menjadi 4 draft nota).

## Spesifikasi

### 1. Anggaran per Kendaraan
- Data kendaraan ditambah field **tahun pembuatan** (`year`).
- Pengurus menginput **total anggaran tiap pos per mobil** sekali (misalnya per tahun anggaran).
- Sistem menampilkan sisa anggaran per pos: `total anggaran − realisasi (nilai nota × 1,13)`.

### 2. Jadwal Maintenance dari Keluhan
- Keluhan yang masuk (dari form pengembalian) tampil di dashboard pengurus.
- Pengurus membuat jadwal maintenance dari keluhan tersebut (sudah termasuk fitur penggantian mobil bila menabrak booking — lihat [penggantian_mobil.md](penggantian_mobil.md)).
- Maintenance memiliki status: `terjadwal` → `selesai`.

### 3. Input Nota Bengkel
- Setelah perawatan selesai, pengurus membuka halaman maintenance → form **input nota**:
  - nama bengkel, nomor & tanggal nota (informatif);
  - rincian nilai nota **dipilah ke 4 pos** (servis / suku cadang / AC / pelumas) — boleh sebagian pos bernilai 0.
- Sistem menyimpan nilai asli tiap pos dan menghitung `nilai × 1,13` sebagai **realisasi anggaran**.

### 4. Generate Dokumen
Setelah input nota disimpan, sistem menggenerate (PDF, via library dompdf). **Master draft dokumen dikelola satu sistem di Karen** — folder `resources/draft_documents/` di dalam project berisi template draft (bend26, draft nota, kartu inventaris pemeliharaan kendaraan) yang dipakai `DocumentService` sebagai dasar generate; hasil generate tersimpan di `storage/app/documents/`. (Google Drive **tidak** dipakai sebagai tempat draft master — Google hanya untuk sinkronisasi data anggaran via Sheets API; arsip sekunder ke Drive dapat ditambahkan kemudian tanpa mengubah alur ini.)

Dokumen yang digenerate:

1. **Bukti Pengeluaran Bendahara (lembar bend26)** — berisi rincian 4 pos (nilai asli, pajak 13%, nilai akhir), total, identitas mobil, nama bengkel, nomor maintenance, dan tanggal.
2. **Draft nota per pos** (maksimal 4 file) — tiap pos menjadi satu draft nota yang akan dibuat ulang oleh bengkel sesuai rincian tersebut.
3. **Kartu Inventaris Pemeliharaan Kendaraan** — rekap riwayat per mobil: seluruh maintenance (tanggal, keluhan asal, bengkel, rincian 4 pos × 1,13), akumulasi realisasi per pos vs anggaran, dan sisa anggaran. Digenerate per kendaraan kapan pun oleh pengurus.

Rumus per pos: `nilai_nota_pos × 1,13 = nilai_bend26_pos`; total bend26 = jumlah keempatnya.

Dokumen tersimpan di server dan bisa diunduh ulang kapan pun; bila nota direvisi, dokumen di-generate ulang (versi lama diarsipkan).

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
- `maintenance_costs`: `maintenance_id`, `post`, `raw_amount` (nilai nota), `taxed_amount` (raw × 1,13) — UNIQUE(maintenance_id, post).
- `generated_documents`: `maintenance_id` NULL (untuk kartu inventaris, diikat `vehicle_id`), `vehicle_id` NULL, `type` ENUM('bend26','draft_nota','kartu_inventaris'), `post` NULL (untuk draft_nota), `file_path`, `version`.

## Endpoint
| Method | Path | Keterangan |
|--------|------|------------|
| GET/PUT | `/pengurus/vehicles/{vehicle}/budgets` | Lihat/atur total anggaran 4 pos per mobil |
| POST | `/pengurus/maintenances` | Jadwalkan maintenance (dari keluhan) |
| PATCH | `/pengurus/maintenances/{m}/finish` | Tandai perawatan selesai |
| GET/PUT | `/pengurus/maintenances/{m}/costs` | Form input & pilah nota ke 4 pos |
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
- Input nota: minimal satu pos > 0; nilai ≥ 0.
- Realisasi kumulatif per pos **boleh melebihi anggaran** — sistem menampilkan peringatan (pos merah), tidak memblokir.

## Skenario Uji
1. Set anggaran 4 pos untuk mobil 2020 → beda dengan mobil 2015.
2. Keluhan → jadwalkan maintenance → tandai selesai → input nota Rp500.000 servis + Rp300.000 pelumas.
3. Generate → bend26 menampilkan 565.000 + 339.000, total 904.000; tersimpan & bisa diunduh.
4. Draft nota terbit hanya untuk pos bernilai > 0 (2 dari 4).
5. Generate kartu inventaris mobil → memuat seluruh riwayat maintenance + akumulasi per pos vs anggaran.
6. Realisasi melebihi anggaran → badge peringatan merah di sisa anggaran.
7. Ubah anggaran di Google Sheets → task `budgets:sync-sheets` menarik perubahan ke Karen dalam ≤ 15 menit.
