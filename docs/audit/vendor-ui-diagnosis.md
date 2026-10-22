# Laporan Diagnosis Visual & Aksesibilitas Dashboard Vendor JagaSewa
**Dokumen:** `docs/audit/vendor-ui-diagnosis.md`  
**Fase:** FASE 0 — Diagnosis Visual & Kontras (Tanpa Mengubah Kode)  
**Waktu Audit:** 22 Oktober 2026  
**Auditor:** AI Systems Architect & UI/UX Specialist  

---

## 1. Ringkasan Eksekutif

Pemeriksaan mendalam terhadap antarmuka Vendor Dashboard JagaSewa mengungkap **kerusakan visual kritis pada hierarki warna dan rasio kontras**. Masalah ini disebabkan oleh **tabrakan antara dua sistem tema yang tidak sinkron**:
1. **Engine Bawaan Filament v5:** Mengaktifkan class `dark` secara otomatis berdasarkan preferensi OS klien (`prefers-color-scheme: dark`) karena panel tidak dikonfigurasi dengan `->darkMode(false)`.
2. **Design Tokens Kustom JagaSewa (`app.css`):** Memaksa latar belakang kanvas `body` bernilai krem terang `#FAF9F6` secara permanen tanpa menyediakan palet gelap yang kohesif.

Akibatnya, elemen-elemen teks Filament yang mengadopsi class `dark:text-white` atau `dark:text-gray-400` dirender di atas kanvas krem terang, menghasilkan rasio kontras ekstrem serendah **1.05:1** (hampir tidak terbaca / *invisible text*). Selain itu, logo hitam dirender di atas topbar hitam (`1.06:1`), dan terjadi redundansi informasi paket langganan di tiga lokasi berbeda.

---

## 2. Akar Masalah Kontras Visual

### 2.1 Judul Halaman "Dashboard" Putih di Atas Latar Krem (1.05:1)
- **Akar Masalah:**
  Pada [VendorPanelProvider.php](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L55), panel hanya dikonfigurasi dengan:
  ```php
  ->defaultThemeMode(ThemeMode::Light)
  ```
  Di Filament, pemanggilan `defaultThemeMode(ThemeMode::Light)` **tidak menonaktifkan dark mode engine**. Filament tetap menyuntikkan skrip JavaScript deteksi OS:
  ```javascript
  if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
  }
  ```
  Ketika browser/OS pengguna berada pada mode gelap, elemen root `<html>` langsung disematkan class `dark`.
  Header bawaan halaman Filament (`fi-header-heading`) menggunakan class:
  ```html
  <h1 class="fi-header-heading text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">
      Dashboard
  </h1>
  ```
  Karena class `dark` aktif, warna judul dipaksa menjadi `text-white` (`#FFFFFF`). Di sisi lain, [resources/css/app.css:155](file:///c:/DataCoding/my-saas/resources/css/app.css#L155) memaksakan:
  ```css
  body { background-color: var(--bg-base); /* #FAF9F6 */ }
  ```
  **Dampak:** Teks putih `#FFFFFF` menempel di atas kanvas krem `#FAF9F6`. Rasio kontras terukur **1.05:1** (ambang batas minimum WCAG AA adalah 3.0:1 untuk judul teks besar). Teks judul menjadi sama sekali tidak terbaca.

### 2.2 Teks Menu Sidebar Pucat di Atas Latar Krem (2.41:1)
- **Akar Masalah:**
  Ketika class `dark` aktif pada `<html>`, sidebar Filament mengasumsikan latarnya berwarna abu-abu gelap (`bg-gray-900`). Link navigasi sidebar yang tidak aktif diberi warna `dark:text-gray-400` (`#9CA3AF`).
  Namun karena container sidebar ter-override oleh latar kanvas terang aplikasi, teks abu-abu terang `#9CA3AF` tersebut jatuh di atas latar krem `#FAF9F6`.
  **Dampak:** Rasio kontras terukur **2.41:1**, jauh di bawah standar WCAG AA (4.5:1 untuk teks normal). Teks menu navigasi tampak pudar dan sulit dibaca oleh pengguna.

### 2.3 Logo Hitam di Atas Header Hitam (1.06:1)
- **Akar Masalah:**
  Topbar Filament menggunakan class bawaan `fi-topbar bg-white dark:bg-gray-900 border-b dark:border-gray-800`. Saat class `dark` aktif, topbar berubah menjadi hitam pekat (`#111827`).
  Pada [VendorPanelProvider.php:40](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L40), logo diset menggunakan:
  ```php
  ->brandLogo(fn () => asset('jagasewa_logo1.png'))
  ```
  File `jagasewa_logo1.png` adalah logo teks JagaSewa bertinta hitam (`#111111` / `#000000`).
  **Dampak:** Logo hitam pekat diletakkan di atas background header `#111827`. Rasio kontras **1.06:1**, membuat identitas brand tenggelam sepenuhnya.

### 2.4 Redundansi Informasi Paket (Fragmentasi Shell)
- Lencana paket toko saat ini muncul secara repetitif di 3 tempat sekaligus:
  1. Sidebar menu item: *Paket & Kuota Toko* dengan badge PRO/FREE ([VendorPanelProvider.php:67-74](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L67-L74)).
  2. Topbar header badge: Pill lencana *Paket Pro / Free* ([resources/views/filament/vendor/layout/topbar-badge.blade.php](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/layout/topbar-badge.blade.php#L23-L34)).
  3. Menu profil dropdown: *Paket Pro — Kelola* ([VendorPanelProvider.php:77-80](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L77-L80)).
  4. Widget dasbor: Kartu khusus *Status Langganan* ([store-link-and-plan-widget.blade.php:112](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php#L112)).
- Tombol *Ekspor Laporan CSV* dijadikan menu sidebar utama tingkat atas ([VendorPanelProvider.php:62-66](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L62-L66)), padahal semestinya merupakan aksi kontekstual di dalam halaman *Laporan & Analitik*.

---

## 3. Konflik Dua Sistem Tema yang Bertabrakan

Dua sistem tema yang saling menimpa secara inkonsisten terdeteksi di dalam repositori:

| Karakteristik | Sistem 1: Filament Engine + Tailwind Dark Engine | Sistem 2: JagaSewa Semantic Light Theme |
|---|---|---|
| **Sumber Utama** | Core Framework Filament v5 (`vendor/filament/*`) | `resources/css/app.css` |
| **Mekanisme Pemicu** | `window.matchMedia('(prefers-color-scheme: dark)')` menyuntikkan `<html class="dark">` | Variabel `:root` dan `@theme` statis |
| **Warna Latar Utama** | `dark:bg-gray-900` (#111827) / `dark:bg-zinc-900` | `--bg-base: #FAF9F6`, `--surface: #FFFFFF` |
| **Warna Teks Utama** | `dark:text-white` (#FFFFFF) / `dark:text-gray-400` (#9CA3AF) | `--text-main: #141414`, `--text-secondary: #6B6860` |
| **Implementasi di View** | Penggunaan sporadis `dark:*` pada widget dasbor | Penggunaan class `.bg-surface`, `.text-main`, `.border-subtle` |

### Daftar File Tempat Tabrakan Terjadi:
1. [app/Providers/Filament/VendorPanelProvider.php](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L52-L55):
   - Baris 52 menyuntikkan `resources/css/app.css` ke dalam `<head>`.
   - Baris 55 menyetel `->defaultThemeMode(ThemeMode::Light)` tanpa mematikan dark mode (`->darkMode(false)`), membiarkan JavaScript Filament menambahkan class `dark`.
2. [resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php):
   - Memadukan class Tailwind default `dark:bg-zinc-900`, `dark:text-white`, `dark:border-zinc-800` dengan palet terang.
3. [resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php):
   - Menggunakan 25+ deklarasi `dark:bg-zinc-*` dan `dark:text-*` yang mengasumsikan permukaan kartu berwarna hitam/zinc.
4. [resources/views/filament/vendor/pages/reports.blade.php](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/pages/reports.blade.php):
   - Tombol tab filter periode dan tombol unduh menggunakan utility hardcoded `bg-primary hover:bg-neutral-800 text-white`.
5. [resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php#L91):
   - Badge PRO menggunakan `bg-success text-white`, tombol upgrade menggunakan `bg-primary hover:bg-neutral-800 text-white`.
6. [app/Filament/Vendor/Pages/StoreSettings.php](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Pages/StoreSettings.php#L190-L285):
   - Tiga blok pemberitahuan HTML string berisi warna hardcoded `bg-zinc-50`, `text-zinc-700`, `bg-emerald-600`, `text-white`.

---

## 4. Inventaris Nilai Hardcoded di Folder UI Vendor

Pemeriksaan `git grep` pada direktori `app/Filament/Vendor` dan `resources/views/filament/vendor` mendeteksi kemunculan nilai hardcoded berikut:

| Lokasi File & Baris | Nilai Hardcoded | Klasifikasi Pelanggaran | Alasan & Dampak |
|---|---|---|---|
| [StoreSettings.php:190-192](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Pages/StoreSettings.php#L190-L192) | `border-zinc-200`, `bg-zinc-50`, `text-zinc-700`, `text-zinc-900`, `text-zinc-500`, `bg-emerald-600`, `text-white` | Palette Tailwind Generik & Text White | Mengabaikan token `--surface-subtle`, `--text-muted`, dan `--success`. Tombol upgrade memakai hijau emerald hardcoded. |
| [StoreSettings.php:232](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Pages/StoreSettings.php#L232) | `hover:bg-neutral-800`, `text-white` | Hardcoded Neutral & White | Nilai hover dan teks tombol tidak terikat pada `--primary` / `--primary-text`. |
| [StoreSettings.php:284](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Pages/StoreSettings.php#L284) | `hover:bg-neutral-800`, `text-white` | Hardcoded Neutral & White | Duplikasi tombol upgrade Pro dengan kelas hardcoded. |
| [store-link-and-plan-widget.blade.php:5](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php#L5) | `bg-amber-50`, `dark:bg-amber-950/40`, `text-amber-900`, `dark:text-amber-200` | Palet Amber & Dark Mode Sporadis | Tidak menggunakan token `--warning-bg` dan `--warning`. |
| [store-link-and-plan-widget.blade.php:44](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php#L44) | `bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800` | Hardcoded Surface & Dark Mode | Permukaan kartu tidak memakai token `--surface` dan memicu background gelap. |
| [store-link-and-plan-widget.blade.php:74](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php#L74) | `bg-zinc-900 text-white hover:bg-zinc-800 border-zinc-900 dark:bg-zinc-100 dark:text-zinc-900` | Hardcoded Zinc & Inverted Text | Tombol salin tautan membalikkan warna secara drastis saat class `dark` terpicu. |
| [onboarding-checklist-widget.blade.php:3](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php#L3) | `bg-white dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800` | Hardcoded Zinc Card | Kartu checklist onboarding terbagi antara putih dan zinc-900 gelap. |
| [onboarding-checklist-widget.blade.php:25](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php#L25) | `bg-zinc-900 dark:bg-white` | Hardcoded Progress Bar | Progress bar menggunakan warna hitam-putih kontras tinggi yang terbalik. |
| [onboarding-checklist-widget.blade.php:32, 56, 80](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php#L32) | `bg-emerald-50/40`, `dark:bg-emerald-950/20`, `bg-zinc-50 dark:bg-zinc-950` | Palet Emerald & Zinc Hardcoded | Kotak tahapan menggunakan 3 kombinasi warna tidak standar. |
| [reports.blade.php:39, 97](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/pages/reports.blade.php#L39) | `bg-primary hover:bg-neutral-800 text-white` | Hardcoded Text White & Neutral | Menggunakan utilitas `text-white` langsung alih-alih `--primary-text`. |
| [reports.blade.php:52-73](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/pages/reports.blade.php#L52-L73) | `bg-primary text-white border-primary` | Hardcoded Tab State | Tombol preset filter tanggal menyematkan `text-white` manual. |
| [vendor-booking-calendar.blade.php:91](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php#L91) | `bg-success text-white` | Hardcoded Text White | Badge PRO menggunakan hijau tua solid dengan teks putih tebal. |
| [top-rented-items-widget.blade.php:84](file:///c:/DataCoding/my-saas/resources/views/filament/vendor/widgets/top-rented-items-widget.blade.php#L84) | `bg-primary text-white` | Hardcoded Badge Circle | Lingkaran nomor peringkat produk menggunakan `text-white` statis. |

---

## 5. Pengukuran Rasio Kontras WCAG 2.1 (AA)

### 5.1 Status Alat Pengujian Otomatis
> [!NOTE]
> **Keterangan Kejujuran Alat:** Paket CLI `axe-core` maupun Google `lighthouse` **tidak terpasang** di lingkungan server lokal proyek saat ini (`npm error: could not determine executable to run`).  
> Oleh karena itu, pengujian rasio kontras dilakukan secara akurat dan objektif menggunakan formula matematis standar **W3C WCAG 2.1 Relative Luminance** (\(L = 0.2126 R + 0.7152 G + 0.0722 B\)) yang dihitung langsung melalui script verifikasi.

### 5.2 Standar Target WCAG AA
- **Teks Biasa (<18pt atau <14pt bold):** Minimal **4,5:1**
- **Teks Besar (≥18pt atau ≥14pt bold) & Elemen UI/Ikon:** Minimal **3,0:1**
- **Enhanced Contrast (WCAG AAA):** Minimal **7,0:1**

### 5.3 Tabel Komparasi Kontras: Masalah Eksisting vs Target Token Baru

| Elemen UI & Pasangan Warna | Warna Teks | Warna Latar | Rasio Kontras | Status Evaluasi WCAG AA |
|---|---|---|:---:|---|
| **Eksisting: Judul "Dashboard" (Class Dark Aktif)** | `#FFFFFF` | `#FAF9F6` (Kanvas) | **1.05:1** | ❌ **GAGAL TOTAL** (Target: ≥3.0:1) |
| **Eksisting: Logo Hitam di Header Topbar Gelap** | `#111111` | `#111827` (Header) | **1.06:1** | ❌ **GAGAL TOTAL** (Target: ≥3.0:1) |
| **Eksisting: Teks Menu Sidebar (`gray-400` di atas Kanvas)** | `#9CA3AF` | `#FAF9F6` (Kanvas) | **2.41:1** | ❌ **GAGAL** (Target: ≥4.5:1) |
| **Eksisting: Teks Menu Sidebar (`zinc-400` di atas Kanvas)** | `#A1A1AA` | `#FAF9F6` (Kanvas) | **2.43:1** | ❌ **GAGAL** (Target: ≥4.5:1) |
| **Eksisting: Teks Muted Lama (`#8F8C84` di atas Kanvas)** | `#8F8C84` | `#FAF9F6` (Kanvas) | **3.19:1** | ❌ **GAGAL** untuk teks normal (<4.5:1) |
| **Eksisting: Chip Denda Terlambat (Merah di atas Hitam)** | `#D92D20` | `#111111` (Dasar) | **3.91:1** | ❌ **GAGAL** untuk teks normal (<4.5:1) |
| **Target Fase 1: Teks Utama (`--text`) di atas Kanvas (`--bg`)** | `#1B1B19` | `#F6F3EC` | **15.57:1** | ✅ **LULUS TINGKAT TERTINGGI (AAA)** |
| **Target Fase 1: Teks Utama (`--text`) di atas Kartu (`--surface`)** | `#1B1B19` | `#FFFFFF` | **17.25:1** | ✅ **LULUS TINGKAT TERTINGGI (AAA)** |
| **Target Fase 1: Teks Sekunder (`--text-muted`) di atas Kanvas (`--bg`)** | `#5F5C55` | `#F6F3EC` | **6.02:1** | ✅ **LULUS (AA)** (Melebihi syarat 4.5:1) |
| **Target Fase 1: Teks Sekunder (`--text-muted`) di atas Kartu (`--surface`)** | `#5F5C55` | `#FFFFFF` | **6.67:1** | ✅ **LULUS (AA)** (Melebihi syarat 4.5:1) |
| **Target Fase 1: Teks Putih di atas Tombol Utama (`--primary`)** | `#FFFFFF` | `#1B1B19` | **17.25:1** | ✅ **LULUS TINGKAT TERTINGGI (AAA)** |
| **Target Fase 1: Teks Gelap di atas Aksen Lime (`--accent`)** | `#1B1B19` | `#C6F432` | **13.46:1** | ✅ **LULUS TINGKAT TERTINGGI (AAA)** |
| **Target Fase 1: Status Danger Baru di atas Latar Lembut** | `#B42318` | `#FEE4E2` | **5.45:1** | ✅ **LULUS (AA)** |
| **Target Fase 1: Status Warning di atas Latar Lembut** | `#B54708` | `#FFFAEB` | **5.20:1** | ✅ **LULUS (AA)** |
| **Target Fase 1: Status Success di atas Latar Lembut** | `#067647` | `#ECFDF3` | **5.40:1** | ✅ **LULUS (AA)** |

---

## 6. Rencana Kerja Perbaikan Sistematis (Fase 1 s/d 4)

Berdasarkan diagnosis di atas, berikut alur perbaikan terstruktur yang akan dilaksanakan:

1. **FASE 1: Token dan Tema Terang Tunggal**
   - Nonaktifkan dark mode sepenuhnya di Filament via `->darkMode(false)` pada [VendorPanelProvider.php](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php).
   - Definisikan token semantik murni di `resources/css/app.css`:
     - `--bg: #F6F3EC` (kanvas hangat)
     - `--surface: #FFFFFF` (kartu & topbar)
     - `--border: #E7E5E0`
     - `--text: #1B1B19`
     - `--text-muted: #5F5C55` (kontras 6.02:1 di atas `--bg` dan 6.67:1 di atas `--surface`)
     - `--primary: #1B1B19` (teks putih)
     - `--accent: #C6F432` (teks gelap)
     - `--accent-soft: #EEF9C4`
     - Status semantik dengan latar lembut (Danger: `#B42318` di atas `#FEE4E2`, Warning: `#B54708` di atas `#FFFAEB`, Success: `#067647` di atas `#ECFDF3`).
   - Buat skrip linter / automated check untuk memastikan tidak ada deklarasi `text-white` atau nilai hex di luar file token.

2. **FASE 2: App Shell (Header, Sidebar, Navigation)**
   - Header 64px berlatar `--surface` dengan border bawah 1px (menghapus bar hitam pekat).
   - Tampilkan logo JagaSewa yang kontras tinggi di atas latar terang.
   - Sidebar 248px (collapsible ke 72px) berlatar `--bg` dengan item aktif berbentuk pil `--primary` teks putih dan item inaktif `--text-muted`.
   - Kelompokkan menu menjadi 3 seksi teratur: *Operasional*, *Pelanggan & Keamanan*, dan *Bisnis*.
   - Satu kartu ringkasan paket di bagian bawah sidebar; hapus duplikasi badge PRO/FREE di topbar dan menu user.
   - Pindahkan aksi "Ekspor Laporan CSV" menjadi tombol di halaman Laporan & Analitik.
   - Mobile: Sidebar beralih menjadi drawer halus + bottom-nav 4 item utama (*Dashboard, Booking, Kalender, Katalog*) dan tombol *Lainnya*.

3. **FASE 3: Dashboard Layout & Hierarchy**
   - PageHeader: Salam ramah + nama toko + format tanggal id-ID lengkap ("Kamis, 22 Oktober 2026") + tombol utama "Buat booking".
   - 3 Kartu KPI:
     - Tugas hari ini: Penghitung terpisah (Pengambilan, Pengembalian, Terlambat dengan chip danger lembut).
     - Pendapatan bulan ini: Angka rupiah tabular-nums dengan perbandingan bulan lalu dan sparkline riwayat.
     - Menunggu konfirmasi: Angka dan tombol aksi "Tinjau sekarang".
   - 2 Kolom Komposisi:
     - Kiri (lebar): *Agenda hari ini* (urutan prioritas: terlambat paling atas dengan tautan WhatsApp & hitung denda).
     - Kanan: Kartu *Tautan toko publik* ringkas + kartu *Kuota paket* (hanya menampilkan progress bar bila kuota terbatas).
   - Panduan memulai 1 kolom ringkas yang hilang otomatis saat 3/3 selesai.

4. **FASE 4: Verifikasi & Audit Ulang**
   - Screenshot antarmuka pada breakpoint 1440px, 768px, dan 390px.
   - Validasi kepatuhan WCAG 2.1 AA di seluruh halaman dasbor.
   - Verifikasi lint, type-check, dan test suite 408 tests tetap hijau 100%.
