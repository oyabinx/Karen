# Draft Documents — Master Template Dokumen

Folder ini berisi **master draft dokumen** Karen yang dirender menjadi PDF
oleh `DocumentService` (barryvdh/laravel-dompdf). Lihat
[docs/feature/anggaran_maintenance.md](../docs/feature/anggaran_maintenance.md).

| File | Jenis | Kapan digenerate |
|------|-------|------------------|
| `bend26.blade.php` | Bukti Pengeluaran Bendahara (lembar bend26) | Setelah input nota bengkel (4 pos) |
| `draft_nota.blade.php` | Draft nota per pos (maks 4 per maintenance) | Setelah input nota — hanya pos bernilai > 0 |
| `kartu_inventaris.blade.php` | Kartu inventaris pemeliharaan kendaraan | Kapan pun oleh pengurus, per kendaraan per tahun |

Hasil PDF tersimpan di `storage/app/documents/` (versioned, versi lama
diarsipkan) dan dicatat pada tabel `generated_documents`.

## Variabel yang tersedia per template

### bend26.blade.php
- `$maintenance` — App\Models\Maintenance (workshop_name, nota_number, nota_date, start_date, end_date, note)
- `$vehicle` — App\Models\Vehicle (name, plate_number, year)
- `$costs` — koleksi MaintenanceCost (post, raw_amount, taxed_amount)
- `$totalRaw`, `$totalTaxed` — total keseluruhan
- `$koefisien` — 1.13

### draft_nota.blade.php
- `$maintenance`, `$vehicle`, `$koefisien`
- `$cost` — MaintenanceCost pos terkait

### kartu_inventaris.blade.php
- `$vehicle`, `$year`
- `$maintenances` — riwayat maintenance tahun tsb (with costs)
- `$summary` — [post => [anggaran, realisasi, sisa]]

Helper format Rupiah: `rp($nilai)` (didefinisikan di setiap template).
