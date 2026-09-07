# Fitur: Log Aktivitas (Admin)

## Deskripsi
Mencatat **siapa mengubah apa, kapan** untuk seluruh data penting Karen — dibuat karena pengelolaan kendaraan dipegang **lebih dari satu akun pengurus**, sehingga perubahan harus bisa dilacak ke pelakunya (keputusan user pasca-UAT 03). Hanya **admin** yang melihat log.

## Spesifikasi

### Yang Dicatat
Model berpegang trait `LogsActivity`: Kendaraan, Jadwal Maintenance, Peminjaman, Event Armada, Anggaran, Biaya Maintenance, Keluhan, Pengguna, Bidang, Seksi, Konfigurasi Integrasi, Dokumen.

| Aksi | Catatan log |
|------|-------------|
| Menambah | "Menambah {objek}" |
| Mengubah | "Mengubah {objek} (kolom: …)" + **nilai lama→baru per kolom** |
| Menghapus | "Menghapus {objek}" (soft delete; aktivasi kembali tercatat sebagai perubahan `deleted_at` → null) |
| Sistem | Ringkasan scheduler (pengembalian otomatis 00:01, penutupan event 00:02) — pelaku "Sistem (otomatis)" |

### Keamanan
- **Field sensitif tidak pernah dicatat isinya**: `password`, `remember_token`, dan `value` Konfigurasi Integrasi (berisi kunci service account terenkripsi).
- Perubahan yang hanya menyentuh field tersembunyi/timestamps **tidak menghasilkan log** (anti-noise).
- Pelaku = user yang sedang login (`Auth::id()`); eksekusi console/scheduler → NULL → tampil "Sistem (otomatis)".

### Halaman (Admin → Log Aktivitas)
- Filter: pencarian deskripsi/objek, pelaku, aksi, jenis objek, rentang tanggal; paginasi 25.
- Setiap entri: pelaku + badge aksi + objek + waktu; "Detail perubahan" membuka tabel kolom/nilai lama/nilai baru.

## Skema
Tabel `activity_logs`: `user_id` (NULL=sistem, FK nullOnDelete), `action` (created/updated/deleted/restored/system), `model_type`, `model_id`, `model_label` (mis. "Kendaraan Avanza B 1234 XYZ"), `description`, `changes` (JSON [kolom→[lama,baru]]), `created_at` sebagai waktu kejadian. Index: waktu, pelaku, objek.

## Endpoint
| Method | Path | Akses |
|--------|------|-------|
| GET | `/admin/activity-logs` (+query filter) | admin |

## Catatan Teknis
- Trait `App\Models\Concerns\LogsActivity` via event model — **mass-update query tidak memicu event**, sehingga `autoReturn()`/`autoFinish()` menulis log ringkasan manual berpengeksekusi "Sistem".
- Jangan mendaftarkan `static::restored(...)` di trait — bukan event sihir di Laravel 13 (memancing reentransi boot; pelajaran ini dicatat di build log).

## Skenario Uji
1. Pengurus A menambah mobil → log "Menambah Kendaraan …" pelakunya A.
2. Admin mengubah status mobil itu → log perubahan `status` lama→baru pelakunya admin.
3. Hapus → log Menghapus; aktivasi kembali user → tercatat sebagai perubahan `deleted_at`.
4. Ubah nama+password user sekaligus → `name` tercatat, `password` TIDAK ada di log.
5. Scheduler auto-return → entri "Sistem (otomatis)".
6. Filter pelaku/aksi/q bekerja; pegawai 403.
