# Skenario 04 — Pengurus: Anggaran, Nota × Koefisien & Dokumen (P1)

**Akun**: `pengurus@karen.test` (atau `admin@karen.test` — kini juga berlaku admin).
**Prasyarat**: skenario 03 selesai (ada maintenance berstatus
terjadwal); siapkan kalkulator untuk cek koefisien (default 1,13 —
cek nilai aktual di Admin → Pengaturan Aplikasi).

> **Rev-2 (implementasi selesai)**: bend26 kini dokumen **BULANAN
> per pos** (4 jenis, format BKPN sesuai file contoh kantor,
> digenerate dari Realisasi Bulanan); dokumen **ditimpa di tempat**
> + badge "Diperbarui" (tanpa v1/v2); kartu inventaris menjadi
> **Kartu Pemeliharaan Kendaraan** (dari menu Laporan); tombol kartu
> maintenance **Input Nota → Edit Nota**; list maintenance
> **terbaru di atas**; admin settings **satu tombol** Simpan
> Pengaturan.

## A. Anggaran 4 Pos per Tahun

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| A1 | Data Kendaraan → **Anggaran** mobil A | Halaman anggaran: form 4 pos + tabel Realisasi & Sisa + pemilih tahun ◀ ▶ | ok | apakah tahun yang ditampilkan secara default adalah tahun berjalan? atau ketika saya membuka sistem pada tahun 2027 juga akan mengikuti tahun 2027? |
| A1b | **Jawaban A1**: default tahun = `now()->year` — ya, otomatis mengikuti tahun berjalan. Buka di 2027 → langsung tampil 2027 | ok | |
| A2 | Isi tahun **2026**: servis 5.000.000, suku cadang 8.000.000, AC 2.000.000, pelumas 1.500.000 → Simpan | Tersimpan; tabel sisa = anggaran (belum ada realisasi) | ok | tambahan: untuk field input angka, kalau bisa diberikan pemisah ribuan, jadi ketika saya ketik 1000000 tampilan menunjukkan 1.000.000 sebelum menekan tombol simpan anggaran|
| A2b | **Verifikasi ulang A2**: ketik `1000000` | Tampil **1.000.000** secara live (pemisah ribuan otomatis) | ok | |
| A3 | Tekan ▶ ke **2027** → isi nilai BERBEDA → Simpan; kembali ◀ 2026 | Nilai 2026 **tidak berubah** (tiap tahun independen) | ok | |
| A4 | Isi kuota/anggaran dengan teks/huruf | Ditolak (angka ≥ 0) | ok | secara tampilan sudah tidak bisa mengetik selain nomor |

## B. Input Nota (rincian → total otomatis → × koefisien)

> Form input nota direvisi *(rev UAT 04)*: input utama = **rincian
> baris** (deskripsi + nominal) per pos; **total per pos otomatis**
> dari jumlah rincian (readonly — bukan input field); **angka
> koefisien tidak ditampilkan** (cukup "dikalikan koefisien pajak").

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| B1 | Jadwal Maintenance → pilih jadwal terjadwal → **Input Nota** (tautan/tombol menuju form) | Form: bengkel, nomor & tgl nota, 4 pos dengan kalkulasi ×1,13 live | revisi | pada menu jadwal maintenance di card mobil yang sedang di maintenance tidak ada tombol ataupun tautan Input Nota|
| B1b | **Verifikasi ulang B1 (implementasi selesai)**: buka Jadwal Maintenance | Kartu maintenance memiliki tombol **Input Nota** | ok | |
| B2 | Isi semua rincian **0 / tanpa baris** → simpan | Ditolak: minimal satu rincian bernilai > 0 | ok | |
| B3 | Isi: bengkel "Bengkel Jaya", nota `INV/001`; pos **Servis** tambah 2 rincian (Ganti oli 300.000 + Ganti kampas 200.000); pos **Pelumas** 1 rincian 300.000; perhatikan angka live | Subtotal servis otomatis **500.000**; total live **565.000** & **339.000** — **tanpa menginput total manual**, tanpa angka koefisien terlihat | revisi | untuk field yang mengandung isian nilai uang atau rupiah perlakuan sama dengan menu anggaran yaitu dibuatkan penanda ketika input, semisal ketika mengetik 1000000 yang terlihat di input field adalah 1.000.000 |
| B3b | **Verifikasi ulang B3 (implementasi selesai)**: ketik `1000000` pada nominal rincian | Tampil **1.000.000** live + subtotal/total ikut terhitung (diverifikasi browser oleh developer: ketik 1000000 → "1.000.000", subtotal Rp 1.000.000, total Rp 1.130.000) | ⬜ | |
| B4 | **Simpan Nota & Generate Dokumen** | Sukses; redirect ke tab **Selesai**; kartu maintenance kini bertombol **"Edit Nota"** (bukan Input Nota); dokumen nota menandai **"Diperbarui"** saat diedit ulang dengan keterangan tanggal jam | revisi | alur setelah menekan tombol Simpan Nota & Generate Dokumen, alur yang saya harapkan adalah redirect ke Selesai, dan di setiap card harus nya sudah tidak muncul tombol input nota, tetapi edit nota, sehingga sistem tidak generate lagi ketika ada perubahan, kemudian nantinya hasil dari simpan proses edit tersebut, merubah status di dokumen nota dan bend26 menjadi updated, dengan keterangan update pada tanggal sekian jam sekian, dan juga urutan mobil adalah posisi paling atas adalah yang di barusaja di maintenance terbaru |
| B4b | **Verifikasi B4 (implementasi selesai)**: simpan nota → lihat tab Selesai | Redirect ke tab Selesai; **maintenance terbaru paling atas**; kartu menampilkan tombol **Edit Nota** (oranye) menggantikan Input Nota; edit nilai → simpan → menu Dokumen menampilkan badge **"Diperbarui {tgl jam}"** pada draft nota terkait (jumlah dokumen tidak bertambah) | ⬜ | |
| B5 | Menu **Dokumen** | Muncul: **2 Draft Nota** (servis & pelumas saja — pos 0 tidak dibuatkan); bend26 tidak lagi otomatis | ok | |
| B6 | ~~Unduh BEND26~~ → **Generate bend26 BULANAN dari Realisasi Bulanan** | Bend26 kini **4 jenis per pos** (Servis, Suku Cadang, Pelumas, Servis AC) berformat **BUKTI KAS PENGELUARAN** sesuai file contoh: terbilang, daftar plat, PPN/PPh, 3 ttd + strip bawah | revisi | saya ingin untuk bend26 format dokumen nya dari saya, tolong tanyakan ke saya lebih lanjut seperti apa formatnya dan juga filetype yang bisa digunakan, bend26 juga seperti nota draft, yaitu terdapat 4 jenis bend26 yang berbeda, |yaitu bend26 untuk servis, suku cadang, pelumas, servic ac. |
| B6b | **Verifikasi B6 (implementasi selesai — format dari docs/Bend26/, PDF)**: Realisasi Bulanan → bulan ber-nota → **Generate Bend26 {bulan}** | 1 PDF per pos bernilai, berisi: Terima dari instansi, Uang sebesar + terbilang huruf, "Yaitu untuk pembayaran Belanja {pos} ... {daftar plat} ... Bulan {bulan}, Nota terlampir", kota + bulan kanan atas, ttd Pengguna Anggaran/Bendahara/Yang menerima (nama+NIP dari pengaturan), strip bawah: PPN = DPP×11%, PPh sesuai tarif pos, Jml, No. Rek per pos, Kode Kegiatan, Tahun Anggaran; regenerasi bulan sama = **timpa** (badge Diperbarui, tidak dobel) | ⬜ | |
| B6c | Admin → Pengaturan Aplikasi → buka **Identitas Dokumen Bend26 (lanjutan)** | Semua identitas bisa diubah via UI (instansi, kota, 4 pejabat+NIP, kode kegiatan, PPN%, No. Rek & PPh% per pos) — satu tombol Simpan Pengaturan menyimpan semuanya | ⬜ | |
| B7 | Unduh Draft Nota "pelumas" | Berisi **rincian baris persis inputan** (mis. pelumas mesin 1 galon 450.000 + pelumas gardan 300.000) + **jumlah total** (750.000) | revisi | isian dari draft nota kurang rinci, harus nya berisi sesuai dengan apa yang saya input semisal saya input pelumas dengan 2 rincian : pelumas mesin mobil one 1 galon = 450.000 dan pelumas gardan bardahl 1 galon = 300.000 maka draft nota juga harus nya berisi 2 item tersebut beserta rupiah nya, dan terdapat jumlah total rupiah dari pelumas |
| B7b | **Verifikasi B7 (implementasi selesai)**: unduh draft nota pos dengan ≥2 rincian | Tabel memuat **1 baris per rincian** (deskripsi + nominal asli) + footer JUMLAH = total nilai asli pos (belum koefisien) | ⬜ | |
| B8 | Kembali ke **Anggaran** mobil tsb (tahun sesuai) | Realisasi servis **565.000**; sisa berkurang; jika minus → merah + peringatan (tidak memblokir) | ok | |
| B9 | **Edit Nota** (tombol baru) nilai berbeda → simpan | Satu entrian per maintenance; realisasi mengikuti nilai terbaru; identitas nota lama **tidak hilang**; rincian lama diganti seluruhnya; dokumen **ditimpa** (lihat B4b) | revisi | terlalu banyak input membingungkan konsepnya lebih baik satu entrian ketika maintenance, jadi tidak menimbulkan kebingungan terhadap pengurus lihat juga revisi poin B4, yaitu ketika sudah generate tombol input nota harus nya berubah menjadi edit nota |
| B9b | **Verifikasi B9 (implementasi selesai)** | Tombol **Edit Nota** membuka form terisi rincian lama; simpan perubahan → tidak ada dokumen/record baru (timpa di tempat) | ⬜ | |
| B10 | ~~Generate Kartu Inventaris~~ → **Laporan → Generate Kartu Pemeliharaan** (pilih mobil + tahun) | PDF **Kartu Pemeliharaan Kendaraan**: judul center (judul / Tahun Anggaran / nama kendaraan / plat); tabel Nomor, Tanggal, Jenis Perbaikan (servis/suku cadang/pelumas/servis ac), Rincian Pemeliharaan (satu cell), Biaya (total belanja setelah koefisien); tombol generate **hanya di menu Laporan** | revisi | kita rubah namanya menjadi kartu pemeliharaan kendaraan, yang berisi judul dengan align centre Kartu Pemeliharaan Kendaraan <br> Tahun Anggaran {tahun berjalan}, lalu kemudian <br> nama kendaraan <br> nomor plat <br>, lalu kemudian dibawah terdapat tabel dengan header nomor, tanggal, jenis perbaikan (yang berisi salah satu dari : servis, cuku cadang, pelumas, servis ac) kemudian header rincian pemeliharaan dijadikan satu cell, header Biaya yang berisi total dari belanja, dan kartu inventaris ini tempatkan pada menu Laporan saja |
| B10b | **Verifikasi B10 (implementasi selesai)**: menu Laporan → pilih mobil → Generate | PDF sesuai format; menu Dokumen & halaman anggaran kendaraan **tidak lagi** punya tombol kartu; menu Dokumen menampilkan label "Kartu Pemeliharaan" | ⬜ | |
| B11 | ~~Regenerate dokumen~~ | **Skema versi v1/v2 & arsip DIHAPUS** — diganti timpa-di-tempat + badge Diperbarui (lihat B4b/B6b) | revisi | saya rasa tidak diperlukan lagi dengan mengubah skema menjadi edit nota |
| B11b | **Verifikasi B11 (implementasi selesai)**: cek menu Dokumen setelah beberapa kali edit nota & regenerate bend26 | Tidak ada v2/v3; setiap dokumen satu record; badge Diperbarui memantulkan waktu perubahan terakhir | ⬜ | |
| B12 | Buka form nota dari **ponsel** (viewport kecil / devtools mobile) | Form **mobile-first**: pos vertikal, tombol + rincian lebar, nominal berpemisah ribuan, tombol ≥44px nyaman disentuh | revisi | tampilan paling bawah masih terlihat tulisan setengah nya alias tertutupi oleh bar yang bersii Beranda, Cari Mobil, Pinjaman, Profil, klau bisa dinaikkan sedikit diatas bar tersebutnsehingga tulisan di paling bawah dapat terlihat utuh |
| B12b | **Verifikasi B12 (implementasi selesai)**: mobile → scroll ke paling bawah form nota | Konten terakhir (tombol Simpan/Batal) terlihat **utuh di atas** bar navigasi bawah (padding bawah mobile dinaikkan 112px → 144px) | ⬜ | |

## C. Batas & Keamanan

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| C1 | Login pegawai → `/pengurus/vehicles/1/budgets` | 403 | ok | |
| C2 | Login pegawai → `/pengurus/documents` | 403 | ok | |
| C3 | Unduh dokumen lewat URL langsung tanpa login | Ditolak (login) | ok | |

## D. Realisasi Bulanan *(skema baru UAT 04 — rev terakhir: default list mobil)*

**Prasyarat**: minimal ada 1 nota tersimpan pada bulan berjalan (bagian B).

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| D1 | Menu **Realisasi Bulanan** (sidebar pengurus) | **Default: daftar mobil** yang maintenance bulan itu — kartu per mobil: nama, plat, jumlah maintenance, total realisasi (termasuk pajak), chip per pos bernilai; ringkasan 4 pos armada di atas | revisi | tampilan daftar mobil descending order ya, maintenance yang paling baru yang diatas |
| D1b | **Verifikasi D1 (implementasi selesai)** | List mobil **terbaru di atas** (urutan berdasar tanggal nota/maintenance terakhir tiap mobil); rincian nota dalam drill-down juga terbaru dulu; tombol **Generate Bend26 {bulan}** tampil saat ada data | ⬜ | |
| D2 | ~~Klik satu mobil~~ *(catatan user di baris ini ternyata tentang halaman Pengaturan Aplikasi admin — lihat E2b)* | Pindah ke rincian mobil: seksi per pos bernilai — subtotal (sebelum pajak) + total (setelah koefisien) + daftar nota per pos (bengkel, tanggal, nomor nota) + tombol **Bend26 pos ini** | revisi | lebih baik tombol simpan dijadikan satu saja yaitu tombol simpan pengaturan, yang berfungsi menyimpan durasi maksimal peminjaman dan koefisien pajak, untuk tombol simpan pengaturan di taruh di paling bawah, tombol simpan semua dihilangkan |
| D3 | Buka **expandable rincian** pada satu nota | Tampil rincian baris nota (deskripsi + nominal) sesuai yang diinput di bagian B | ok | |
| D4 | Tekan **← Semua mobil** | Kembali ke list mobil | ok | |
| D5 | Ganti **pemilih bulan** ke bulan tanpa maintenance | List kosong dengan pesan "Tidak ada mobil yang maintenance pada bulan ini"; ringkasan 0 | ok | |
| D6 | Klik **⬇ CSV** (list & saat drill-down) | File CSV terunduh: kolom tanggal; mobil; plat; bengkel; nota; pos; rincian; nilai; koefisien; total — mengikuti bulan (dan mobil bila sedang drill-down) | ok | |
| D7 | Cek bulan pemilihan | Dropdown berisi 12 bulan terakhir (bulan berjalan paling atas) | ok | |

## E. Anggaran Terpusat & Koefisien Pajak *(skema baru UAT 04)*

| No | Langkah | Hasil Diharapkan | Status | Catatan |
|----|---------|------------------|--------|---------|
| E1 | Menu **Anggaran** (sidebar pengurus) | Ringkasan **seluruh armada** per tahun (pemilih tahun): anggaran vs realisasi vs sisa per pos tiap mobil; ada pintasan atur anggaran per mobil | ok | |
| E2 | Login **admin** → Pengaturan Aplikasi → ubah **Koefisien Pajak** `1,15` → simpan | Tersimpan; rentang ditolak di luar 1,00–2,00 | revisi | di bawah pengaturan koefisien pajak terdapat tombol simpan semua, apa maksud dari tombol tersebut? apakah cuma menyimpan perubahan koefisien pajak atau menyimpan juga konfigurasi durasi maksimal peminjaman? kalau saya lebih baik tombol nama diganti simpan pengaturan koefisien dan untuk fungsi nya juga disesuaikan |
| E2b | **Verifikasi E2 (implementasi selesai)**: buka Pengaturan Aplikasi | **SATU tombol "Simpan Pengaturan" di paling bawah** form (tombol "Simpan Semua" & tombol ganda lama dihilangkan) — menyimpan durasi + koefisien + identitas bend26 sekaligus; terdapat seksi lanjutan "Identitas Dokumen Bend26" | ⬜ | |
| E3 | Pengurus input **nota baru** setelah koefisien berubah | Nota baru memakai **1,15** (cek realisasi); nota lama **tetap 1,13** | ok | |
| E4 | Kembalikan koefisien ke `1,13` | Tersimpan | ok | |
