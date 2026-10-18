# testing-strategy.md — JagaSewa

> [!IMPORTANT]
> Dokumen ini adalah **living document**. Setiap fitur baru wajib disertai entri tes baru di Bagian 2 sebelum PR dapat di-merge.

---

## 1. Piramida Tes

```
                        /\
                       /  \
                      / E2 \
                     / E/   \
                    / Browser \
                   /  (Dusk)  \
                  /  < 10%    \
                 /  happy path \
                /  kritis saja  \
               /________________\
              /                  \
             /  Feature/Integration\
            /   Tests (PHPUnit)    \
           /   ~30% — per endpoint  \
          /____________________________\
         /                              \
        /       Unit Tests               \
       /   > 60% — Services & Helpers    \
      /____________________________________\
```

| Lapisan | Framework | Proporsi [ASUMSI] | Cakupan |
|---|---|---|---|
| **Unit** | PHPUnit | > 60% | Services, Helpers, Value Objects, Jobs |
| **Feature / Integration** | PHPUnit (HTTP test) | ~30% | Endpoint, middleware, autorисasi, webhook |
| **E2E / Browser** | Laravel Dusk | < 10% | Happy path kritis: booking flow, login vendor, dashboard |

> [!NOTE]
> Target proporsi bersifat **[ASUMSI]** dan dapat disesuaikan setelah sprint pertama selesai. Yang tidak boleh berubah: semua tes P0 di Bagian 2 wajib ada sebelum rilis MVP.

---

## 2. Daftar Tes Wajib

### 2.1 Buffer Calendar (BR-01) — T-11

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-01 | Buffer Calendar | BT-01 Overlap langsung | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-02 | Buffer Calendar | BT-02 Irisan buffer before | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-03 | Buffer Calendar | BT-03 Irisan buffer after | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-04 | Buffer Calendar | BT-04 Back-to-back tepat di batas buffer | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-05 | Buffer Calendar | BT-05 Slot aman di luar buffer | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-06 | Buffer Calendar | BT-06 Rentang satu hari | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-07 | Buffer Calendar | BT-07 Pergantian bulan | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-08 | Buffer Calendar | BT-08 Pergantian tahun | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-09 | Buffer Calendar | BT-09 Pembatalan membebaskan slot | Unit | `BookingCalendarServiceTest` | P0 | T-11 |
| TC-10 | Buffer Calendar | BT-10 Buffer 0 hari — back-to-back diizinkan | Unit | `BookingCalendarServiceTest` | P0 | T-11 |

### 2.2 Kalkulator Denda (BR-03) — T-30

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-11 | Kalkulator Denda | DT-01 Tepat waktu tanpa kerusakan → denda = 0 | Unit | `FineCalculatorServiceTest` | P0 | T-30 |
| TC-12 | Kalkulator Denda | DT-02 Terlambat 2 hari → denda per hari dikali 2 | Unit | `FineCalculatorServiceTest` | P0 | T-30 |
| TC-13 | Kalkulator Denda | DT-03 Ada kerusakan → biaya kerusakan ditambah | Unit | `FineCalculatorServiceTest` | P0 | T-30 |
| TC-14 | Kalkulator Denda | DT-04 Shortfall deposit < total denda | Unit | `FineCalculatorServiceTest` | P0 | T-30 |
| TC-15 | Kalkulator Denda | DT-05 Kembali sebelum jatuh tempo → denda = 0 | Unit | `FineCalculatorServiceTest` | P0 | T-30 |

### 2.3 Isolasi Vendor / IDOR (BR-06, SEC-08) — T-06, T-07

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-16 | Isolasi Vendor | Vendor A akses item milik Vendor B → 404 | Feature | `VendorIsolationTest` | P0 | T-06, IDOR-01 |
| TC-17 | Isolasi Vendor | Vendor A akses booking milik Vendor B → 404 | Feature | `VendorIsolationTest` | P0 | T-06, IDOR-02 |
| TC-18 | Isolasi Vendor | Vendor A generate signed URL dokumen Vendor B → 404 | Feature | `VendorIsolationTest` | P0 | IDOR-03 |
| TC-19 | Isolasi Vendor | Vendor A akses blacklist Vendor B → 404 | Feature | `VendorIsolationTest` | P0 | IDOR-04 |
| TC-20 | Otorisasi Admin | Admin akses data vendor via admin route → 200 | Feature | `AdminAuthorizationTest` | P0 | T-07 |

### 2.4 Rate Limiting (SEC-11..SEC-14) — T-37 [USULAN]

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-21 | Rate Limiting | Login 6x dalam 1 menit per IP → 429 + header `Retry-After` | Feature | `RateLimitTest` | P0 | T-37 [USULAN] |
| TC-22 | Rate Limiting | Form booking 11x dalam 1 jam per IP → 429 | Feature | `RateLimitTest` | P0 | T-37 [USULAN] |
| TC-23 | Rate Limiting | Upload dokumen 6x dalam 15 menit per IP → 429 | Feature | `RateLimitTest` | P0 | T-37 [USULAN] |

### 2.5 Konkurensi Double Booking (SEC-19) — T-15

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-24 | Konkurensi | Dua request simultan untuk item + tanggal sama → hanya satu sukses | Feature | `ConcurrentBookingTest` | P0 | T-15 |
| TC-25 | Konkurensi | Tiga request simultan → hanya satu sukses, dua lainnya error 409/422 | Feature | `ConcurrentBookingTest` | P0 | T-15 |

> [!WARNING]
> Tes TC-24 dan TC-25 memerlukan database nyata (bukan SQLite in-memory) untuk menguji row-level locking. Gunakan MySQL/PostgreSQL di environment CI.

### 2.6 Webhook Billing (SEC-22, billing.md) — T-41 [USULAN]

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-26 | Webhook Billing | BLT-01 Webhook ganda (replay) → HTTP 200 idempoten, tidak duplikat proses | Feature | `XenditWebhookTest` | P1 | T-41 [USULAN] |
| TC-27 | Webhook Billing | BLT-04 Nominal tidak cocok → tolak dan kirim alert | Feature | `XenditWebhookTest` | P1 | T-41 [USULAN] |
| TC-28 | Webhook Billing | BLT-08 Signature salah → HTTP 401 | Feature | `XenditWebhookTest` | P0 | T-41 [USULAN] |
| TC-29 | Webhook Billing | BLT-05 Replay attack → tolak (`gateway_event_id` sudah ada di DB) | Feature | `XenditWebhookTest` | P0 | T-41 [USULAN] |
| TC-30 | Webhook Billing | BLT-06 Race redirect vs webhook → redirect tidak mengubah status langganan | Feature | `XenditWebhookTest` | P1 | T-41 [USULAN] |

> [!NOTE]
> TC-26..TC-30 relevan untuk **Fase 1.5** (vendor subscription billing). Bisa di-skip pada rilis MVP selama billing belum aktif, tapi wajib ada sebelum fitur billing di-launch.

### 2.7 Upload File Berbahaya (SEC-26..SEC-31) — T-19

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-31 | Upload File | Upload file `.exe` dengan MIME `image/jpeg` → tolak (magic bytes check) | Feature | `DocumentUploadTest` | P0 | T-19 |
| TC-32 | Upload File | Upload file JPEG valid ukuran > 5 MB → tolak | Feature | `DocumentUploadTest` | P0 | T-19 |
| TC-33 | Upload File | Upload file PHP dengan ekstensi `.jpg` → tolak | Feature | `DocumentUploadTest` | P0 | T-19 |
| TC-34 | Upload File | Upload file JPEG valid → disimpan dengan nama UUID, EXIF dihapus | Feature | `DocumentUploadTest` | P0 | T-19 |
| TC-35 | Upload File | Akses direct URL ke objek S3/storage → 403 | Feature | `DocumentUploadTest` | P0 | T-19 |

### 2.8 Job Retensi Data (BR-05) — T-21

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-36 | Retensi Data | `AutoDeleteDocumentsJob` → file > 30 hari setelah booking `closed` dihapus | Unit | `AutoDeleteDocumentsJobTest` | P0 | T-21 |
| TC-37 | Retensi Data | `AutoDeleteDocumentsJob` → `audit_log` tidak mengandung path file setelah hapus | Unit | `AutoDeleteDocumentsJobTest` | P0 | T-21 |
| TC-38 | Retensi Data | Fallback: file > 90 hari setelah upload (booking tidak pernah `closed`) dihapus | Unit | `AutoDeleteDocumentsJobTest` | P1 | T-21 |

### 2.9 Keamanan Web (SEC-32..SEC-39) — T-20, T-26, T-38 [USULAN]

| ID | Kategori | Skenario | Jenis Tes | File Tes | Prioritas | Referensi |
|---|---|---|---|---|---|---|
| TC-39 | Keamanan Web | CSRF token tidak ada pada POST request → HTTP 419 | Feature | `CsrfTest` | P0 | T-38 [USULAN] |
| TC-40 | Keamanan Web | Signed URL sudah expired → HTTP 403 | Feature | `SignedUrlTest` | P0 | T-20 |
| TC-41 | Keamanan Web | Blacklist — pencocokan hash bersifat deterministik (input sama → hash sama) | Unit | `IdentityHashServiceTest` | P0 | T-26 |

---

## 3. Aturan Data Uji

> [!CAUTION]
> **DILARANG** menggunakan data produksi (foto KTP asli, selfie asli, NIK asli) di environment tes manapun, termasuk staging.

| Aturan | Detail |
|---|---|
| **Identitas palsu** | Factory menggunakan Faker untuk nama, nomor WA, dan NIK palsu yang realistis tapi tidak valid |
| **Format NIK palsu** | 16 digit, dimulai dengan `9999` — tidak valid sebagai NIK asli [ASUMSI] |
| **File gambar** | Di-generate secara programatik menggunakan PHP GD library; bukan foto asli |
| **Environment terpisah** | File `.env.testing` terpisah dari `.env.local`; database tes berbeda dari database development |
| **Cleanup** | Setiap test class menggunakan `RefreshDatabase` trait; file yang di-upload ke storage tes dihapus setelah setiap test run |
| **Seed data** | Gunakan `DatabaseSeeder` khusus testing (`--env=testing`); jangan jalankan production seeder |

---

## 4. Langkah CI (GitHub Actions)

CI berjalan pada **setiap push ke branch `main`** dan **setiap Pull Request**. Deploy otomatis ke staging hanya diizinkan jika **semua langkah berikut hijau (exit code 0)**.

```
Langkah  Perintah                                                    Gagal jika
───────  ──────────────────────────────────────────────────────────  ──────────────────────────────────────
  1      composer install --no-dev --optimize-autoloader             dependency tidak terinstall
  2      php artisan config:cache && php artisan route:cache         config/route error
  3      ./vendor/bin/pint --test                                    ada pelanggaran gaya kode (lint error)
  4      composer audit                                              ditemukan CVE severity high / critical
  5      php artisan test --parallel                                 ada tes yang gagal
  6      [report coverage]  target: >= 80% untuk direktori Services  [ASUMSI] coverage di bawah threshold
```

> [!TIP]
> Gunakan GitHub Actions cache untuk `vendor/` berdasarkan hash `composer.lock` agar langkah 1 lebih cepat pada run berikutnya. Ikuti dokumentasi resmi GitHub Actions untuk konfigurasi cache.

> [!NOTE]
> Langkah 6 (coverage report) menggunakan `--coverage-clover` di PHPUnit. Threshold di-enforce via `<coverage>` tag di `phpunit.xml`. [ASUMSI]

---

## 5. Kriteria Rilis

Semua kriteria berikut **wajib terpenuhi** sebelum rilis ke production. Kriteria P0 bersifat **blocking** (rilis ditahan jika gagal).

| Kriteria | Target | Cara Ukur | Blocking? |
|---|---|---|---|
| Semua tes P0 lulus | 100% | CI pass — `php artisan test --parallel` | ✅ Ya |
| Coverage unit tests direktori `Services/` | ≥ 80% [ASUMSI] | PHPUnit coverage report (`--coverage-clover`) | ✅ Ya |
| `composer audit` | 0 CVE severity high/critical | Exit code `composer audit` | ✅ Ya |
| Lint (Pint) | 0 error | Exit code `pint --test` | ✅ Ya |
| IDOR tests lulus | 100% (IDOR-01..IDOR-12) | CI pass — `VendorIsolationTest` | ✅ Ya |
| Concurrent booking test lulus | 100% (TC-24, TC-25) | CI pass — `ConcurrentBookingTest` | ✅ Ya |
| Checklist security pra-launch selesai | 100% item ter-review | Manual review oleh tech lead | ✅ Ya |
| Staging deploy sukses | 0 error HTTP 5xx dalam 15 menit pertama | Monitoring Sentry (error rate alert) | ✅ Ya |

> [!WARNING]
> Jangan bypass kriteria blocking dengan label `skip-ci` atau `hotfix` tanpa persetujuan eksplisit dari tech lead dan catatan di PR description.
