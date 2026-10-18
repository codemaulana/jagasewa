# Tasks — JagaSewa MVP

> Format: `- [ ] T-xx | Judul | Estimasi: S/M/L`
> Estimasi: S ≈ 1–2 jam, M ≈ 2–4 jam, L ≈ 4–8 jam
> Satu tugas = satu hasil yang bisa dites secara independen.

---

## Milestone 0 — Setup Proyek

- [x] **T-01** | Inisialisasi proyek Laravel + Filament + Livewire
  - **Tujuan:** Scaffold proyek baru dengan semua dependensi utama terpasang.
  - **Dependensi:** —
  - **Kriteria Selesai:** `php artisan serve` berjalan; Filament panel dapat diakses di `/admin`.
  - **Tes:** `php artisan test` tidak ada error; Filament route exists.
  - **Estimasi:** M

- [x] **T-02** | Konfigurasi environment & koneksi database
  - **Tujuan:** `.env` dikonfigurasi untuk DB (MySQL/PostgreSQL), S3 storage, timezone.
  - **Dependensi:** T-01
  - **Kriteria Selesai:** `php artisan migrate` berhasil; `APP_TIMEZONE=Asia/Jakarta`; storage S3 terhubung.
  - **Tes:** `Storage::disk('s3')->put('test.txt', 'ok')` berhasil; bucket privat (403 direct URL).
  - **Estimasi:** S

- [x] **T-03** | Setup GitHub repository + CI (lint + test)
  - **Tujuan:** Repo Git aktif; GitHub Actions jalankan `composer test` + `./vendor/bin/pint` di setiap push.
  - **Dependensi:** T-01
  - **Kriteria Selesai:** PR yang gagal test ditolak oleh CI.
  - **Tes:** Push commit dengan test gagal → CI merah.
  - **Estimasi:** S

---

## Milestone 1 — Auth, Multi-Vendor & Isolasi Data

- [x] **T-04** | Migrasi database & model: `vendors`, `vendor_users`
  - **Tujuan:** Struktur tabel vendor sesuai ERD; model Eloquent dengan relasi.
  - **Dependensi:** T-02
  - **Kriteria Selesai:** Migrasi berjalan; factory menghasilkan data valid; `vendor_users.vendor_id` FK ke `vendors`.
  - **Tes:** Factory test; rollback migrasi bersih.
  - **Estimasi:** S
  - **Story:** US-06

- [x] **T-05** | Registrasi & login vendor (Filament Auth)
  - **Tujuan:** Vendor bisa mendaftar dan login; session terikat pada `vendor_id`.
  - **Dependensi:** T-04
  - **Kriteria Selesai:** Login berhasil → redirect ke dashboard; login gagal → pesan error.
  - **Tes:** Feature test: `POST /vendor/login` dengan kredensial valid dan tidak valid.
  - **Estimasi:** M
  - **Story:** US-06

- [ ] **T-06** | Middleware isolasi vendor + test otomatis
  - **Tujuan:** Setiap request vendor hanya bisa mengakses resource milik vendor sendiri.
  - **Dependensi:** T-05
  - **Kriteria Selesai:** Request vendor A ke resource vendor B → HTTP 404.
  - **Tes:** `VendorIsolationTest`: vendor A request item vendor B → 404; own item → 200.
  - **Estimasi:** M
  - **Story:** US-13 (BR-06)

- [ ] **T-07** | Admin panel: kelola vendor (list, activate, deactivate)
  - **Tujuan:** Admin bisa menonaktifkan vendor; vendor dinonaktifkan tidak bisa login.
  - **Dependensi:** T-05
  - **Kriteria Selesai:** Toggle `is_active`; vendor inactive → redirect dengan pesan.
  - **Tes:** Feature test deactivate + login attempt.
  - **Estimasi:** M
  - **Story:** US-13

---

## Milestone 2 — Katalog Barang

- [ ] **T-08** | Migrasi & model `items`
  - **Tujuan:** Tabel `items` sesuai ERD; semua kolom integer uang (price, deposit, fine).
  - **Dependensi:** T-04
  - **Kriteria Selesai:** Migrasi + factory berjalan; `items.vendor_id` FK.
  - **Tes:** Unit test: factory; rollback bersih.
  - **Estimasi:** S
  - **Story:** US-07

- [ ] **T-09** | CRUD barang di dashboard vendor (Filament Resource)
  - **Tujuan:** Vendor bisa tambah, edit, nonaktifkan barang lewat UI.
  - **Dependensi:** T-08, T-06
  - **Kriteria Selesai:** Barang tersimpan dengan validasi (harga > 0, deposit ≥ 0, buffer ≥ 0); foto ter-upload ke S3.
  - **Tes:** Feature test: POST item valid; POST item harga = 0 → validasi gagal.
  - **Estimasi:** M
  - **Story:** US-07

- [ ] **T-10** | Halaman barang publik (tanpa login)
  - **Tujuan:** Penyewa bisa melihat detail barang via URL slug publik.
  - **Dependensi:** T-09
  - **Kriteria Selesai:** Route `/b/{vendor_slug}/{item_slug}` menampilkan info barang; foto dari signed URL.
  - **Tes:** GET halaman → HTTP 200; foto URL expire dalam 60 menit.
  - **Estimasi:** M
  - **Story:** US-01, US-05

---

## Milestone 3 — Smart Buffer Calendar

- [ ] **T-11** | Service: `BookingCalendarService` (logika buffer overlap)
  - **Tujuan:** Kelas service yang mengecek apakah rentang tanggal baru bentrok dengan booking existing.
  - **Dependensi:** T-08
  - **Kriteria Selesai:** Method `isAvailable(itemId, startDate, endDate, bufferBefore, bufferAfter): bool`.
  - **Tes:** Unit test mencakup semua 10 kasus uji BT-01 s.d. BT-10 dari `business-rules.md`.
  - **Estimasi:** M
  - **Story:** US-01 (BR-01)

- [ ] **T-12** | Endpoint API kalender ketersediaan (JSON)
  - **Tujuan:** Frontend kalender mendapatkan data slot tersedia/terpesan per bulan.
  - **Dependensi:** T-11
  - **Kriteria Selesai:** `GET /api/items/{id}/availability?month=2026-10` mengembalikan JSON array hari dengan status `available | booked | buffer`.
  - **Tes:** Feature test dengan berbagai skenario overlap; response time < 200ms.
  - **Estimasi:** M

- [ ] **T-13** | Komponen Livewire kalender di halaman publik
  - **Tujuan:** Kalender interaktif menampilkan slot dengan warna; penyewa bisa klik tanggal.
  - **Dependensi:** T-12, T-10
  - **Kriteria Selesai:** Navigasi bulan berfungsi; warna sesuai status; klik tanggal tersedia → pra-isi form booking.
  - **Tes:** Browser test (Dusk atau manual): klik tanggal `booked` tidak bisa dipilih.
  - **Estimasi:** L
  - **Story:** US-01

---

## Milestone 4 — Halaman Booking Publik

- [ ] **T-14** | Migrasi & model `renters`, `bookings`
  - **Tujuan:** Tabel sesuai ERD; `bookings.vendor_id`, `item_id`, `renter_id` FK; status enum terdefinisi.
  - **Dependensi:** T-04
  - **Kriteria Selesai:** Migrasi + factory; constraint status; `pending_expires_at` diisi saat insert.
  - **Tes:** Factory; rollback.
  - **Estimasi:** S

- [ ] **T-15** | Form booking publik (Livewire) + validasi
  - **Tujuan:** Penyewa mengisi form; sistem memvalidasi dan membuat booking dengan `SELECT FOR UPDATE`.
  - **Dependensi:** T-11, T-13, T-14
  - **Kriteria Selesai:** Submit valid → booking `pending` terbuat, nomor referensi tampil. Submit tanggal bentrok → error. Submit tanpa consent → disabled.
  - **Tes:** Feature test: booking valid; booking bentrok; booking tanpa consent checkbox.
  - **Estimasi:** L
  - **Story:** US-02, US-04 (BR-01, BR-02)

- [ ] **T-16** | Halaman sukses booking + tautan wa.me
  - **Tujuan:** Setelah booking dibuat, tampilkan nomor referensi dan tombol WA dengan pesan pre-filled.
  - **Dependensi:** T-15
  - **Kriteria Selesai:** URL `wa.me/62xxx?text=...` mengandung nomor referensi, nama barang, tanggal sewa.
  - **Tes:** Unit test: generator URL wa.me dengan berbagai nomor WA (format 08xx, +62xx).
  - **Estimasi:** S
  - **Story:** US-04

- [ ] **T-17** | Scheduler: auto-cancel booking pending > 24 jam
  - **Tujuan:** Job yang berjalan periodik membatalkan booking pending yang kedaluwarsa.
  - **Dependensi:** T-14
  - **Kriteria Selesai:** Job mengubah status `pending` yang melewati `pending_expires_at` → `cancelled_timeout`; kalender dibebaskan.
  - **Tes:** Unit test: booking dengan `pending_expires_at` = 25 jam lalu → status berubah.
  - **Estimasi:** S
  - **Story:** US-08 (BR-02)

---

## Milestone 5 — Unggah Dokumen Aman + Auto-Delete

- [ ] **T-18** | Migrasi & model `identity_documents`
  - **Tujuan:** Tabel menyimpan path + metadata retensi; tidak menyimpan file.
  - **Dependensi:** T-14
  - **Kriteria Selesai:** Kolom `delete_scheduled_at`, `deleted_at`; FK ke `bookings`.
  - **Tes:** Factory; rollback.
  - **Estimasi:** S

- [ ] **T-19** | Upload KTP + selfie ke S3 privat
  - **Tujuan:** File diunggah ke bucket privat; nama file di-UUID; tipe MIME & magic bytes divalidasi.
  - **Dependensi:** T-18, T-02
  - **Kriteria Selesai:** File tersimpan di S3; URL langsung → 403; MIME exe/pdf → tolak; > 5MB → tolak.
  - **Tes:** Feature test: upload JPG valid; upload exe → 422; upload 6MB → 422; direct URL → 403.
  - **Estimasi:** M
  - **Story:** US-03

- [ ] **T-20** | Signed URL sementara untuk akses foto (vendor)
  - **Tujuan:** Vendor bisa melihat foto KTP/selfie via URL bertanda tangan yang expire dalam 60 menit.
  - **Dependensi:** T-19, T-05
  - **Kriteria Selesai:** Signed URL valid 60 menit; URL expired → 403; URL vendor A tidak bisa akses foto vendor B.
  - **Tes:** Unit test: generate signed URL; test expiry; test cross-vendor isolation.
  - **Estimasi:** M
  - **Story:** US-08

- [ ] **T-21** | Job auto-delete dokumen + audit log
  - **Tujuan:** Dokumen dihapus otomatis N hari setelah booking `closed/cancelled`; dicatat di audit log.
  - **Dependensi:** T-18, T-02
  - **Kriteria Selesai:** File terhapus dari S3; `deleted_at` terisi; `audit_logs` mencatat event tanpa path mentah.
  - **Tes:** Unit test: booking closed 31 hari lalu → file terhapus; audit log tidak mengandung path.
  - **Estimasi:** M
  - **Story:** US-03 (BR-05)

---

## Milestone 6 — Dashboard Persetujuan Vendor

- [ ] **T-22** | Daftar booking di dashboard vendor (Filament)
  - **Tujuan:** Vendor melihat semua booking miliknya, difilter per status dan barang.
  - **Dependensi:** T-14, T-06
  - **Kriteria Selesai:** Tabel menampilkan: referensi, nama barang, penyewa, status, tanggal; filter berfungsi; hanya booking vendor sendiri muncul.
  - **Tes:** Feature test: vendor A hanya melihat booking vendor A.
  - **Estimasi:** M
  - **Story:** US-11

- [ ] **T-23** | Halaman detail booking + approve/reject action
  - **Tujuan:** Vendor melihat detail booking (termasuk link foto KTP/selfie) dan bisa approve/reject.
  - **Dependensi:** T-22, T-20
  - **Kriteria Selesai:** Approve → status `approved`; reject → status `rejected`; link wa.me ke penyewa tampil setelah approve.
  - **Tes:** Feature test: approve → status berubah; reject → status berubah; cross-vendor → 404.
  - **Estimasi:** M
  - **Story:** US-08

- [ ] **T-24** | Transisi status: active, returned (manual vendor)
  - **Tujuan:** Vendor bisa mengubah status booking ke `active` (barang dikirim) dan `returned` (barang kembali).
  - **Dependensi:** T-23
  - **Kriteria Selesai:** Tombol "Tandai Aktif" dan "Tandai Dikembalikan" sesuai BR-02; transisi tidak valid (contoh: pending → returned) ditolak.
  - **Tes:** Feature test: urutan valid; urutan tidak valid → error.
  - **Estimasi:** S
  - **Story:** US-08, US-09 (BR-02)

---

## Milestone 7 — Blacklist Pribadi

- [ ] **T-25** | Migrasi & model `blacklist_entries`
  - **Tujuan:** Tabel dengan kolom hash; kolom fase 2 (`scope`, `consent_basis`, `evidence`, `expires_at`) sudah ada tapi nullable.
  - **Dependensi:** T-04
  - **Kriteria Selesai:** Migrasi berjalan; `scope` default `private`.
  - **Tes:** Factory; rollback.
  - **Estimasi:** S

- [ ] **T-26** | Service: normalisasi + HMAC hashing identitas
  - **Tujuan:** `IdentityHashService` menghasilkan hash deterministik dari input WA/NIK/Instagram.
  - **Dependensi:** T-25
  - **Kriteria Selesai:** Input `08123456789` → hash sama setiap kali; input berbeda → hash berbeda.
  - **Tes:** Unit test: normalisasi WA; normalisasi NIK; normalisasi Instagram; determinisme hash.
  - **Estimasi:** S
  - **Story:** US-10 (BR-04)

- [ ] **T-27** | CRUD blacklist di dashboard vendor
  - **Tujuan:** Vendor bisa menambah dan menghapus entri blacklist pribadi.
  - **Dependensi:** T-26, T-06
  - **Kriteria Selesai:** Tambah entri → hash tersimpan (bukan nilai asli); hapus entri; entri vendor lain tidak tampil.
  - **Tes:** Feature test: tambah; hapus; cross-vendor isolation.
  - **Estimasi:** M
  - **Story:** US-10

- [ ] **T-28** | Pencocokan blacklist saat vendor review booking
  - **Tujuan:** Saat vendor membuka detail booking, sistem menampilkan peringatan jika penyewa ada di blacklist vendor.
  - **Dependensi:** T-27, T-23
  - **Kriteria Selesai:** Penyewa di blacklist → peringatan muncul; penyewa bersih → tidak ada peringatan; blacklist vendor lain tidak mempengaruhi.
  - **Tes:** Feature test: booking penyewa blacklisted → warning; booking penyewa bersih → no warning.
  - **Estimasi:** M
  - **Story:** US-10 (BR-04)

---

## Milestone 8 — Kalkulator Denda & Deposit

- [ ] **T-29** | Migrasi & model `settlements`, `damage_items`
  - **Tujuan:** Tabel settlement satu-ke-satu dengan booking; `damage_items` one-to-many dengan settlement.
  - **Dependensi:** T-14
  - **Kriteria Selesai:** Migrasi + factory; constraint FK.
  - **Tes:** Factory; rollback.
  - **Estimasi:** S

- [ ] **T-30** | Service: `FineCalculatorService`
  - **Tujuan:** Menghitung denda berdasarkan rumus BR-03 dengan bilangan bulat rupiah.
  - **Dependensi:** T-29
  - **Kriteria Selesai:** Rumus tepat; tidak ada pembulatan float; hasil sesuai 5 kasus uji DT-01 s.d. DT-05.
  - **Tes:** Unit test: semua 5 kasus DT-xx dari `business-rules.md`; input tanggal kembali < end_date → late_days = 0.
  - **Estimasi:** S
  - **Story:** US-09 (BR-03)

- [ ] **T-31** | Form penyelesaian sewa (Livewire + kalkulasi real-time)
  - **Tujuan:** Vendor mengisi form; kalkulasi denda muncul real-time sebelum submit.
  - **Dependensi:** T-30, T-24
  - **Kriteria Selesai:** Ubah tanggal kembali → ringkasan langsung update; submit → settlement tersimpan; status booking → `closed`.
  - **Tes:** Feature test: submit settlement valid; ringkasan kalkulasi sesuai DT-04 (shortfall).
  - **Estimasi:** M
  - **Story:** US-09

---

## Milestone 9 — Integrasi wa.me & Notifikasi

- [ ] **T-32** | Helper: generator URL wa.me dengan pesan pre-filled
  - **Tujuan:** Semua tautan WA di platform menggunakan helper terpusat.
  - **Dependensi:** T-14
  - **Kriteria Selesai:** Helper normalisasi nomor WA (08xx → +62xx); URL di-encode; bisa diuji unit.
  - **Tes:** Unit test: format 08xx, +62xx, 62xx; karakter spesial di pesan di-encode.
  - **Estimasi:** S
  - **Story:** US-04, US-12

- [ ] **T-33** | Tautan wa.me di halaman sukses booking + halaman detail vendor
  - **Tujuan:** Penyewa dan vendor bisa menghubungi satu sama lain via WA satu klik.
  - **Dependensi:** T-32, T-16, T-23
  - **Kriteria Selesai:** Pesan pre-filled mengandung referensi booking, nama barang, tanggal; link benar di mobile browser.
  - **Tes:** Feature test: output URL sesuai format; nama barang muncul di pesan.
  - **Estimasi:** S
  - **Story:** US-04, US-12

---

## Milestone 10 — Deploy, Backup & Pemantauan Error

- [ ] **T-34** | Konfigurasi server produksi (PHP, Nginx/Caddy, Supervisor)
  - **Tujuan:** Server siap menjalankan Laravel dengan queue worker dan scheduler.
  - **Dependensi:** T-01
  - **Kriteria Selesai:** `php artisan queue:work` berjalan via Supervisor; Nginx melayani traffic HTTPS; `APP_DEBUG=false`.
  - **Tes:** Cek status Supervisor; curl HTTPS → 200.
  - **Estimasi:** M

- [ ] **T-35** | Backup otomatis database harian + uji restore
  - **Tujuan:** Backup database berjalan setiap hari; restore pernah diuji.
  - **Dependensi:** T-34
  - **Kriteria Selesai:** Backup tersimpan di S3 bucket terpisah; prosedur restore terdokumentasi dan diuji sekali.
  - **Tes:** Jalankan restore dari backup terbaru ke environment staging; data konsisten.
  - **Estimasi:** M

- [ ] **T-36** | Error monitoring (Sentry atau setara) + checklist pre-launch security
  - **Tujuan:** Error produksi terdeteksi; semua item checklist di `security.md` lulus.
  - **Dependensi:** T-34
  - **Kriteria Selesai:** Error terkait di Sentry; semua 16+ item checklist security dicentang.
  - **Tes:** Trigger error manual → muncul di Sentry dalam 60 detik; checklist diisi dan di-review.
  - **Estimasi:** M

---

## Matriks Referensi Silang

| Task | User Story | Business Rule |
|------|-----------|--------------|
| T-11 | US-01 | BR-01 |
| T-15 | US-02 | BR-01, BR-02 |
| T-19 | US-03 | — |
| T-21 | US-03 | BR-05 |
| T-16, T-33 | US-04, US-12 | — |
| T-17 | US-08 | BR-02 |
| T-26, T-28 | US-10 | BR-04 |
| T-30 | US-09 | BR-03 |
| T-06 | US-13 | BR-06 |
