# security.md — JagaSewa

> File ini **menggantikan** versi sebelumnya. Tidak ada pertentangan struktural dengan `prd.md`, `erd.md`, `business-rules.md` — kontrol merujuk BR-xx dan US-xx yang sudah ada.
> ⚠️ Dokumen ini bukan opini hukum. Bagian yang memerlukan tinjauan ahli hukum ditandai 🔍.

---

## A. Model Ancaman

| Aset | Aktor Ancaman | Ancaman | Kontrol Utama | ID | Prioritas | Fase |
|------|--------------|---------|--------------|-----|-----------|------|
| Data semua vendor/booking | Vendor iseng / penyerang | Akses lintas vendor (IDOR) | Policy per resource + `vendor_id` scope + 404 bukan 403 | SEC-08 | **P0** | MVP |
| Foto KTP, NIK hash | Penyerang eksternal | Kebocoran data identitas penyewa | Storage privat, signed URL TTL, enkripsi | SEC-26 | **P0** | MVP |
| File upload | Penyerang | Unggahan berbahaya (webshell, malware) | Magic bytes check, re-encode, UUID filename | SEC-28 | **P0** | MVP |
| Akun vendor | Bot / orang | Pengambilalihan akun (credential stuffing) | Progressive lockout, bcrypt, rotasi sesi | SEC-01 | **P0** | MVP |
| Slot kalender / booking | Dua request simultan | Double booking bersamaan | `SELECT FOR UPDATE` / exclusion constraint | SEC-19 | **P0** | MVP |
| Status langganan | Penyerang / bug | Manipulasi status pembayaran via redirect | Webhook sebagai sumber kebenaran, signature verify | SEC-22 | **P0** | Fase 1.5 |
| Blacklist entries | Vendor kompetitor | Enumerasi / scraping blacklist vendor lain | Rate limit cek blacklist + isolasi vendor_id | SEC-14 | **P1** | MVP |
| Endpoint publik | Bot / DDoS L7 | Spam form booking / brute force | Rate limit per IP + nomor WA | SEC-11 | **P0** | MVP |

---

## B. Autentikasi & Sesi

### SEC-01 — Hashing Sandi | P0 | MVP
- Bcrypt dengan cost ≥ 12 (default Laravel)
- Minimal 8 karakter, kombinasi huruf + angka [ASUMSI]
- Opsional: cek terhadap haveibeenpwned API saat registrasi [ASUMSI]

### SEC-02 — Progressive Lockout | P0 | MVP
| Percobaan Gagal | Tindakan | Pemulihan |
|----------------|---------|-----------|
| 5x dalam 10 menit / IP | Delay 1 menit | Otomatis setelah delay |
| 10x dalam 10 menit / akun | Lock akun 15 menit | Otomatis |
| 15x+ / akun | Lock akun + kirim email notifikasi ke vendor | Manual via reset sandi |

### SEC-03 — Reset Sandi Aman | P0 | MVP
- Token: random 64 byte, disimpan sebagai hash (SHA-256) di DB
- Kedaluwarsa: 60 menit setelah dikirim
- Single-use: token di-invalidate segera setelah digunakan
- Rate limit: lihat SEC-12

### SEC-04 — 2FA Opsional | P2 | MVP
- TOTP via Google Authenticator / Authy [ASUMSI]
- Hanya untuk vendor owner; vendor staff opsional [ASUMSI]
- Backup codes: 8 kode sekali pakai, disimpan sebagai hash

### SEC-05 — Flag Cookie | P0 | MVP
- `HttpOnly`: ya (tidak bisa diakses JS)
- `Secure`: ya (hanya HTTPS)
- `SameSite`: `Lax`
- Waktu kedaluwarsa: explicit (jangan `session` saja)

### SEC-06 — Rotasi Sesi Saat Login | P0 | MVP
- `session()->regenerate()` dipanggil setelah login berhasil
- Mencegah session fixation attack

### SEC-07 — Logout Semua Perangkat | P1 | MVP
- Invalidate semua session aktif untuk user tersebut
- Tersedia di halaman profil vendor
- Wajib dipanggil saat password direset

---

## C. Otorisasi

### SEC-08 — Policy Per Resource | P0 | MVP
- Setiap resource memiliki Laravel Policy class tersendiri
- Middleware `EnsureVendorOwnership` dijalankan di semua route vendor
- Rujuk `authorization-matrix.md` untuk matriks lengkap

### SEC-09 — Tenant Scope | P0 | MVP
- **Setiap** query ke resource yang dimiliki vendor WAJIB menyertakan `WHERE vendor_id = auth_vendor_id`
- Tidak ditemukan = HTTP **404** (bukan 403 — menghindari enumerasi)
- Tes wajib: vendor A request resource vendor B → harus 404

### SEC-10 — ID Publik Acak (ULID) | P0 | MVP
- Semua resource dengan URL publik menggunakan ULID, bukan integer sequential
- Mengapa ULID bukan UUID: sortable by time, lebih pendek, URL-friendly
- Bigint internal ID tidak pernah muncul di URL atau response JSON

> Rujuk `authorization-matrix.md` untuk daftar tes IDOR-01 s.d. IDOR-14.

---

## D. Rate Limiting & Anti-Abuse

### SEC-11..SEC-14 | P0 | MVP

**Penyimpanan counter:** Redis (INCR + EXPIRE atomic) [ASUMSI]. Fallback: cache database jika Redis tidak tersedia.

**Trusted proxy:** Konfigurasi `TrustProxies` middleware dengan Cloudflare IP ranges agar `$request->ip()` mengembalikan IP asli pengunjung, bukan IP Cloudflare. Ikuti dokumentasi resmi Laravel TrustProxies dan list IP Cloudflare di https://www.cloudflare.com/ips/.

| Endpoint | Batas [ASUMSI] | Kunci | Respons | Eskalasi |
|----------|---------------|-------|---------|---------|
| `POST /login` | 5/menit | IP | 429 + `Retry-After` | Lock akun + email setelah 10 gagal |
| `POST /password/reset` | 3/jam | IP | 429 + `Retry-After` | — |
| `POST /booking` (publik) | 10/jam | IP | 429 + `Retry-After` | Cloudflare Turnstile CAPTCHA [ASUMSI] |
| `POST /booking` (per WA) | 3/jam | Hash nomor WA | 429 | — |
| `POST /booking/upload` | 5/15 menit | IP | 429 + `Retry-After` | — |
| `GET /blacklist/check` (internal) | 30/menit | Vendor akun | 429 | Log + alert admin jika pola aneh |
| `GET /documents/signed-url` | 20/menit | Vendor akun | 429 | — |
| `POST /webhooks/payment` | Tidak dibatasi rate | Allowlist IP + signature | 401 jika invalid | — |

**Catatan webhook:** Endpoint webhook tidak dibatasi rate limit biasa. Keamanannya berasal dari: (1) allowlist IP provider pembayaran, (2) verifikasi signature HMAC. Jika IP tidak di allowlist atau signature salah → reject tanpa proses.

**Eskalasi CAPTCHA:** Setelah threshold tertentu [ASUMSI: 3 gagal untuk form publik], tampilkan Cloudflare Turnstile. Turnstile dijalankan di edge, bukan di aplikasi Laravel.

---

## E. DDoS & Edge

### SEC-15 — Cloudflare Proxy & Sembunyikan IP Origin | P0 | MVP
- DNS record VPS di-proxy melalui Cloudflare (orange cloud)
- IP origin VPS **tidak boleh** muncul di DNS publik
- Firewall VPS (UFW): hanya terima koneksi port 80/443 dari Cloudflare IP ranges
- Rujuk `deployment-ops.md` seksi 4.1 untuk konfigurasi UFW

### SEC-16 — WAF & Rate Rules di Edge | P0 | MVP
- Aktifkan Cloudflare WAF dengan ruleset OWASP — ikuti dokumentasi resmi Cloudflare
- Rate rules di Cloudflare untuk volumetrik flood (bukan untuk business logic)
- Bot protection: Cloudflare Bot Management [ASUMSI: JS Challenge di free tier; Managed Rules di Pro]

### Perbedaan Kritis: Edge vs Aplikasi

| Aspek | Edge (Cloudflare) | Aplikasi (Laravel) |
|-------|------------------|-------------------|
| Ancaman | DDoS volumetrik L3/L4/L7, bot flood | Brute force, business logic abuse, per-user |
| Lokasi | Sebelum request masuk VPS | Setelah request masuk VPS |
| Granularitas | Per IP / AS / negara | Per IP + per akun + per nomor WA |
| Konfigurasi | Dashboard Cloudflare | routes/web.php + middleware |
| **Jujur:** VPS kecil **tidak bisa** menahan serangan volumetrik besar sendirian. Cloudflare adalah lini pertahanan pertama dan utama. |

### SEC-17 — Batas Web Server | P0 | MVP
- `client_max_body_size 6M` di Nginx (sedikit di atas batas upload 5MB)
- `client_body_timeout 30s`, `send_timeout 30s`
- `server_tokens off` (sembunyikan versi Nginx)

### SEC-18 — Rencana Saat Diserang | P1 | MVP
1. Aktifkan **Under Attack Mode** di Cloudflare (JS challenge semua pengunjung)
2. Jika trafik dari negara tertentu: block di Cloudflare WAF
3. Periksa log Nginx untuk pola serangan
4. Rujuk Runbook RB-01 di `deployment-ops.md`

---

## F. Integritas Data & Konkurensi

### SEC-19 — Pencegahan Double Booking | P0 | MVP

**Dua pendekatan — pilih sesuai database:**

| | `SELECT FOR UPDATE` (MySQL & PostgreSQL) | Exclusion Constraint (PostgreSQL only) |
|-|------------------------------------------|---------------------------------------|
| **Cara kerja** | Lock baris yang overlapping dalam transaksi, INSERT jika kosong | DB enforce secara deklaratif via GiST index |
| **Kelebihan** | Portabel (MySQL/PostgreSQL), sudah diimplementasi di erd.md | Lebih robust, tidak bisa di-bypass oleh bug kode |
| **Kekurangan** | Harus benar-benar diimplementasi di semua code path | Hanya PostgreSQL; butuh ekstensi `btree_gist` |
| **Contoh** | `BEGIN; SELECT ... FOR UPDATE; INSERT; COMMIT;` | `EXCLUDE USING gist (item_id WITH =, daterange(start_date, end_date, '[]') WITH &&)` |

**Rekomendasi:** Jika PostgreSQL → gunakan exclusion constraint sebagai safety net tambahan di atas `SELECT FOR UPDATE`. Jika MySQL → `SELECT FOR UPDATE` wajib, tidak ada alternatif DB-level. [ASUMSI: PostgreSQL direkomendasikan, lihat ADR-02 di `architecture.md`]

### SEC-20 — Idempotensi Submit Form | P1 | MVP
- Client menyertakan `idempotency_key` (UUID) saat submit booking
- Server cek di DB sebelum insert; jika sudah ada → return existing booking
- Mencegah double-submit akibat koneksi lambat di HP

### SEC-21 — Optimistic Locking Perubahan Status | P1 | MVP
- Tambah kolom `lock_version` (integer) di tabel `bookings`
- Saat update status: `WHERE id = ? AND lock_version = ?`; jika 0 rows affected → error konflik
- Mencegah race condition antara dua vendor staff yang approve/reject booking bersamaan

---

## G. Keamanan Pembayaran Langganan

### SEC-22 — Webhook sebagai Sumber Kebenaran | P0 | Fase 1.5
- Status subscription **hanya** diubah oleh webhook terverifikasi dari Xendit [ASUMSI]
- Browser redirect setelah checkout: tampilkan pesan "menunggu konfirmasi" — JANGAN ubah status dari redirect
- Rujuk `billing.md` seksi 6 untuk urutan lengkap webhook handler

### SEC-23 — Verifikasi Signature Webhook | P0 | Fase 1.5
- Verifikasi signature/token header sesuai dokumentasi resmi Xendit — ikuti dokumentasi resmi
- Tolak request tanpa signature valid: HTTP 401, jangan proses, catat di log

### SEC-24 — Validasi Payload Webhook | P0 | Fase 1.5
- Nominal IDR harus cocok dengan order di DB (integer, bukan float)
- Mata uang harus IDR
- `order_id` harus milik vendor yang benar (cek kepemilikan)
- Jika tidak cocok: HTTP 400, alert admin, jangan ubah status

### SEC-25 — Entitlement Dicek di Server | P0 | Fase 1.5
- Setiap request ke fitur Premium: cek `subscription.status == active AND current_period_end >= now()`
- Jangan andalkan cookie atau session untuk entitlement
- Cache boleh digunakan maksimal 5 menit di Redis [ASUMSI]; bukan dari session browser

---

## H. Unggahan & Dokumen Identitas

### SEC-26 — Storage Privat | P0 | MVP
- Bucket S3 tidak memiliki public-read ACL
- Direct URL ke file → HTTP 403 dari provider
- SSE aktif di sisi provider (SSE-S3 atau SSE-KMS) [ASUMSI]

### SEC-27 — Signed URL Sementara | P0 | MVP
- Setiap akses foto KTP/selfie: generate signed URL baru, TTL ≤ 60 menit [ASUMSI]
- URL tidak disimpan di DB; di-generate on-demand saat vendor buka halaman
- Setiap generate signed URL dicatat di `audit_logs` (tanpa path mentah)

### SEC-28 — Validasi Tipe File (Magic Bytes) | P0 | MVP
- Gunakan PHP `finfo_file()` untuk deteksi tipe berdasarkan isi file, bukan ekstensi atau MIME header dari client
- Tipe yang diterima: `image/jpeg`, `image/png`, `image/webp`
- Tipe lain → tolak HTTP 422

### SEC-29 — Batas Ukuran & Re-encode | P0 | MVP
- Ukuran maksimal: 5 MB per file
- Re-encode gambar via GD atau Imagick sebelum disimpan: membuang payload tersembunyi
- Hapus metadata EXIF (koordinat GPS, info perangkat) saat re-encode

### SEC-30 — Nama File Acak | P0 | MVP
- Nama file disimpan sebagai UUID.ext (mis. `550e8400-e29b-41d4-a716-446655440000.jpg`)
- Nama asli file dari user TIDAK digunakan sebagai path storage

### SEC-31 — Retensi & Auto-Delete | P0 | MVP
- Hapus otomatis 30 hari setelah booking `closed` atau `cancelled_*` (BR-05)
- Fallback: hapus 90 hari setelah upload jika booking tidak pernah ditutup [ASUMSI]
- Penghapusan dicatat di `audit_logs`: `{ event: "document_deleted", booking_id, timestamp }` — tanpa path mentah

---

## I. Proteksi Web Umum

### SEC-32 — CSRF | P0 | MVP
- Laravel CSRF middleware aktif untuk semua route state-changing (POST, PUT, PATCH, DELETE)
- Form Livewire: otomatis ter-protect via Livewire CSRF handling

### SEC-33 — XSS | P0 | MVP
- Blade auto-escape semua output (`{{ }}`)
- Jangan gunakan `{!! !!}` kecuali untuk HTML yang sudah disanitasi
- CSP header: `script-src 'self'`; hindari `unsafe-inline` [ASUMSI: perlu uji kompatibilitas dengan Livewire]

### SEC-34 — SQL Injection | P0 | MVP
- Gunakan Eloquent parameterized queries
- Jika raw query diperlukan: gunakan `DB::select('...', [$param])` dengan binding
- JANGAN string concatenation untuk query

### SEC-35 — Mass Assignment | P0 | MVP
- `$fillable` eksplisit di semua model Eloquent
- JANGAN `$guarded = []`
- Review `$fillable` setiap kali kolom baru ditambahkan ke model

### SEC-36 — SSRF | P1 | MVP
- Jika ada fitur yang mengambil URL eksternal (avatar, webhook test): validasi allowlist domain
- Blokir private IP ranges: `10.0.0.0/8`, `172.16.0.0/12`, `192.168.0.0/16`, `127.0.0.1`
- Di MVP, tidak ada fitur fetch URL eksternal [ASUMSI] — aktifkan kontrol ini saat ada kebutuhan

### SEC-37 — Clickjacking & Header Keamanan | P0 | MVP

| Header | Nilai | Keterangan |
|--------|-------|-----------|
| `X-Frame-Options` | `DENY` | Mencegah clickjacking |
| `X-Content-Type-Options` | `nosniff` | Mencegah MIME sniffing |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Paksa HTTPS 1 tahun |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Kurangi info referrer |
| `Content-Security-Policy` | `default-src 'self'; script-src 'self'; img-src 'self' data:` | Batasi sumber resource [ASUMSI: sesuaikan] |

### SEC-38 — Open Redirect | P1 | MVP
- Jangan gunakan parameter URL (misal `?redirect=`) sebagai destination redirect langsung
- Jika redirect setelah login: hanya izinkan URL internal (sama host)

### SEC-39 — CORS | P1 | MVP
- Whitelist domain frontend saja
- JANGAN gunakan `*` di header `Access-Control-Allow-Origin` di production
- Di MVP: tidak ada SPA terpisah, CORS hanya relevan untuk API endpoint billing [ASUMSI]

---

## J. Rahasia & Konfigurasi

### SEC-40 — .env & Git | P0 | MVP
- `.env` wajib ada di `.gitignore`; tidak pernah di-commit
- Pasang pre-commit hook untuk deteksi credential di-commit [ASUMSI: tools seperti `gitleaks`]
- Di produksi: gunakan secrets manager atau environment injection [ASUMSI]

### SEC-41 — Konfigurasi Produksi | P0 | MVP
- `APP_DEBUG=false` di production
- `APP_ENV=production`
- `APP_KEY` berbeda tiap environment; jangan salin dari staging ke prod

### SEC-42 — Variabel Secret Wajib di `.env`
```
APP_KEY=                        # Enkripsi Laravel (wajib rotate jika bocor)
BLACKLIST_SECRET=               # HMAC untuk blacklist (BERBEDA dari APP_KEY)
AWS_ACCESS_KEY_ID=              # Akses S3
AWS_SECRET_ACCESS_KEY=
SIGNED_URL_TTL_MINUTES=60
DOCUMENT_RETENTION_DAYS=30
XENDIT_WEBHOOK_TOKEN=           # Fase 1.5
XENDIT_SECRET_KEY=              # Fase 1.5
```

### SEC-43 — Hak Minimum DB User | P0 | MVP
- DB user aplikasi: hanya `SELECT, INSERT, UPDATE, DELETE` pada tabel aplikasi
- Tidak boleh `DROP, CREATE, ALTER, TRUNCATE`
- DB user terpisah untuk backup: `SELECT` only

---

## K. Rantai Pasok

### SEC-44 — Audit Dependensi | P0 | MVP
- `composer audit` dijalankan di setiap CI build
- Gagalkan build jika ada CVE dengan severity `high` atau `critical`
- `composer.lock` di-commit ke repo (lockfile wajib)

### SEC-45 — Update Berkala | P1 | MVP
- Review update dependency setiap bulan [ASUMSI]
- Security patch: apply segera setelah tersedia
- Aturan AGENTS.md: jangan `composer require` tanpa persetujuan

---

## L. Logging & Pemantauan

### SEC-46 — Yang WAJIB Dicatat
| Event | Data yang Dicatat |
|-------|-----------------|
| Login sukses / gagal | `vendor_id`, IP, timestamp, user_agent |
| Logout | `vendor_id`, timestamp |
| Perubahan status booking | `booking_id` (ULID), status lama, status baru, aktor |
| Generate signed URL | `booking_id`, `vendor_id`, timestamp (TANPA path file) |
| Akses cek blacklist | `vendor_id`, timestamp |
| Webhook diterima | event_type, `subscription_id`, status validasi |
| Auto-delete dokumen | `booking_id`, `doc_type`, timestamp |
| Perubahan konfigurasi platform | admin_id, field yang diubah, timestamp |

### SEC-47 — Yang TIDAK BOLEH Dicatat
- NIK (plaintext atau hash-nya)
- Nomor WhatsApp (plaintext)
- Path file mentah ke S3
- Isi foto / konten dokumen
- Data kartu pembayaran
- Password (plaintext maupun hash)
- Token reset sandi

### SEC-48 — Audit Trail | P0 | MVP
- `audit_logs` bersifat append-only; tidak ada `UPDATE` atau `DELETE` pada tabel ini
- Minimal simpan 1 tahun [ASUMSI]

### SEC-49 — Alert | P1 | MVP
| Kondisi | Threshold [ASUMSI] | Penerima |
|---------|-------------------|---------|
| Login gagal berulang | > 10 gagal / IP / 5 menit | Admin + PIC teknis |
| Disk usage VPS | > 80% | PIC teknis |
| Error rate 5xx | > 5% dalam 5 menit | PIC teknis |
| Webhook tidak diterima | Order pending > 30 menit | PIC teknis (Fase 1.5) |

---

## M. Respons Insiden

### SEC-50 — Langkah Ringkas | P0 | MVP
1. **Deteksi** — alert otomatis atau laporan user
2. **Isolasi** — nonaktifkan akses yang terdampak (bucket, akun, endpoint)
3. **Investigasi** — periksa `audit_logs`, log Nginx, log Cloudflare
4. **Pemulihan** — restore dari backup jika perlu; perbaiki root cause
5. **Post-mortem** — dalam 24 jam; dokumentasikan timeline, dampak, tindakan

Rujuk runbook detail di `deployment-ops.md` (RB-01 s.d. RB-05).

### SEC-51 — Kewajiban Pemberitahuan Kebocoran 🔍
- Kewajiban dan batas waktu notifikasi ke Kominfo/BSSN dan subjek data: **perlu tinjauan ahli hukum**
- Jangan klaim batas waktu spesifik tanpa konfirmasi hukum
- Siapkan template notifikasi dan kontak darurat sebelum peluncuran

---

## N. Checklist Pra-Peluncuran

### N.1 Checklist Teknis
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `.env` tidak ter-commit; tidak ada secret di Git history
- [ ] Bucket S3: tidak ada public-read ACL; SSE aktif
- [ ] HTTPS aktif dengan sertifikat valid; HSTS diaktifkan
- [ ] Firewall VPS: hanya Cloudflare IP yang bisa akses port 80/443
- [ ] Rate limiting dikonfigurasi dan diuji (TC-21..TC-23)
- [ ] Test isolasi vendor lulus: HTTP 404 untuk resource vendor lain (IDOR-01..IDOR-14)
- [ ] Upload validation: magic bytes, max size 5MB, UUID rename, EXIF dihapus (TC-31..TC-35)
- [ ] Auto-delete job terdaftar di scheduler dan diuji di staging (TC-36..TC-38)
- [ ] `audit_logs` tidak mengandung PII (spot check manual)
- [ ] Signed URL TTL ≤ 60 menit; direct URL → 403
- [ ] Password bcrypt cost ≥ 12
- [ ] `BLACKLIST_SECRET` berbeda dari `APP_KEY`
- [ ] Header keamanan hadir: CSP, HSTS, X-Frame-Options, X-Content-Type-Options (TC-39)
- [ ] `composer audit` → 0 high/critical CVE
- [ ] Backup database terjadwal dan pernah diuji restore (lihat `deployment-ops.md`)
- [ ] Error monitoring aktif (Sentry atau setara) [ASUMSI]
- [ ] Concurrent booking test lulus (TC-24..TC-25)
- [ ] CSRF aktif untuk semua POST/PUT/PATCH/DELETE
- [ ] DB user hanya memiliki hak SELECT/INSERT/UPDATE/DELETE

### N.2 Checklist Legal & Proses
- [ ] Teks consent penyewa ditinjau ahli hukum 🔍
- [ ] Kebijakan Privasi dipublikasikan dan dapat diakses
- [ ] Syarat Layanan untuk vendor ditinjau ahli hukum 🔍
- [ ] Prosedur DSAR (akses/hapus data) terdokumentasi
- [ ] Kontak darurat insiden (PIC teknis + PIC legal) tercatat
- [ ] DPO ditunjuk jika diperlukan regulasi 🔍

### N.3 Daftar Tes Keamanan Wajib

| ID | Skenario | Jenis | Referensi |
|----|---------|-------|-----------|
| ST-01 | IDOR: vendor A akses item vendor B → 404 | Feature | IDOR-01, SEC-09 |
| ST-02 | IDOR: vendor A akses booking vendor B → 404 | Feature | IDOR-02, SEC-09 |
| ST-03 | IDOR: vendor A generate signed URL dokumen vendor B → 404 | Feature | IDOR-03, SEC-09 |
| ST-04 | Login brute force 6x/menit → 429 + Retry-After | Feature | TC-21, SEC-11 |
| ST-05 | Upload file `.exe` dengan MIME `image/jpeg` → ditolak | Feature | TC-31, SEC-28 |
| ST-06 | Upload file JPEG 6MB → ditolak | Feature | TC-32, SEC-29 |
| ST-07 | Double booking simultan (dua request bersamaan) → satu sukses | Feature | TC-24, SEC-19 |
| ST-08 | Webhook billing: signature salah → HTTP 401 | Feature | TC-28, SEC-23 |
| ST-09 | Webhook billing: replay duplikat → HTTP 200 idempoten | Feature | TC-26, SEC-22 |
| ST-10 | Signed URL expired (> 60 menit) → HTTP 403 | Feature | TC-40, SEC-27 |
| ST-11 | Auto-delete job: file > 30 hari setelah closed dihapus | Unit | TC-36, SEC-31 |
| ST-12 | CSRF: POST tanpa token → HTTP 419 | Feature | TC-39, SEC-32 |
| ST-13 | XSS: output di Blade di-escape otomatis | Unit | SEC-33 |
| ST-14 | Enumerasi booking by sequential ID gagal (ULID digunakan) | Feature | IDOR-10, SEC-10 |
| ST-15 | Direct URL ke file S3 → HTTP 403 | Feature | TC-35, SEC-26 |
| ST-16 | HTTPS redirect: HTTP → HTTPS paksa | Manual | SEC-37 |
| ST-17 | Header keamanan hadir di semua response | Feature | SEC-37 |
| ST-18 | Blacklist vendor A tidak terlihat vendor B | Feature | IDOR-04, SEC-08 |
