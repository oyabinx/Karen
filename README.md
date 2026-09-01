# Karen — Sistem Peminjaman Kendaraan Dinas

Aplikasi web intranet self-service untuk peminjaman mobil dinas kantor: pegawai mencari mobil tersedia pada rentang tanggal, memesan dengan alamat & keperluan (maks **3 hari**, termasuk Sabtu–Minggu), dan menyelesaikan peminjaman dengan tombol **"Selesai"** + keluhan opsional. Pengelolaan kendaraan, jadwal maintenance, **anggaran 4 pos (×1,13) + dokumen bend26/draft nota/kartu inventaris**, **event armada bidang di atas kuota**, dan **sinkronisasi Google Sheets** tersedia untuk pengurus/admin.

Dokumentasi perencanaan lengkap: folder [`docs/`](docs/) — log implementasi per fase: folder [`build_logs/`](build_logs/).

## Kebutuhan

- **Docker** + Docker Compose (pengembangan memakai [Laravel Sail](https://laravel.com/docs/sail))
- Git

## Setup Pengembangan (laptop, via Sail)

```bash
git clone git@github.com:oyabinx/Karen.git && cd Karen
cp .env.example .env          # sesuaikan bila perlu (default sudah untuk Sail)
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD":/app -w /app composer install
./vendor/bin/sail up -d       # tunggu mysql healthy: ./vendor/bin/sail ps
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm install && ./vendor/bin/sail npm run dev
# buka http://localhost
```

Akun bawaan (seeder, **ganti password setelah login pertama**):

| Email | Password | Role |
|-------|----------|------|
| `admin@karen.test` | `password` | admin |
| `pengurus@karen.test` | `password` | pengurus |
| `pegawai@karen.test` | `password` | pegawai |

Perintah Sail harian: `sail up -d`, `sail down`, `sail ps`, `sail logs -f`, `sail artisan …`, `sail composer …`, `sail npm …`, `sail test`.

## ⚠️ Scheduler WAJIB dipasang (satu kali oleh petugas IT)

Aplikasi tidak bisa membangunkan dirinya sendiri (intranet tanpa trafik tengah malam). Tiga tugas terjadwal — **pengembalian otomatis peminjaman (00:01)**, penutupan event armada (00:02), dan **sinkronisasi Google Sheets** (tiap 5 menit, interval dalamnya diatur admin) — bergantung pada pemicu eksternal:

```bash
# Linux (cron) — jalankan sekali:
(umask 077 ; crontab -l 2>/dev/null; echo "* * * * * cd /path/karen && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

Alternatif Linux tanpa cron: systemd unit long-running `php artisan schedule:work` (`Restart=always`). Verifikasi: `php artisan schedule:list`, lalu cek `storage/logs/laravel.log` setelah tengah malam. Detail: [`docs/tech.md` §7](docs/tech.md).

## Testing

```bash
./vendor/bin/sail test
```

## Integrasi Google (produksi)

Outbound saja (Google Sheets API + Service Account) — tidak ada akses inbound dari internet. Seluruh konfigurasi runtime via UI admin (**Admin → Integrasi Google**): upload kunci JSON (tersimpan terenkripsi di database), URL spreadsheet, tab anggaran/realisasi, arsip Drive opsional, interval polling, **Test Koneksi**, dan log sinkronisasi. Pastikan firewall server mengizinkan outbound HTTPS ke `oauth2.googleapis.com` dan `sheets.googleapis.com`. Panduan lengkap: [`docs/feature/integrasi_google.md`](docs/feature/integrasi_google.md).

## Deployment

Lihat Fase 10 pada [`docs/taskplan.md`](docs/taskplan.md): alur laptop (Sail) → GitHub → server intranet Ubuntu (Nginx + PHP-FPM + MySQL), termasuk checklist integrasi Google di produksi.
