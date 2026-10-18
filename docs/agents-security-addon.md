# agents-security-addon.md — USULAN Penambahan ke AGENTS.md / tasks.md

> ⚠️ **USULAN SAJA.** File ini tidak menimpa file lama. Terapkan secara manual ke `AGENTS.md`, `CLAUDE.md`, dan `tasks.md` setelah disetujui.

---

## USULAN: Tambahan Bagian "Aturan Keamanan Wajib" di AGENTS.md

Tambahkan setelah bagian **"Aturan Wajib (JANGAN DILANGGAR)"** yang sudah ada:

```markdown
## Aturan Keamanan Wajib (JANGAN DILANGGAR)

8.  **ID publik ULID/UUID:** Jangan gunakan integer sequential sebagai ID di URL atau
    response JSON. Semua resource publik menggunakan ULID atau UUID. (SEC-10)

9.  **404 bukan 403 untuk IDOR:** Jika resource ditemukan tapi milik vendor lain,
    kembalikan HTTP 404. Jangan 403 — mencegah enumerasi resource. (SEC-09)

10. **Webhook sumber kebenaran pembayaran:** Hanya webhook Xendit yang terverifikasi
    boleh mengubah status subscription. Browser redirect TIDAK boleh mengubah status
    apapun. (SEC-22)

11. **Signed URL selalu TTL <= 60 menit:** Jangan generate URL tanpa batas waktu untuk
    dokumen identitas. Setiap akses harus di-generate ulang on-demand. (SEC-27)

12. **Validasi upload berdasarkan isi file (magic bytes):** Jangan percaya ekstensi file
    atau MIME header dari client. Selalu validasi dengan finfo_file() PHP. (SEC-28)

13. **Re-encode gambar + hapus EXIF:** Setiap foto KTP/selfie harus di-re-encode via GD
    atau Imagick dan metadata EXIF (GPS, device) dihapus sebelum disimpan. (SEC-29)

14. **Rate limiting wajib:** Semua endpoint publik harus memiliki throttle middleware.
    Konfigurasi TrustProxies untuk Cloudflare IP. Jangan deploy tanpa rate limiter. (SEC-11)

15. **BLACKLIST_SECRET terpisah dari APP_KEY:** Gunakan variabel .env berbeda untuk HMAC
    blacklist. Rotasi prosedur harus terdokumentasi. (SEC-42)

16. **Jangan log PII:** Audit logs dan Laravel logs tidak boleh mengandung NIK, nomor WA
    plaintext, path file mentah, isi foto, atau data kartu. Gunakan ULID booking saja. (SEC-47)

17. **Tes IDOR wajib sebelum merge:** Setiap PR yang menyentuh resource ownership harus
    menyertakan atau memperbarui tes IDOR di VendorIsolationTest. (SEC-08, SEC-09)
```

*(17 aturan total termasuk 7 yang sudah ada di AGENTS.md — total dalam batas 25 baris)*

---

## USULAN: Tambahan Tugas di tasks.md

Sambungkan dari T-36. Tambahkan dua milestone baru:

```markdown
---

## Milestone 11 -- Keamanan Lanjutan (MVP)

- [ ] **T-37** | Rate limiting semua endpoint kritis
  - **Tujuan:** Konfigurasi Laravel throttle middleware untuk login, booking publik, upload
    dokumen, dan cek blacklist sesuai tabel SEC-11..SEC-14 di security.md.
  - **Dependensi:** T-02 (Redis/cache), T-05, T-15, T-19
  - **Kriteria Selesai:** Setiap endpoint kritis mengembalikan HTTP 429 + Retry-After
    jika melebihi batas; TrustProxies dikonfigurasi dengan Cloudflare IP ranges.
  - **Tes:** TC-21 (login brute force), TC-22 (booking flood), TC-23 (upload limit)
  - **Estimasi:** M | **SEC:** SEC-11..SEC-14

- [ ] **T-38** | Header keamanan HTTP + middleware CSRF
  - **Tujuan:** Pasang semua header keamanan (HSTS, CSP, X-Frame-Options,
    X-Content-Type-Options, Referrer-Policy) di Nginx atau middleware Laravel;
    konfirmasi CSRF middleware aktif untuk semua POST/PUT/PATCH/DELETE.
  - **Dependensi:** T-34 (server setup)
  - **Kriteria Selesai:** Semua header hadir saat dicek via curl; CSRF test lulus.
  - **Tes:** TC-39 (CSRF); curl header check di CI sebagai langkah tambahan.
  - **Estimasi:** S | **SEC:** SEC-32, SEC-33, SEC-37, SEC-38

- [ ] **T-39** | ULID sebagai public ID di semua resource
  - **Tujuan:** Semua model dengan URL publik menggunakan ULID sebagai ID eksternal;
    bigint primary key tetap internal.
  - **Dependensi:** T-04, T-08, T-14
  - **Kriteria Selesai:** Route parameter menggunakan ULID; integer ID tidak muncul di URL
    atau JSON response; IDOR-10 (enumerasi sequential) gagal seperti yang diharapkan.
  - **Tes:** IDOR-10 dari authorization-matrix.md; unit test ULID generation dan routing.
  - **Estimasi:** M | **SEC:** SEC-10

- [ ] **T-40** | Hardening VPS: firewall + SSH + Nginx
  - **Tujuan:** VPS hanya menerima koneksi HTTP/HTTPS dari Cloudflare IP; SSH hanya dari
    IP developer; root login dinonaktifkan; Nginx dikonfigurasi sesuai deployment-ops.md.
  - **Dependensi:** T-34
  - **Kriteria Selesai:** UFW dikonfigurasi; curl langsung ke IP VPS -> connection refused;
    SSH dengan root -> denied; server_tokens off terkonfirmasi.
  - **Tes:** Manual: curl langsung ke IP VPS -> connection refused; SSH root -> denied.
  - **Estimasi:** M | **SEC:** SEC-15, SEC-16, SEC-17

---

## Milestone 12 -- Billing Langganan (Fase 1.5)

- [ ] **T-41** | Migrasi tabel billing: subscriptions, subscription_events, manual_activations
  - **Tujuan:** Skema DB billing sesuai billing.md seksi 2; kolom state machine siap.
  - **Dependensi:** T-04
  - **Kriteria Selesai:** Migrasi berjalan; factory menghasilkan data valid; rollback bersih;
    gateway_event_id memiliki UNIQUE constraint.
  - **Tes:** Factory test; rollback; unique constraint test (duplikat gateway_event_id ditolak).
  - **Estimasi:** S | **Fase:** 1.5 | **SEC:** SEC-22

- [ ] **T-42** | Webhook handler Xendit + verifikasi signature + state machine
  - **Tujuan:** Endpoint POST /webhooks/xendit memverifikasi signature, memeriksa
    idempotency, menjalankan state machine sesuai billing.md seksi 6, menggunakan
    SELECT FOR UPDATE saat transisi status.
  - **Dependensi:** T-41
  - **Kriteria Selesai:** Semua 10 langkah billing.md seksi 6 diimplementasi;
    semua kasus BLT-01..BLT-09 lulus; sumber kebenaran hanya webhook (bukan redirect).
  - **Tes:** TC-26 (replay), TC-27 (nominal tidak cocok), TC-28 (signature salah),
    TC-29 (replay attack), TC-30 (race redirect vs webhook).
  - **Estimasi:** L | **Fase:** 1.5 | **SEC:** SEC-22..SEC-25

- [ ] **T-43** | Entitlement check server-side untuk fitur Premium
  - **Tujuan:** Middleware atau service yang mengecek status subscription aktif di setiap
    request ke fitur Premium; tidak mengandalkan cookie atau session.
  - **Dependensi:** T-41
  - **Kriteria Selesai:** Vendor Free yang mengakses fitur Premium -> redirect/403;
    cache entitlement maks 5 menit di Redis [ASUMSI]; vendor Premium -> 200.
  - **Tes:** Feature test: vendor Free -> 403/redirect; vendor Premium -> 200.
  - **Estimasi:** M | **Fase:** 1.5 | **SEC:** SEC-25

- [ ] **T-44** | Rekonsiliasi langganan terjadwal + alert ketidakcocokan
  - **Tujuan:** Job harian yang membandingkan status subscription di Xendit API vs DB;
    kirim alert jika ada ketidakcocokan.
  - **Dependensi:** T-42
  - **Kriteria Selesai:** Job terdaftar di scheduler (03:00 WIB); alert terkirim jika
    status tidak cocok; hasil dicatat di audit_logs tanpa PII.
  - **Tes:** Unit test: mock Xendit API response; simulasi ketidakcocokan -> alert terpicu.
  - **Estimasi:** M | **Fase:** 1.5 | **SEC:** SEC-25
```

---

## Matriks Referensi Silang Baru

| Task | Security Control | Test Case | Billing Test |
|------|-----------------|-----------|--------------|
| T-37 | SEC-11..SEC-14 | TC-21, TC-22, TC-23 | — |
| T-38 | SEC-32, SEC-33, SEC-37, SEC-38 | TC-39 | — |
| T-39 | SEC-10 | IDOR-10 | — |
| T-40 | SEC-15, SEC-16, SEC-17 | Manual | — |
| T-41 | SEC-22 | — | BLT-01..BLT-09 |
| T-42 | SEC-22..SEC-25 | TC-26..TC-30 | BLT-01..BLT-09 |
| T-43 | SEC-25 | Feature test entitlement | — |
| T-44 | SEC-25 | Unit test mock | — |

---

## Dokumen Terkait

| Dokumen | Seksi Relevan |
|---------|--------------|
| `security.md` | SEC-08..SEC-51 (semua kontrol yang dirujuk tugas di atas) |
| `authorization-matrix.md` | IDOR-01..IDOR-14 (target tes T-06, T-39) |
| `billing.md` | Seksi 2, 6, 7 (skema DB dan webhook handler untuk T-41..T-44) |
| `testing-strategy.md` | TC-21..TC-30, TC-39 (test cases untuk T-37, T-38, T-42) |
| `deployment-ops.md` | Seksi 4 hardening VPS (panduan T-40) |
