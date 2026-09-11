# TASKLIST — Tindak Lanjut UAT 04 Rev-2 (Nota, Bend26 Bulanan, Kartu Pemeliharaan, UI)

Sumber: UAT/04 ulangan user (rev B3, B4, B6, B7, B9, B10, B11, B12,
D1, D2/E2) + file contoh bend26 (docs/Bend26/*.pdf).
Status: **SELURUHNYA SELESAI DIEKSEKUSI** — 239 test hijau.

---

## KELOMPOK A — Alur Input/Edit Nota + Urutan (B4, B9)
- [x] A1. Tombol kartu maintenance: **Input Nota** (hijau) bila belum
  ada nota → **Edit Nota** (oranye) bila sudah ada cost raw>0
  (controller load `costs`, view cek `$m->costs->max('raw_amount') > 0`)
- [x] A2. Urutan list maintenance **terbaru di atas**
  (`orderByDesc('start_date')->orderByDesc('id')`, kedua tab)
- [x] A3. Pesan sukses dibedakan mode input pertama vs edit

## KELOMPOK B — Dokumen Timpa di Tempat, Tanpa Versi (B4, B11)
- [x] B1. Migrasi: `regenerated_at` + `period` di generated_documents;
  enum `kartu_inventaris` → `kartu_pemeliharaan` (+update data lama)
- [x] B2. `DocumentService::store()`: kunci sama → **timpa file &
  record** (versi selalu 1); regenerasi mengisi `regenerated_at`;
  `latestDocuments()` tanpa dedup versi (order by updated_at)
- [x] B3. Menu Dokumen: badge **"Diperbarui {d M Y H:i}"** amber;
  label bend26 + pos + periode; tanpa tombol kartu
- [x] B4. Endpoint `maintenances.generate` (regenerate) + method
  controller DIHAPUS; logika versi dihapus dari semua pesan/view
- [x] B5. Bersih record bend26 lama (era per-maintenance) di DB dev

## KELOMPOK C — Bend26 Bulanan 4 Jenis (B6 — format file contoh)
- [x] C1. `DocumentService::bend26Bulanan(year, month, ?post)`:
  agregasi seluruh nota bulan tsb per pos (atribusi nota_date
  fallback start_date — konsisten Realisasi Bulanan); hanya pos
  bernilai; `period` YYYY-MM
- [x] C2. Template `bend26.blade.php` rewrite sesuai contoh BKPN:
  judul center; Terima dari / Uang sebesar / dengan huruf (terbilang
  NumberFormatter id_ID, rupiah utuh); Yaitu untuk pembayaran
  (Belanja {pos} + daftar plat + Sub Kegiatan + Kegiatan + Bulan +
  Nota terlampir); Terbilang; kota+bulan kanan; 3 kolom ttd
  (Pengguna Anggaran / Bendahara / Yang menerima + NIP); strip bawah
  3 kolom bergaris (Barang diterima PPTK | PPN/PPh/Jml | No. Rek +
  Kode Kegiatan + TA)
- [x] C3. PPN/PPh sesuai contoh: DPP = total/(1+PPN%), PPN = DPP×11%
  default, PPh = DPP × tarif pos (servis/AC 2%, suku cadang/pelumas
  1,5%) — dibulatkan rupiah utuh, Jml = PPN+PPh
- [x] C4. `Bend26Identity` (app/Support): defaults dari file contoh
  (instansi, kota, 4 pejabat+NIP, kode kegiatan, No. Rek servis &
  suku cadang; pelumas/AC kosong diisi admin) + pajak() + terbilang()
- [x] C5. Identitas dikonfigurasi admin: `AppSettings::bend26Identity`
  (JSON) + seksi "Identitas Dokumen Bend26 (lanjutan)" di halaman
  Pengaturan Aplikasi
- [x] C6. Tombol generate: **Realisasi Bulanan** header "Generate
  Bend26 {bulan}" (semua pos) + per-pos "Bend26 pos ini" (Level 2);
  endpoint POST `/pengurus/documents/bend26-bulanan`; bulan kosong →
  peringatan, tidak membuat dokumen
- [x] C7. `generateForMaintenance` TIDAK lagi membuat bend26 —
  hanya draft nota

## KELOMPOK D — Draft Nota Rinci (B7)
- [x] D1. Template draft_nota: **1 baris per rincian** (deskripsi +
  nominal asli) + footer JUMLAH = total asli pos; fallback bila tanpa
  rincian (nota lama)
- [x] D2. `draftNota` load `costs.details`

## KELOMPOK E — Kartu Pemeliharaan Kendaraan (B10)
- [x] E1. Rename (enum + konstanta + label + template baru
  `kartu_pemeliharaan.blade.php`)
- [x] E2. Format sesuai user: judul center (judul / Tahun Anggaran /
  nama / plat); tabel **Nomor | Tanggal | Jenis Perbaikan | Rincian
  Pemeliharaan satu cell (baris rincian + bengkel) | Biaya (setelah
  koefisien)** per maintenance per pos; terbaru di atas
- [x] E3. Tombol generate pindah ke **menu Laporan** (pilih mobil +
  tahun); dihapus dari Dokumen & anggaran kendaraan; route rename
  `generate-kartu-pemeliharaan`

## KELOMPOK F — UI Kecil (B3, B12, D1, D2/E2)
- [x] F1. B3 **diverifikasi browser**: ketik 1000000 → "1.000.000"
  live + subtotal/total terhitung — kode sudah benar (user kemungkinan
  menguji build lama); baris B3b ditambahkan di UAT
- [x] F2. B12: padding bawah mobile `pb-28` → **`pb-36`** (144px vs
  bar 56px) — diverifikasi visual viewport 390px
- [x] F3. D1: Realisasi Bulanan list mobil **descending** (lastDate
  nota/maintenance) + nota terbaru dulu dalam tiap pos (drill-down)
- [x] F4. D2/E2: Pengaturan Aplikasi **satu tombol "Simpan
  Pengaturan" paling bawah** (menghapus 2 tombol lama)

## KELOMPOK G — Test, Docs, UAT, Build, Commit
- [x] G1. Test dirombak: bend26 tidak otomatis (draft-only saat simpan
  nota); **edit = timpa di tempat** (record tidak bertambah,
  regenerated_at terisi); **bend26 bulanan** (period, 1 per pos
  bernilai, regenerasi = timpa, bulan kosong = warning); kartu
  pemeliharaan via route Laporan; label dokumen baru
- [x] G2. Docs: anggaran_maintenance.md (§4 rewrite + 4b + skema +
  endpoint + skenario), laporan.md (kartu pemeliharaan), product.md
  (aturan 27b rev + 28/29/30 baru)
- [x] G3. UAT/04: baris verifikasi B3b, B4b, B6b-c, B7b, B9b, B10b,
  B11b, B12b, D1b, E2b; harapan B4/B6/B9/B10/B11 diperbarui sesuai
  skema baru
- [x] G4. `TASKLIST-UAT04.md` (file ini) + build log + suite hijau
  (239) + `npm run build` + commit + push

---

**Catatan penting desain (dari file contoh)**: bend26 asli kantor
adalah **agregat bulanan per pos multi-mobil** — bukan per
maintenance. Karen mengikuti: bend26 digenerate on demand per bulan;
file "Contoh Bend26 Servis AC.pdf" ternyata lembar kerja SPJ
(rincian per mobil) = padanan rincian nota Karen; isi file bernama
"Pelumas" & "Suku Cadang" tertukar label (layout sama, No. Rek
berbeda) — tidak berdampak. No. Rek Pelumas & Servis AC tidak ada di
contoh → dikosongkan, diisi admin via Pengaturan Aplikasi.
