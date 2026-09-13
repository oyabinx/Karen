# TASKLIST — Tindak Lanjut UAT 05–09 (2026-09-13)

Sumber: UAT/05-event-armada.md, 06-monitoring-laporan-dashboard.md,
07-responsive-mobile.md, 08-scheduler-otomatis-opsional.md,
09-admin-log-aktivitas.md.

Ringkasan temuan:
- UAT 05: A9 (baru) daftar armada per event; A10/B6 (revisi) batalkan
  event harus mengembalikan booking SUDAH DIGANTI ke unit asli.
- UAT 06: A7 (revisi) pagination monitoring + pemilih baris/halaman;
  C3 (revisi) dashboard pegawai tampil 2 kartu peminjaman aktif;
  K7 (revisi) maintenance selesai → auto-tandai selesai keluhan unit.
- UAT 07: semua ok.
- UAT 08: #3 belum sempat diuji user (menunggu pengganti lewat tempo).
- UAT 09: B1 bingung (salah baca langkah — "Log Uji 1" = nama contoh
  kendaraan, bukan membuka log); B/C belum dieksekusi.

## CP1 — UAT 06-C3: Kartu peminjaman aktif ganda di dashboard pegawai
- [x] Hapus kartu kecil "Peminjaman Aktif" di grid atas (duplikat panel
      besar bawahnya yang punya tombol); kartu kuota jadi lebar penuh.
- [x] Test dashboard pegawai tetap hijau.

## CP2 — UAT 06-A7: Pagination monitoring "Semua Peminjaman"
- [x] Pemilih jumlah baris per halaman: 10 / 20 / 50 / 100
      (default 20) — submit GET, query string filter terjaga.
- [x] Kontrol pagination selalu terlihat di bawah daftar + penanda
      "Menampilkan X–Y dari Z peminjaman" (agar tidak "hilang" saat
      hasil ≤ 1 halaman).
- [x] Validasi per_page (in:10,20,50,100).

## CP3 — UAT 06-K7: Maintenance selesai → keluhan unit otomatis selesai
- [x] Saat jadwal maintenance ditandai SELESAI, semua keluhan belum
      selesai pada unit itu otomatis ditandai selesai (keputusan:
      semua — sesuai permintaan user; didokumentasikan).
- [x] Flash message menyebut jumlah keluhan yang ikut diselesaikan.
- [x] Test: buat keluhan → jadwalkan via modal keluhan → tandai
      maintenance selesai → keluhan resolved di DB.

## CP4 — UAT 05-A9: Daftar armada per event (menjawab "mobil event X apa saja?")
- [x] Kartu event di halaman Event Armada bisa di-expand (klik) untuk
      menampilkan daftar unit armadanya (nama + plat), mobile-friendly.
- [x] Default ringkas: hanya jumlah armada (tampilan lama).

## CP5 — UAT 05-A10/B6: Batalkan event → pulihkan booking ke unit asli
- [x] Booking BELUM diganti → kembali Dipinjam di unit asli (sudah
      jalan — revertPendingForVehicle).
- [x] Booking SUDAH diganti (original_vehicle_id = armada event,
      overlap rentang event) → kembali ke unit asli BILA unit asli
      bebas pada rentang bookingnya; bila tidak bebas, tetap di
      pengganti (aman — tidak pernah menabrak).
- [x] Teks konfirmasi "Batalkan Event?" diubah (menyebut pemulihan).
- [x] Test: (a) diganti → batal event → balik ke unit asli;
      (b) unit asli sudah dipakai orang lain → tetap di pengganti;
      (c) belum diganti → balik dipinjam (regresi).

## CP6 — UAT 08-#3 + UAT 09: Verifikasi otomatis + klarifikasi langkah
- [x] Test autoReturn: booking menunggu_penggantian lewat tempo →
      Dibatalkan + chip waktu + kuota lepas (bila belum ada).
- [x] Audit coverage log aktivitas: B7b (batal mandiri → kolom
      status+cancelled_at), B8 (autoReturn → pelaku Sistem), C1
      (password tak pernah tercatat), C2 (isi kunci service account
      tak tercatat), C3 (deleted_at aktif/nonaktif) — tambah yang
      kurang.
- [x] Tulis ulang langkah UAT 09-B1..B4 lebih jelas di file UAT
      (jawab kebingungan: "Log Uji 1" = nama contoh kendaraan; log
      dilihat ADMIN, bukan pengurus).

## CP7 — Penutup
- [x] Docs: event_bidang.md (A9/A10), laporan.md (A7), dashboard.md
      (C3), manajemen_kendaraan.md / keluhan (K7), log_aktivitas.md
      bila ada tambahan perilaku.
- [x] Update status/catatan file UAT 05–09.
- [x] Build log baru, full suite hijau, commit + push per CP.
