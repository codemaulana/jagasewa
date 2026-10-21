# Panduan Hardening Cloud Hosting cPanel & Cloudflare Proxy (T-40)

> Dokumen panduan implementasi untuk hardening lingkungan hosting cPanel Jagoan Hosting dan konfigurasi Cloudflare Edge Proxy.

---

## 1. Konfigurasi Server Lokal Repository (Telah Selesai)

Berkas-berkas berikut telah dikonfigurasi di repository:

1. **`public/.htaccess`**:
   - `LimitRequestBody 6291456`: Membatasi ukuran body request maksimal **6 MB** di level web server Apache/LiteSpeed sebelum mencapai PHP.
   - Blokir akses ke file sensitif (`.env*`, `.git`, `composer.json`, `composer.lock`, `package.json`, `artisan`, `phpunit.xml`, `.user.ini`) dengan **HTTP 403 Forbidden**.
   - Blokir metode HTTP `TRACE` dan `TRACK`.
   - Menonaktifkan directory listing (`Options -Indexes -MultiViews`) dan `ServerSignature Off`.
   - Header keamanan HTTP & strip `X-Powered-By`.
2. **`public/.user.ini`**:
   - `upload_max_filesize = 6M`
   - `post_max_size = 8M`
   - `memory_limit = 256M`
   - `expose_php = Off`
3. **`public/robots.txt`**:
   - Melindungi rute vendor dan admin dari web crawling indexer.

---

## 2. Langkah Pengaturan di cPanel Jagoan Hosting

Buka dashboard cPanel akun `sdncirac`:

### A. Pengaturan PHP Options
1. Cari menu **Select PHP Version** (atau **MultiPHP INI Editor**).
2. Pilih tab **Options** pada versi **PHP 8.4**.
3. Sesuaikan nilai berikut:
   - `upload_max_filesize` → `6M`
   - `post_max_size` → `8M`
   - `memory_limit` → `256M`
   - `expose_php` → **Off** (hapus centang)
   - `max_execution_time` → `60`
4. Klik **Save / Apply**.

---

## 3. Langkah Pengaturan Cloudflare Edge Proxy (Orange Cloud)

Untuk menyembunyikan IP origin hosting (`101.50.1.83`), mengaktifkan proteksi DDoS L3/L4/L7, dan mempercepat static assets:

### A. Konfigurasi DNS di Cloudflare
1. Tambahkan domain `jagasewa.web.id` ke akun Cloudflare (Free Plan sudah memadai).
2. Pada menu **DNS > Records**:
   - **Type:** `A` | **Name:** `@` (atau `jagasewa.web.id`) | **IPv4 address:** `101.50.1.83` | **Proxy status:** **Proxied (Awan Oranye)**
   - **Type:** `CNAME` | **Name:** `www` | **Target:** `jagasewa.web.id` | **Proxy status:** **Proxied (Awan Oranye)**
3. Ganti Nameserver di registrar domain Anda dengan 2 Nameserver yang diberikan oleh Cloudflare.

### B. Konfigurasi SSL/TLS di Cloudflare
1. Buka menu **SSL/TLS > Overview**:
   - Pilih mode enkripsi: **Full (strict)** (karena server cPanel Jagoan Hosting sudah memiliki sertifikat SSL AutoSSL/Let's Encrypt yang aktif).
2. Buka menu **SSL/TLS > Edge Certificates**:
   - **Always Use HTTPS:** `ON`
   - **Minimum TLS Version:** `TLS 1.2`
   - **Opportunistic Encryption:** `ON`
   - **Automatic HTTPS Rewrites:** `ON`

### C. Konfigurasi Keamanan (WAF & DDoS)
1. Buka menu **Security > WAF**:
   - Aktifkan **Cloudflare Managed Ruleset** (jika tersedia).
2. Buka menu **Security > Bots**:
   - Aktifkan **Bot Fight Mode** (`ON`).
3. Mode Darurat Serangan (DDoS):
   - Jika terjadi traffic flood, aktifkan **Under Attack Mode** dari dashboard utama Cloudflare (memberikan tantangan JS otomatis kepada pengunjung).

---

## 4. Checklist Pengujian & Verifikasi Pra-Rilis

| No | Pengujian | Perintah / Cara Uji | Hasil yang Diharapkan |
|---|---|---|---|
| 1 | Proteksi `.env` | `curl -I https://jagasewa.web.id/.env` | HTTP 403 Forbidden |
| 2 | Proteksi `.git` | `curl -I https://jagasewa.web.id/.git/HEAD` | HTTP 403 Forbidden |
| 3 | Sembunyikan versi PHP | `curl -I https://jagasewa.web.id/` | Tidak ada header `X-Powered-By` |
| 4 | Proxy Cloudflare aktif | `curl -I https://jagasewa.web.id/` | Memuat header `server: cloudflare` & `cf-ray` |
| 5 | Batas upload > 5MB | Form upload booking dengan file 6.5 MB | Ditolak oleh server (HTTP 413 / 422) |
| 6 | IP Asli diakses Laravel | Akses via Cloudflare Proxy | Terbaca IP klien asli (via `trustProxies` di `bootstrap/app.php`) |
