# Karen — Product Overview

## 1. Latar Belakang

Karen adalah aplikasi web internal kantor untuk mengelola **peminjaman kendaraan dinas (mobil)** secara **self-service**. Pegawai dapat meminjam mobil sendiri tanpa proses administrasi manual: cukup membuka aplikasi, memilih tanggal, memilih mobil yang tersedia, dan mengajukan peminjaman.

## 2. Visi Produk

- Menghilangkan proses manual peminjaman kendaraan (form kertas / koordinasi via telepon).
- Transparansi ketersediaan kendaraan secara real-time.
- Riwayat penggunaan kendaraan tercatat rapi untuk kebutuhan monitoring dan laporan.
- Keluhan terhadap unit kendaraan terdokumentasi dan dapat ditindaklanjuti.

## 3. Profil Pengguna (Role)

Sistem memiliki **3 role**, masing-masing dengan dashboard tersendiri:

### 3.1 Admin
- Mengelola data **pengguna** (tambah, ubah, nonaktifkan, atur role).
- Mengelola **struktur organisasi**: 5 bidang yang dibagi menjadi beberapa seksi.
- Membuat **event armada bidang** (pemakaian banyak mobil sekaligus di atas kuota — bersama pengurus).
- **Mengonfigurasi integrasi Google** (upload kunci service account, URL spreadsheet anggaran & folder Drive, test koneksi, log sinkronisasi) → [integrasi_google.md](feature/integrasi_google.md).
- Mengelola profil pribadi.

### 3.2 Pengurus
- Mengelola **data kendaraan** (CRUD mobil, foto, plat nomor, kapasitas).
- Mengatur status kendaraan: **bisa dipinjam / tidak bisa dipinjam**.
- Mengatur **jadwal maintenance** kendaraan (kendaraan dalam masa maintenance tidak muncul sebagai tersedia).
- **Mengatur penggantian mobil**: bila mobil yang dijadwalkan maintenance ternyata memiliki booking, sistem mencarikan mobil pengganti yang tersedia dan **pengurus yang mengonfirmasi** pilihan penggantinya.
- **Monitoring** semua peminjaman yang sedang berjalan dan riwayatnya.
- Melihat dan menindaklanjuti **keluhan** unit dari pegawai.
- **Mengelola anggaran maintenance**: 4 pos anggaran per mobil (servis, suku cadang, pemeliharaan AC, pelumas — besaran berbeda per mobil sesuai tahun pembuatan), input nota bengkel dipilah per pos, pengalian koefisien pajak 1,13, dan generate dokumen **bend26** + draft nota per pos.
- Melihat **laporan** penggunaan kendaraan (dengan export).
- **Meminjam mobil seperti pegawai** — sejatinya pengurus adalah user pegawai juga (memiliki fitur cari mobil, booking, pengembalian, dan terkena kuota bidang tempat seksi pengurus terdaftar).
- Membuat **event armada bidang** (pemakaian banyak mobil sekaligus di atas kuota — bersama admin).
- Mengelola profil pribadi.

### 3.3 Pegawai
- **Mencari mobil tersedia** pada rentang tanggal tertentu.
- **Membuat peminjaman** (mengisi alamat tujuan dan keperluan).
- **Mengembalikan** kendaraan (dengan form keluhan opsional).
- Melihat riwayat peminjaman pribadi.
- Mengelola profil pribadi.

## 4. Alur Bisnis Utama

### 4.1 Alur Peminjaman
1. Pegawai login lalu memilih **tanggal mulai** dan **tanggal selesai** peminjaman.
2. Sistem menampilkan **daftar mobil yang tersedia** (tidak sedang dipinjam dan tidak sedang maintenance) pada rentang tanggal tersebut.
3. Pegawai memilih mobil, lalu mengisi **alamat tujuan** dan **keperluan**.
4. Sistem menyimpan peminjaman dengan status `dipinjam` — **langsung terkonfirmasi tanpa approval** (full self-service).
5. Mobil otomatis dianggap tidak tersedia bagi pegawai lain pada rentang tanggal tersebut.

### 4.2 Alur Pengembalian Manual
1. Setelah selesai menggunakan mobil, pegawai membuka aplikasi dan menekan tombol **"Selesai"** pada peminjaman aktifnya.
2. Sistem membuka **pop-up** dengan **textbox keluhan opsional** (*"Apakah ada keluhan terkait unit yang dipinjam?"*) — boleh dikosongkan.
3. Setelah submit, status peminjaman berubah menjadi `dikembalikan` dan mobil **segera tersedia** untuk dipinjam.
4. Bila keluhan diisi, keluhan **hanya tercatat** pada daftar Keluhan Unit pengurus untuk ditindaklanjuti manual — mobil **tidak otomatis** disisihkan/dijadwalkan maintenance; bila perlu perbaikan, pengurus membuat jadwal maintenance secara manual (alur 4.1 penggantian berlaku bila menabrak booking).

### 4.3 Alur Pengembalian Otomatis
1. Jika masa pinjam telah berakhir (melewati `end_date`) dan pegawai **tidak** menekan tombol kembalikan, sistem secara otomatis:
   - Mengubah status peminjaman menjadi `dikembalikan` dengan penanda `otomatis`.
   - Mengembalikan status mobil menjadi **tersedia**.
2. Booking `menunggu_penggantian` (mobil sedang diproses penggantian karena maintenance) yang lewat jatuh tempo tanpa keputusan pengurus otomatis **dibatalkan** — jatah kuota bidang dilepas.
3. Dijalankan oleh scheduler setiap hari pukul 00:01.

## 5. Aturan dan Pembatasan Bisnis

| No | Aturan |
|----|--------|
| 1 | Login menggunakan **email dan password**. |
| 2 | Setiap user wajib memiliki **nomor HP** pada profil (wajib diisi saat mengubah profil). |
| 3 | **Maksimal peminjaman 3 hari**, termasuk hari Sabtu dan Minggu. |
| 4 | Durasi dihitung dalam **hari penuh: pukul 00:00 sampai 24:00** — tidak ada satuan jam. |
| 5 | Jika peminjaman dilakukan pada hari yang sama dengan tanggal sekarang (hari-H), peminjaman tetap dihitung **dimulai pukul 00:00 hari itu** (bukan dari jam pengajuan). |
| 6 | Mobil tidak dapat dipinjam pada rentang tanggal yang **overlap** dengan peminjaman lain, **jadwal maintenance**, atau saat mobil berstatus tidak bisa dipinjam. |
| 7 | Setiap peminjam — **pegawai maupun pengurus** — hanya dapat memiliki **satu peminjaman aktif** pada satu waktu. |
| 8 | Pengembalian otomatis dijalankan jika peminjaman lewat jatuh tempo tanpa pengembalian manual. |
| 9 | Kantor memiliki **5 bidang**, masing-masing dibagi menjadi **beberapa seksi** — dikelola oleh admin. |
| 10 | Setiap bidang memiliki **jatah maksimal mobil yang dipinjam bersamaan**: default **2 mobil**, dengan **satu bidang khusus maksimal 3 mobil** — kuota dikonfigurasi oleh admin. Booking mendatang yang sudah terkonfirmasi turut mengunci jatah. |
| 11 | Pengurus dapat **membuat peminjaman mobil** seperti pegawai — selama kuota bidang masih tersisa — dan peminjamannya dihitung pada kuota bidang tempat **seksi** pengurus terdaftar (seksi wajib diisi untuk pegawai dan pengurus). |
| 12 | Mobil yang dijadwalkan **maintenance** padahal sudah memiliki booking: sistem mencarikan **mobil pengganti yang tersedia** dan pengurus mengonfirmasi penggantinya; bila tidak ada pengganti, booking dibatalkan dan jatah kuota dilepas. |
| 12b | Keluhan pada saat pengembalian bersifat **catatan saja** — mobil tetap bisa dipinjam dan tidak otomatis menjadi maintenance/`perlu_diperiksa`; kondisi `perlu_diperiksa` hanya diatur **manual** oleh pengurus. |
| 13 | Anggaran maintenance terbagi **4 pos** (servis, suku cadang, AC, pelumas) dengan total **berbeda per mobil sesuai tahun pembuatan**, dikelola pengurus. |
| 14 | Realisasi nota bengkel dipilah pengurus ke 4 pos, dikalikan **koefisien 1,13** (pajak), dan menghasilkan dokumen **bukti pengeluaran bendahara (bend26)** serta **draft nota per pos** (1 nota bengkel dipecah menjadi maksimal 4 draft). |
| 15 | Aplikasi berjalan di **intranet**; pertukaran data anggaran dengan Google dilakukan via **sinkronisasi Google Sheets API (outbound dari server)** — tidak ada akses inbound dari internet. |
| 16 | **Event armada bidang** dapat dibuat hanya oleh **admin dan pengurus**: satu bidang memakai banyak mobil sekaligus untuk satu event, **bebas dari kuota bidang**. Durasi event **fleksibel dan boleh melebihi 3 hari** untuk event khusus — batas 3 hari hanya berlaku untuk peminjaman biasa pegawai/pengurus. |
| 17 | Mobil untuk event dipilih dari unit yang layak (bisa dipinjam, kondisi baik, tanpa maintenance, tanpa event lain). Mobil yang **menabrak booking** boleh diambil dengan syarat setiap booking terdampak diberi **mobil pengganti** yang dikonfirmasi/dipilih oleh pembuat event; bila tidak ada pengganti, booking dibatalkan atau mobil event diganti. |
| 18 | Event selesai otomatis setelah rentangnya berakhir (scheduler); pembatalan event meleaskan armada, namun booking yang sudah digeser tetap memakai mobil penggantinya. |
| 19 | Karen berjalan **sepenuhnya di intranet kantor** (tidak diakses dari internet). Seluruh **konfigurasi runtime** (integrasi Google, kuota bidang, organisasi, user) dilakukan **melalui UI aplikasi** — pengguna (admin/pengurus/pegawai) **tidak pernah mengedit file atau folder di server**; file server hanya disentuh sekali oleh petugas IT saat deploy. |
| 20 | Kendaraan memiliki **data sekunder opsional** (nomor rangka, nomor mesin, jatuh tempo pajak tahunan & 5 tahunan) yang dilihat lewat tombol **Detail Kendaraan**; pengurus menerima **notifikasi pajak** saat ≤3 minggu sebelum jatuh tempo (atau lewat tempo). *(UAT 03-A11)* |
| 21 | Pada hasil pencarian mobil pegawai, unit yang **sedang maintenance** pada rentang **tetap tampil nonaktif** dengan keterangan rentangnya — pegawai tahu penyebab tanpa bertanya; unit yang dipinjam pihak lain tetap disembunyikan. *(UAT 03-A12)* |
| 22 | **Penggantian parsial**: bila maintenance/event hanya menabrak bagian tepi rentang peminjaman, pengurus dapat memberi mobil pengganti **hanya untuk tanggal yang menabrak** — sisa tanggal tetap memakai mobil semula (peminjaman terpecah dua secara otomatis). Fitur penggantian berada di **menu mandiri "Penggantian Mobil"**. *(UAT 03-B7, D3)* |

## 6. Lingkup Produk (Scope)

### Termasuk (In Scope)
- Autentikasi email/password + manajemen sesi.
- Manajemen 3 role dengan dashboard berbeda.
- Manajemen user & struktur organisasi (bidang/seksi).
- Pengaturan **kuota peminjaman per bidang** (default 2, satu bidang khusus 3).
- Manajemen kendaraan + jadwal maintenance.
- Pencarian ketersediaan & peminjaman self-service (pegawai **dan pengurus**).
- Penggantian mobil otomatis saat maintenance menabrak booking (dikonfirmasi pengurus).
- **Event armada bidang** (admin & pengurus): pemakaian banyak mobil di atas kuota + penggeseran booking dengan pengganti yang dikonfirmasi.
- **Halaman konfigurasi integrasi Google** (admin): kredensial, spreadsheet, folder Drive, test koneksi, log sync — tanpa mengedit file server.
- Pengembalian manual dengan form keluhan.
- Manajemen anggaran maintenance 4 pos + generate dokumen bend26, draft nota per pos, dan kartu inventaris pemeliharaan kendaraan (dompdf; master draft dikelola satu sistem di Karen).
- Pengembalian otomatis via scheduler.
- Dashboard ringkasan per role.
- Laporan penggunaan kendaraan.
- Profil user dengan nomor HP wajib.

### Tidak Termasuk (Out of Scope) — versi 1
- Approval/persetujuan peminjaman.
- Notifikasi SMS/WhatsApp otomatis.
- Akses aplikasi dari internet (intranet only; integrasi Google hanya via outbound sync Google Sheets).
- Peminjaman dengan satuan jam (hanya hari penuh).
- Aplikasi mobile native (web responsive saja — spesifikasi tampilan desktop vs mobile ada di [feature/ui_responsive.md](feature/ui_responsive.md)).
- Integrasi GPS/tracking kendaraan.
- Manajemen bahan bakar / BBM.

## 7. Referensi
- Detail teknis: [tech.md](tech.md)
- Struktur folder: [structure.md](structure.md)
- Detail fitur: folder [feature/](feature/)
- Rencana implementasi: [taskplan.md](taskplan.md)
