# Panduan Uji Restore Database (T-35) — JagaSewa

Dokumen ini merupakan panduan operasional untuk memverifikasi integritas dan menjalankan pemulihan (*restore*) database JagaSewa dari arsip cadangan (*backup*) terenkripsi.

---

## 1. Ringkasan Sistem Backup

- **Jadwal Eksekusi:** Setiap hari pukul `01:00 WIB` via Laravel Scheduler (`routes/console.php`):
  ```php
  Schedule::command('backup:database')->dailyAt('01:00')->timezone('Asia/Jakarta');
  ```
- **Lokasi Penyimpanan:** S3-compatible remote storage (Backblaze B2 privat) pada direktori `backups/`.
- **Format File:** `backup-jagasewa-YYYY-MM-DD_HHmmss.sql.zip`
- **Metode Enkripsi:** Password-protected ZIP dengan algoritma **AES-256** (`ZipArchive::EM_AES_256`).
- **Kunci Enkripsi:** Diambil dari variabel `.env`:
  ```env
  DB_BACKUP_PASSWORD=kunci_rahasia_aes256_disini
  ```
  *(Jika kosong, sistem menggunakan fallback ke `APP_KEY`).*
- **Kebijakan Retensi:** 30 hari. File backup di remote storage yang berumur lebih dari 30 hari otomatis dibersihkan setiap kali backup harian selesai.

---

## 2. Perintah Artisan

### A. Membuat Backup Manual Kapan Saja
```bash
# Menggunakan disk default (s3) dan password dari .env
php artisan backup:database

# Menyimpan ke disk lokal untuk pengujian
php artisan backup:database --disk=local

# Menggunakan password kustom
php artisan backup:database --password=RahasiaKustom123!
```

Output yang dihasilkan:
```text
+------------------+------------------------------------------------------------+
| Attribute        | Value                                                      |
+------------------+------------------------------------------------------------+
| File             | backups/backup-jagasewa-2026-10-19_010000.sql.zip          |
| Storage Disk     | s3                                                         |
| Archive Size     | 45.20 KB                                                   |
| Tables Dumped    | 12                                                         |
| Retention Pruned | 0 file(s) > 30 days old                                    |
| Encryption       | AES-256 (Password-protected ZIP)                           |
+------------------+------------------------------------------------------------+
```

---

## 3. Prosedur Uji Restore (Langkah-demi-Langkah)

Uji restore **wajib dilakukan secara berkala** (minimal 1 bulan sekali atau sebelum rilis besar) untuk memastikan arsip backup tidak korup dan dapat dipulihkan dengan sempurna.

### Langkah 1: Siapkan Lingkungan Pengujian
Lakukan pengujian di server staging atau lingkungan lokal:
```bash
# Pastikan konfigurasi database mengarah ke database staging/uji
php artisan migrate:status
```

### Langkah 2: Jalankan Perintah Restore

#### Opsi A: Memulihkan Backup Terbaru Otomatis dari S3
```bash
php artisan backup:restore
```
Sistem akan memindai disk S3, memilih file backup terbaru secara otomatis, dan meminta konfirmasi sebelum mengeksekusi:
```text
Scanning storage disk [s3] for latest backup...
Found latest backup archive: backups/backup-jagasewa-2026-10-19_010000.sql.zip

WARNING: This will drop and overwrite existing database tables. Are you sure you want to proceed? (yes/no) [no]:
> yes
```

#### Opsi B: Memulihkan File Tertentu dari S3
```bash
php artisan backup:restore backups/backup-jagasewa-2026-10-19_010000.sql.zip
```

#### Opsi C: Memulihkan File dari Komputer Lokal
Jika Anda telah mengunduh file `.sql.zip` ke server:
```bash
php artisan backup:restore /path/to/backup-jagasewa-2026-10-19.sql.zip
```

#### Opsi D: Menjalankan Non-Interaktif (Otomatis/CI/Script)
Gunakan flag `--force`:
```bash
php artisan backup:restore --force
```

---

### Langkah 3: Verifikasi Integritas Data Pasca Restore

Setelah restore selesai, jalankan langkah verifikasi:
```bash
# 1. Jalankan test suite menyeluruh
php artisan test

# 2. Cek jumlah data kritis (Vendor, Items, Bookings)
php artisan tinker --execute="echo 'Vendors: ' . App\Models\Vendor::count() . ', Bookings: ' . App\Models\Booking::count();"
```

Jika semua tes hijau dan data cocok dengan snapshot sebelum backup, maka prosedur restore dinyatakan **LULUS (VALID)**.

---

## 4. Cara Membuka Arsip Backup Manual di Komputer (Eksternal)

Karena arsip menggunakan standar enkripsi AES-256 ZIP:
1. **Windows:** Buka file `.zip` menggunakan aplikasi **7-Zip** atau **WinRAR**, lalu masukkan `DB_BACKUP_PASSWORD`.
2. **Linux / macOS:** Gunakan perintah terminal:
   ```bash
   unzip -P "PASSWORD_ANDA" backup-jagasewa-2026-10-19_010000.sql.zip
   ```
3. File SQL di dalamnya berisi seluruh definisi tabel (`CREATE TABLE`) dan data baris (`INSERT INTO`) dengan format UTF-8 / UTF8MB4.
