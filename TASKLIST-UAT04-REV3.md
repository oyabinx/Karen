# TASKLIST — UAT 04 Rev-3 (checkpoint aman quota)

Sumber: review user UAT/04 (baris verifikasi revisi).
Prinsip: eksekusi berurutan; centang [x] setelah selesai —
jika sesi terputus, lanjut dari checkpoint pertama yang belum [x].

## RINGKASAN TEMUAN
Hanya 3 revisi baru (baris b lainnya ok):
1. B4b → tombol "Selesai Edit" saat mode edit nota
2. B6b → spasi atas/bawah "Mengetahui dan menyetujui" di bend26 dihilangkan
3. B6c → PPN per pos (No. Rek + PPN + PPh per pos; pelumas default 0)

---

## CHECKPOINT 1 — [x] R1: Tombol "Selesai Edit" (B4b)
- [x] `costs.blade.php`: tombol submit label bersyarat —
      `$hasNota = $costs->max('raw_amount') > 0` →
      true: **"Selesai Edit"**; false: "Simpan Nota & Generate Dokumen"
- [x] Judul h1 ikut menyesuaikan: "Edit Nota Bengkel" vs "Input Nota Bengkel"

## CHECKPOINT 2 — [x] R2: Spasi bend26 (B6b)
- [x] `draft_documents/bend26.blade.php`: `.ttd` margin-top 18px → 4px;
      hapus `<br><br>` setelah "Mengetahui dan menyetujui"
      (satu template dipakai semua 4 pos — otomatis apply)

## CHECKPOINT 3 — [x] R3: PPN per pos (B6c)
- [x] `Bend26Identity::defaults()`: `ppn_percent` scalar 11 → array
      `{servis:11, suku_cadang:11, ac:11, pelumas:0}`
- [x] `AppSettings::bend26Identity()`: normalisasi legacy scalar → array
      (scalar lama diterapkan ke 3 pos, pelumas 0)
- [x] `Bend26Identity::pajak()`: baca PPN per pos (DPP=total bila PPN 0;
      pelumas: PPN 0, PPh tetap DPP×1,5%)
- [x] `admin/settings/index.blade.php`: hapus field PPN global;
      grid per pos: **No. Rek | PPN % | PPh %** (4 kelompok)
- [x] `AppSettingController`: validasi `b26.ppn_percent.*` (0–100)
- [x] Test: pajak() servis (PPN 11% DPP) vs pelumas (PPN 0, PPh jalan)

## CHECKPOINT 4 — [x] Verifikasi + Docs + Commit
- [x] `php artisan test` penuh hijau
- [x] Render bend26 servis & pelumas → cek spasi & PPN pelumas = 0
- [x] Docs: anggaran_maintenance.md (PPN per pos), product.md aturan 28
- [x] UAT/04: baris B4c, B6d, B6c2 (verifikasi baru, ⬜)
- [x] Build log + commit + push
