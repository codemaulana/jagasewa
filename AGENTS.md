# AGENTS.md — JagaSewa

> Dibaca oleh AI coding agent di awal setiap sesi. Maksimal ~70 baris inti.

---

## Ringkasan Proyek
JagaSewa adalah platform booking berbasis web (mobile-first) untuk rental barang (kostum, kamera, kebaya, dll.) yang dipasarkan via Instagram/X. Penyewa memesan tanpa login; vendor mengelola lewat dashboard. Notifikasi via `wa.me`. Tanpa payment gateway, tanpa chat internal.

## Stack
- **Backend:** Laravel 13, PHP 8.4
- **Admin/Vendor UI:** Filament v5 + Livewire v4
- **Database:** MySQL 8 atau PostgreSQL 16 (pilih saat setup)
- **Storage:** S3-compatible (bucket **privat**)
- **Queue:** Database driver (upgrade ke Redis jika perlu di Fase 2)
- **Timezone:** `Asia/Jakarta` — wajib dikonfigurasi di `config/app.php`

## Perintah Umum
```bash
# Install
composer install && npm install

# Jalankan lokal
php artisan serve
npm run dev

# Migrasi + seed
php artisan migrate --seed

# Tes
php artisan test
php artisan test --filter=BookingCalendarTest

# Lint
./vendor/bin/pint

# Queue worker (lokal)
php artisan queue:work

# Scheduler (lokal)
php artisan schedule:work
```
> **[PLACEHOLDER]** Perintah di atas diperbarui setelah T-01 selesai.

## Struktur Folder
```
app/
  Http/Controllers/Public/   # Halaman publik (booking, item)
  Http/Controllers/Vendor/   # Dashboard vendor
  Filament/Resources/        # Filament admin panel
  Services/                  # BookingCalendarService, FineCalculatorService, IdentityHashService
  Jobs/                      # AutoDeleteDocumentsJob, AutoCancelPendingBookingsJob
  Models/                    # Eloquent models
database/migrations/
docs/                        # Semua dokumen perencanaan
tests/Feature/               # Feature tests per domain
tests/Unit/                  # Unit tests (Services, Helpers)
```

## Konvensi Kode
- Nama tabel, kolom, variabel: **bahasa Inggris** (snake_case)
- Uang: selalu **integer rupiah** — tidak boleh `float` atau `decimal` untuk kalkulasi uang
- Tanggal: gunakan `Carbon` dengan timezone `Asia/Jakarta`; simpan di DB sebagai UTC [ASUMSI]
- Status booking: enum string (`pending`, `approved`, `active`, `returned`, `closed`, `rejected`, `cancelled_timeout`, `cancelled_renter`, `cancelled_vendor`)

## Aturan Wajib (JANGAN DILANGGAR)

1. **Isolasi vendor:** Setiap query resource harus menyertakan `vendor_id`. Resource vendor lain → HTTP 404.
2. **Uang integer:** Jangan gunakan `float` untuk rupiah. Gunakan `integer` di DB dan PHP.
3. **Timezone:** Semua operasi tanggal menggunakan `Asia/Jakarta`. Jangan hardcode offset.
4. **Tanpa PII di log:** `audit_logs` dan Laravel logs tidak boleh mengandung NIK, nomor WA plaintext, atau path file mentah.
5. **Tanpa dependensi baru tanpa izin:** Jangan `composer require` atau `npm install` tanpa bertanya terlebih dahulu.
6. **Jangan kerjakan Fase 2:** Blacklist bersama, payment gateway, chat internal = di luar scope. Jika ada kolom Fase 2 di schema, biarkan nullable dan jangan implementasikan logikanya.
7. **Storage selalu privat:** Jangan set ACL public pada bucket S3. Akses foto hanya via signed URL.

## Alur Kerja per Tugas
1. Baca task ID di `docs/tasks.md` + business rule terkait di `docs/business-rules.md`
2. Buat **rencana singkat** (3–5 poin) sebelum menulis kode
3. Implementasi → tulis/jalankan tes → pastikan tes hijau
4. Jalankan `./vendor/bin/pint` untuk lint
5. Commit kecil dengan pesan: `feat(T-xx): deskripsi singkat`
6. **Berhenti dan lapor** setelah satu task selesai; jangan lanjut ke task berikutnya tanpa instruksi

## Definisi Selesai (Definition of Done)
- [ ] Kode diimplementasi sesuai kriteria selesai di `tasks.md`
- [ ] Semua tes yang disebutkan di task lulus (`php artisan test`)
- [ ] Lint bersih (`./vendor/bin/pint --test`)
- [ ] Tidak ada `APP_DEBUG=true` atau secret ter-hardcode
- [ ] Migrasi dapat di-rollback bersih
- [ ] Commit sudah dibuat dengan pesan konvensional

## Referensi Dokumen
| Dokumen | Isi |
|---------|-----|
| `docs/prd.md` | Ringkasan produk, persona, metrik, ruang lingkup |
| `docs/user-stories.md` | Stories per peran + kriteria penerimaan |
| `docs/business-rules.md` | Aturan buffer, status, denda, blacklist, retensi + kasus uji |
| `docs/erd.md` | Diagram ERD + kamus data + strategi pencegahan double booking |
| `docs/security.md` | Aset dilindungi, kontrol keamanan, checklist pre-launch |
| `docs/user-flow.md` | Diagram alur penyewa & vendor |
| `docs/wireframes.md` | Sketsa tata letak 5 layar utama |
| `docs/tasks.md` | Daftar tugas berurutan per milestone |
