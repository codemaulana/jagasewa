# deployment-ops.md — JagaSewa

> Dokumen ini mencakup prosedur deployment, hardening VPS, backup, pemantauan, dan runbook insiden untuk platform JagaSewa.

---

## 1. Environment

| Environment | Branch Git | URL | DB | Tujuan |
|---|---|---|---|---|
| local | `feature/*` atau `main` | `localhost:8000` | SQLite / MySQL lokal | Development |
| staging | `main` | `staging.jagasewa.id` [ASUMSI] | MySQL / PostgreSQL staging | Validasi sebelum prod |
| production | `main` (tag release) | `jagasewa.id` | MySQL / PostgreSQL prod | Live |

> [!IMPORTANT]
> Deploy ke production **harus** dari tagged release, bukan langsung dari `main` branch. [ASUMSI]

---

## 2. Langkah Deploy

### 2.1 Deployment di Cloud Hosting cPanel (Jagoan Hosting) — Environment Nyata

Karena JagaSewa berjalan di **Cloud Hosting Next Gen cPanel (Jagoan Hosting)** tanpa akses root VPS/Supervisor, alur deploy dan manajemen proses disesuaikan:

```bash
# Di terminal SSH cPanel (sebagai user 'sdncirac' di direktori project)
cd /home/sdncirac/jagasewa
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan down
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

#### Pengganti Supervisor di cPanel (Cron Job):
Tambahkan entri Cron Job di cPanel (*cPanel > Cron Jobs*):
```cron
* * * * * php /home/sdncirac/jagasewa/artisan schedule:run > /dev/null 2>&1
```
Cron job ini berjalan setiap menit untuk menjalankan:
1. `AutoCancelPendingBookingsJob` (setiap 30 menit)
2. `AutoDeleteDocumentsJob` (harian pukul 02:00 WIB)
3. Queue Worker: `php artisan queue:work --stop-when-empty` (memproses antrean yang ada lalu berhenti, ramah RAM cPanel)

### 2.2 Deployment di VPS Mandiri (Alternatif / Target Skala Besar) [ASUMSI]

```bash
# Di server production VPS (sebagai user non-root 'deploy')
git pull origin main
composer install --no-dev --optimize-autoloader
npm run build
php artisan down
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
sudo supervisorctl restart jagasewa-worker:*
```

### 2.2 Zero-Downtime Deploy (target) [ASUMSI]

> [!TIP]
> Gunakan Envoyer atau deploy script berbasis symlink. Pendekatan atomic symlink:
> 1. Build artefak baru di direktori `releases/<timestamp>/`
> 2. Jalankan `composer install`, `npm run build`, cache config/route/view di sana
> 3. Jalankan `php artisan migrate --force`
> 4. Atomik: `ln -sfn releases/<timestamp> current`
> 5. Restart PHP-FPM worker (`sudo supervisorctl restart jagasewa-worker:*`)
>
> Rollback cukup dengan memindahkan symlink `current` ke direktori release sebelumnya.

---

## 3. Langkah Rollback

```bash
# Jika deploy gagal
php artisan down
git checkout <previous-tag>
composer install --no-dev
php artisan migrate:rollback --step=1  # hanya jika migrasi baru perlu dibatalkan
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
sudo supervisorctl restart jagasewa-worker:*
```

> [!WARNING]
> Selalu test migrasi rollback di staging sebelum deploy ke prod (sesuai DoD di `tasks.md`).

---

## 4. Hardening VPS

### 4.1 Firewall (UFW)

| Port | Source | Action | Keterangan |
|---|---|---|---|
| 22 / 2222 (SSH) | IP developer saja [ASUMSI] | ALLOW | Ubah ke non-standard port, misal 2222 [ASUMSI] |
| 80 (HTTP) | Cloudflare IP ranges | ALLOW | Redirect ke HTTPS |
| 443 (HTTPS) | Cloudflare IP ranges | ALLOW | Traffic utama |
| Semua lain | Any | DENY | Default deny |

> [!NOTE]
> List Cloudflare IP ranges selalu berubah. Ikuti dokumentasi resmi: <https://www.cloudflare.com/ips/>

### 4.2 SSH Hardening

- Nonaktifkan root login: `PermitRootLogin no`
- Gunakan SSH key only: `PasswordAuthentication no`
- Nonaktifkan `X11Forwarding`
- Buat user `deploy` dengan sudo **terbatas** — hanya boleh menjalankan `supervisorctl`, bukan full sudo [ASUMSI]

### 4.3 Update Otomatis

- Aktifkan `unattended-upgrades` untuk security patches [ASUMSI: server Ubuntu/Debian]
- Review minor/major update secara manual sebelum diterapkan ke production

### 4.4 User Database

- Buat user DB dengan hak minimum: `SELECT`, `INSERT`, `UPDATE`, `DELETE` pada tabel aplikasi saja
- **Jangan** gunakan root DB user untuk koneksi aplikasi
- Buat user terpisah untuk backup (hak `SELECT` saja)

### 4.5 Web Server Hardening (.htaccess di cPanel / Nginx di VPS)

#### A. Pada Cloud Hosting cPanel (Apache / LiteSpeed):
Gunakan konfigurasi pada file `public/.htaccess` atau menu cPanel (*Select PHP Version > Options*):
```apache
# Sembunyikan versi PHP
Header always unset X-Powered-By
Header unset X-Powered-By

# Header Keamanan
Header always set X-Frame-Options "DENY"
Header always set X-Content-Type-Options "nosniff"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

# Batas upload diatur via .user.ini atau cPanel PHP Options:
# upload_max_filesize = 6M
# post_max_size = 8M
# expose_php = Off
```

#### B. Pada VPS Mandiri (Nginx):
```nginx
# Sembunyikan versi server
server_tokens off;

# Batas ukuran upload (sedikit di atas batas upload 5 MB)
client_max_body_size 6M;

# Timeout
client_body_timeout 30s;
send_timeout 30s;
```

---

## 5. Backup

| Data | Frekuensi | Metode | Enkripsi | Lokasi | Retensi |
|---|---|---|---|---|---|
| Database | Harian pukul 01:00 WIB | `mysqldump` → kompresi ZIP berpassword | Password-protected ZIP AES-256 (atau GPG) | S3/Backblaze bucket terpisah dari aplikasi | 30 hari |
| File `.env` | Manual setiap ada perubahan | Simpan di secrets manager / offline vault | AES-256 | Secrets manager / Vault | Sesuai kebijakan internal |
| Storage S3 aplikasi | Otomatis | Backblaze B2 lifecycle & versioning | SSE provider | Same provider | 30 hari versioning |

### Uji Restore Berkala

> [!CAUTION]
> Jika restore belum pernah diuji, backup **tidak bisa diandalkan**. Lakukan uji restore minimal setiap bulan. [ASUMSI]

Prosedur uji restore:

1. Download backup terbaru dari S3
2. Dekripsi dengan GPG key
3. Restore ke DB staging kosong
4. Jalankan `php artisan test` terhadap data restore
5. Verifikasi record terbaru ada (cek timestamp)
6. Catat tanggal dan hasil di log internal tim

---

## 6. Pemantauan & Alert

| Metrik | Tool [ASUMSI] | Threshold Alert | Aksi |
|---|---|---|---|
| Uptime website | UptimeRobot / Better Uptime | Downtime > 1 menit | Notif WA / email ke PIC teknis |
| Error rate (5xx) | Sentry | > 5% dalam 5 menit | Alert PIC teknis |
| Disk usage VPS | Netdata / custom cron | > 80% | Alert PIC teknis |
| Queue backlog | Laravel Horizon / custom | Queue > 100 jobs menunggu [ASUMSI] | Alert PIC teknis |
| Webhook tidak diterima | Custom job | Order pending > 30 menit tanpa webhook | Alert PIC teknis |
| Login gagal berulang | Audit log monitor | > 10 gagal / IP / 5 menit | Alert + auto-block [ASUMSI] |
| DB connection error | Sentry | Any | Alert PIC teknis |

---

## 7. Runbook Insiden

### RB-01: Serangan Trafik (DDoS)

1. Cek Cloudflare dashboard — apakah sudah terdeteksi dan dimitigasi?
2. Aktifkan **Under Attack Mode** di Cloudflare (JS challenge semua pengunjung)
3. Jika trafik masih tembus: pertimbangkan block negara sumber di Cloudflare WAF
4. Periksa Nginx access log untuk pola serangan
5. Jika server tidak responsif: restart PHP-FPM dan Nginx
6. Catat insiden: waktu mulai, durasi, dampak, mitigasi yang dilakukan
7. Post-mortem dalam 24 jam setelah insiden selesai

---

### RB-02: Kebocoran Data

> [!CAUTION]
> Insiden kebocoran data memiliki implikasi hukum. Jangan hapus log atau bukti apapun sebelum konsultasi ahli hukum.

1. **Isolasi segera**: nonaktifkan akses bucket S3 yang terdampak (block public, rotasi credentials)
2. **Identifikasi scope**: periksa `audit_logs` untuk booking / penyewa terdampak
3. Notifikasi internal tim dalam **1 jam**
4. Simpan semua log dan bukti (jangan hapus)
5. 🔍 Konsultasi ahli hukum: kewajiban pemberitahuan ke Kominfo/BSSN dan subjek data
6. Notifikasi penyewa terdampak sesuai saran hukum
7. Post-mortem: root cause, timeline, perbaikan sistem

---

### RB-03: Webhook Billing Gagal

1. Cek dashboard Xendit — apakah webhook dikirim? Error apa yang tercatat?
2. Cek Laravel log untuk error di webhook handler
3. Jika Xendit konfirmasi pembayaran sukses tapi status DB salah: manual aktivasi via admin panel
4. Rekonsiliasi: jalankan `SubscriptionReconciliationJob` secara manual
5. Catat insiden dan perbaiki webhook handler agar tidak berulang

---

### RB-04: Disk Hampir Penuh

1. Cek disk usage:
   ```bash
   df -h
   ```
2. Identifikasi penyebab: log Laravel (`storage/logs`), storage sementara, foto yang belum terhapus
3. Jalankan pembersihan log (backup dulu sebelum hapus di production):
   ```bash
   php artisan log:clear
   ```
4. Jalankan `AutoDeleteDocumentsJob` secara manual jika ada file yang menumpuk
5. Jika storage S3 penuh: cek bucket policy dan versioning; hapus versi lama yang tidak diperlukan
6. Long-term: tambah kapasitas disk VPS atau pastikan semua media sudah dialihkan ke S3

---

### RB-05: Akun Vendor Diambil Alih

1. Nonaktifkan akun vendor segera via admin panel (`is_active = false`)
2. Invalidate semua session aktif vendor tersebut
3. Periksa `audit_log`: apa yang dilakukan pelaku selama mengakses akun?
4. Reset password dan kirim link reset ke email vendor asli
5. Review perubahan data: item, booking, blacklist yang diubah selama periode pengambilalihan
6. Rollback perubahan berbahaya jika diperlukan dan memungkinkan
7. Notifikasi vendor asli dan dokumentasikan insiden secara lengkap
