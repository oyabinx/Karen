# Skenario 08 — Scheduler Otomatis (P3, opsional — perlu trik data)

Pengembalian otomatis berjalan 00:01 lewat cron; untuk UAT tanpa
menunggu tengah malam kita **buat data lewat tempo secara manual**
lewat tinker, lalu jalankan prosesnya langsung.

**Aktifkan Sail dulu**, lalu jalankan perintah berikut dari folder
proyek (semua satu baris):

```bash
newgrp docker
```
```bash
# 1. Login pegawai via aplikasi → buat 1 booking MULAI HARI INI
#    (agar lewat validasi), lalu "mundurkan" tanggalnya lewat tinker:
./vendor/bin/sail artisan tinker --execute='$b = App\Models\Booking::where("status","dipinjam")->latest("id")->first(); $b->update(["start_date"=>today()->subDays(3),"end_date"=>today()->subDay()]); print("booking #{$b->id} dibuat lewat tempo");'

# 2. Jalankan proses pengembalian otomatis (biasanya dikerjakan cron 00:01):
./vendor/bin/sail artisan tinker --execute='print(json_encode(app(App\Services\ReturnService::class)->autoReturn()));'

# 3. Verifikasi di aplikasi (login pegawai):
#    - Peminjaman Saya → booking berstatus Dikembalikan + badge
#      "Dikembalikan otomatis oleh sistem"
#    - mobil bisa dipinjam lagi oleh orang lain
```

| No | Pemeriksaan | Hasil Diharapkan | Status | Catatan |
|----|-------------|------------------|--------|---------|
| 1 | Booking lewat tempo tanpa tombol Selesai → autoReturn | Status **Dikembalikan** + badge otomatis + waktu kembali terisi | ⬜ | |
| 2 | Booking `end_date` HARI INI → autoReturn | **Tidak berubah** (masih Dipinjam — berlaku hingga 24:00) | ⬜ | |
| 3 | Booking **menunggu pengganti** lewat tempo → autoReturn | Status **Dibatalkan** + chip **"Dibatalkan {waktu}"** (waktu pembatalan oleh sistem) + kuota bidang lepas (user bisa booking lagi) | ⬜ | |
| 4 | Jalankan autoReturn dua kali | Tidak ada perubahan ganda (idempoten) | ⬜ | |
| 5 | Tidak ada keluhan otomatis tercipta | Daftar keluhan tidak bertambah | ⬜ | |
| 6 | Event lewat `end_date` (buat event kemarin via tinker) → jalankan `app(App\Services\EventService::class)->autoFinish()` | Event berstatus **Selesai**; armada bebas | ⬜ | |
| 7 | Dashboard admin → kartu Kesehatan Scheduler | Waktu "terakhir" auto-return/auto-finish terisi (setelah langkah di atas) | ⬜ | |

> Catatan: cron sungguhan (pemicu menit-per-menit) hanya dipasang
> di server produksi (Fase 10 / README). Di laptop, langkah 2–3
> menggantikan peran cron.
