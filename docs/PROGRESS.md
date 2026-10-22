# Catatan Progres & Status Audit (docs/PROGRESS.md) — JagaSewa

> **Sesi Kerja:** 22 Oktober 2026  
> **Aktivitas:** Overhaul Desain Frontend JagaSewa (Fase A: Design Token, Fase B: Komponen Dasar, Fase C: Layout, Fase D: Rewrite Halaman & Final Verification).

---

## 1. Ringkasan Status Proyek
- [x] **Fase A — Design Token:** Master token terpusat di `resources/css/app.css`, zero raw hex di seluruh views/app/config.
- [x] **Fase B — Komponen Dasar:** 18 komponen UI modular di `resources/views/components/ui/` (Button, Input, Select, DatePicker, Badge, Card, Table, Modal, ConfirmDialog, Toast, Tabs, EmptyState, Skeleton, PageHeader, Pagination, UploadImage, Alert, Textarea).
- [x] **Fase C — Layout:** Panel vendor (collapsible sidebar, drawer mobile, bottom nav 5-menu, topbar plan badge) dan layout portal penyewa terpadu (`x-layouts.renter`).
- [x] **Fase D — Rewrite Halaman:** 
  - Detail Barang & Booking Form (`public/items/show.blade.php`, Livewire booking form & calendar).
  - Kalender Sewa Vendor (`filament/vendor/pages/vendor-booking-calendar.blade.php`).
  - Halaman Billing & Finish (`vendor/billing/index.blade.php`, `finish.blade.php`).
  - Etalase Katalog Toko (`public/vendor/catalog.blade.php`).
  - Halaman Sukses Booking (`public/bookings/success.blade.php`).
  - Landing Page Utama (`welcome.blade.php`).
  - Halaman Error Global (`resources/views/errors/404.blade.php`, `500.blade.php`).
- [x] **Verifikasi Akhir:**
  - Grep emoji unicode di seluruh views: **0 match (Bersih Total)**.
  - Grep hex color di luar `resources/css/app.css`: **0 match (Bersih Total)**.
  - Grep "lorem" / "TODO": **0 match**.
  - Pint linting: **Passed**.
  - Vite asset build: **Passed (1.88s)**.
  - PHPUnit test suite: **390 passed (2.053 assertions)**.

---

## 2. Detail Implementasi per Fase

### 2.1 Fase A: Design Token (SELESAI & TERVERIFIKASI)
- Mendefinisikan `@theme` Tailwind CSS v4 dan `:root` CSS variables di `resources/css/app.css`:
  - Latar: `#FAF9F6` (`--bg-base`), Permukaan: `#FFFFFF` (`--surface`), Permukaan Halus: `#F5F4F0` (`--surface-subtle`)
  - Border: `#E7E5E0` (`--border-subtle`), Border Hover: `#D5D3CC` (`--border-hover`)
  - Teks: `#141414` (`--text-main`), Teks Sekunder: `#6B6860` (`--text-secondary`), Teks Muted: `#8F8C84` (`--text-muted`)
  - Primer: `#111111` (`--primary`), Hover: `#262626`, Active: `#000000`, Teks: `#FFFFFF` (`--primary-text`)
  - Aksen Lime: `#C6F432` (`--accent-lime`), Hover: `#B8E825`, Teks: `#141414` (`--accent-lime-text`), Halus: `#F3FDE0`
  - Status: Sukses `#067647`, Peringatan `#B54708`, Bahaya `#D92D20`, Info `#026AA2`
  - Tipografi: Plus Jakarta Sans (fallback system-ui), JetBrains Mono, `tabular-nums` untuk angka/harga/tanggal.
  - Radii: 6px (`rounded-sm`), 8px (`rounded-md`), 12px (`rounded-lg`), 9999px (`rounded-full`).
  - Bayangan: 1 level halus (`shadow-subtle: 0 1px 2px 0 rgba(0, 0, 0, 0.05)`).
- Membersihkan seluruh kode hex hardcoded dari views, PHP helper, config, dan `composer.json`.

### 2.2 Fase B: Komponen Dasar (SELESAI & TERVERIFIKASI)
18 komponen reusable di `resources/views/components/ui/`:
1. `button.blade.php`: Varian primer (hitam), sekunder, ghost, bahaya, whatsapp, aksen; touch target min 44px (`h-11`); state loading & disabled.
2. `input.blade.php`: Input teks dengan label terhubung, pesan error spesifik bahasa Indonesia, hint, prefix/suffix.
3. `select.blade.php`: Select dropdown kustom SVG chevron, border token, touch target 44px.
4. `textarea.blade.php`: Textarea token dengan auto-grow/resize vertikal.
5. `date-picker.blade.php`: Date picker dengan ikon kalender SVG dan angka tabular.
6. `badge.blade.php`: Badge status semantik (Pending, Disetujui, Aktif, Selesai, Batal, Denda) dengan titik warna (dot SVG), **tanpa emoji**.
7. `card.blade.php`: Kontainer permukaan bersih `bg-surface border-border-subtle shadow-subtle`.
8. `table.blade.php`: Tabel data responsif: tabel penuh di desktop, berubah menjadi kartu terpisah di mobile (375px).
9. `modal.blade.php`: Dialog modal dan drawer slide-up mobile dengan trapping focus & Escape key.
10. `confirm-dialog.blade.php`: Modal konfirmasi aksi kritis dengan ikon SVG peringatan dan tombol Batal/Konfirmasi.
11. `toast.blade.php`: Sistem notifikasi mengambang dengan auto-dismiss 4.5s dan integrasi event global Alpine.
12. `tabs.blade.php`: Navigasi tab underline & pills dengan badge tabular.
13. `empty-state.blade.php`: State kosong yang tenang dengan ikon SVG dan slot tombol aksi.
14. `skeleton.blade.php`: Shimmer loading state untuk teks, avatar, dan kartu.
15. `page-header.blade.php`: Header halaman terpadu dengan breadcrumb dan action buttons.
16. `pagination.blade.php`: Paginasi ramah aksesibilitas dengan ringkasan data dan touch target 44px.
17. `upload-image.blade.php`: Dropzone unggah foto dengan validasi file (JPG/PNG/WEBP <5MB), drag-and-drop, dan preview.
18. `alert.blade.php`: Inline alert dengan palet semantik lembut.

### 2.3 Fase C: Layout (SELESAI & TERVERIFIKASI)
1. **Vendor Panel Layout (`app/Providers/Filament/VendorPanelProvider.php`):**
   - Sidebar collapsible di desktop (`->sidebarCollapsibleOnDesktop()`).
   - Drawer mobile off-canvas bawaan Filament.
   - Mobile bottom navigation bar 5-menu (`filament.vendor.layout.bottom-nav`) di hook `PanelsRenderHook::BODY_END` dengan touch target >= 44px.
   - Topbar plan badge & quick store link (`filament.vendor.layout.topbar-badge`) di hook `PanelsRenderHook::USER_MENU_BEFORE`.
   - Injeksi font Plus Jakarta Sans dan token CSS Vite.
2. **Portal Penyewa Layout (`resources/views/components/layouts/renter.blade.php`):**
   - Header toko terverifikasi dengan logo, nama vendor, breadcrumbs, dan tombol kembali touch target 44px.
   - Menghapus ketergantungan runtime CDN Tailwind.
   - Integrasi toast global `<x-ui.toast />`.
   - White-label support pada footer (menyembunyikan watermark pada vendor Pro).

### 2.4 Fase D: Rewrite Halaman (SELESAI & TERVERIFIKASI)
1. **Detail Barang & Form Booking (`public/items/show.blade.php`, `booking-form.blade.php`, `item-availability-calendar.blade.php`):**
   - Label KTP dan selfie terhubung eksplisit dengan `for` dan `id` input file.
   - Kalender ketersediaan: touch target tanggal minimal 44px, pola striping jeda buffer WCAG AA (`buffer-zone-pattern`), navigasi bulan >= 44px.
   - Validasi form spesifik bahasa Indonesia ("NIK harus 16 digit angka").
   - Format mata uang rupiah integer dengan `tabular-nums`.
2. **Kalender Sewa Vendor (`filament/vendor/pages/vendor-booking-calendar.blade.php`):**
   - Kisi kalender 7-kolom dibungkus kontainer scroll horizontal (`min-w-[640px] sm:min-w-full`) sehingga sel tanggal tidak terjepit di mobile 375px.
   - Tombol navigasi bulan ("Bulan Sebelumnya", "Bulan Ini", "Bulan Berikutnya") touch target >= 44px (`min-h-[44px] min-w-[44px]`).
   - Filter barang dropdown touch target >= 44px.
   - Seluruh badge status menggunakan token semantik tanpa emoji.
3. **Halaman Billing Vendor (`vendor/billing/index.blade.php`, `finish.blade.php`):**
   - Kartu paket harga bulanan, 3 bulan, dan 1 tahun responsif di 375px.
   - Progress bar kuota barang aktif dan blacklist menggunakan track border token.
   - Tabel perbandingan fitur Free vs Pro dan tabel riwayat transaksi responsif.
   - Layar konfirmasi pembayaran (`finish.blade.php`) menggunakan token hangat, ikon SVG sukses, dan polling entitlement reaktif.
   - Seluruh simbol checkmark unicode U+2713 digantikan ikon SVG checkmark crisp.
4. **Etalase Katalog Vendor (`public/vendor/catalog.blade.php`):**
   - Ditambahkan dimensi eksplisit (`width` & `height`), `loading="lazy"`, dan `decoding="async"` untuk mencegah Cumulative Layout Shift (CLS).
   - Kartu katalog menggunakan `<x-ui.card>`, tag harga tabular, dan tombol detail touch target 44px.
5. **Landing Page Utama (`welcome.blade.php`):**
   - Seluruh emoji pada kartu kategori (`⭐`, `📷`, `🌸`, `👑`) dan daftar fitur pilar digantikan label profesional dan SVG icon.
   - Responsif di 375px, 768px, 1280px, dan 1536px.
6. **Halaman Error Global (`resources/views/errors/404.blade.php`, `500.blade.php`):**
   - Dibuat halaman 404 (Halaman Tidak Ditemukan) dan 500 (Kendala Server) dengan nuansa hangat, copy bahasa Indonesia yang tenang, tombol kembali touch target 44px, dan 0 emoji.

---

## 3. Hasil Verifikasi Akhir (Verification Suite)

| Kriteria Pengujian | Perintah / Metode | Target | Hasil Nyata | Status |
|-------------------|-------------------|--------|-------------|:------:|
| **Grep Emoji Unicode** | Scan `[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{2B50}]` di seluruh views | 0 match | **0 match** | **LULUS** |
| **Grep Hex di Luar Token** | Scan `#([0-9a-fA-F]{3,8})\b` di luar `app.css` | 0 match | **0 match** | **LULUS** |
| **Grep AI Slop (Lorem/TODO)** | Scan `lorem ipsum`, `TODO` di production views | 0 match | **0 match** | **LULUS** |
| **Vite Production Build** | `npm run build` | Build bersih | `✓ built in 1.88s` | **LULUS** |
| **PHP Pint Code Style** | `./vendor/bin/pint --test` | 0 style issues | `passed` | **LULUS** |
| **PHPUnit Test Suite** | `php artisan test` | Semua test lulus | **390 passed (2.053 assertions)** | **LULUS** |

---

## 4. Ringkasan Sebelum vs Sesudah (Per Halaman)

| Halaman / Komponen | Sebelum Refaktor | Sesudah Refaktor | Hal yang Sengaja Tidak Diubah |
|--------------------|-------------------|------------------|-------------------------------|
| **Katalog Vendor (`/b/{vendor}`)** | CDN Tailwind runtime, header duplikat, gambar tanpa ukuran (potensi CLS), tanpa layout terpadu. | Menggunakan `<x-layouts.renter>`, token Vite, gambar dengan `width`/`height`/`loading="lazy"`, kartu seragam. | Kontrak route, query pagination, dan relasi vendor-item. |
| **Detail Barang (`/b/{vendor}/{item}`)** | Label file KTP tidak terhubung ke input (gagal a11y), striping buffer kontras rendah. | Label `for`/`id` terhubung, pattern striping WCAG AA, touch target tanggal 44px, galeri foto modular. | Logika `BookingCalendarService`, formula kalkulasi denda, dan payload Livewire. |
| **Kalender Vendor (`/vendor/calendar`)** | Grid 7-kolom hancur/terjepit di 375px mobile, touch target navigasi bulan <40px, status campuran. | Kontainer scroll horizontal responsif di mobile (`min-w-[640px]`), touch target navigasi 44px (`h-11`), badge token. | Isolasi query vendor, logika navigasi bulan, dan mapping agenda harian. |
| **Halaman Billing (`/vendor/billing`)** | Card warna warni, emoji checkmark unicode U+2713, tabel tidak responsif di layar kecil. | Card bernuansa netral hangat dengan aksen lime hemat, SVG checkmark crisp, tabel scrollable responsif, 0 hex. | State lifecycle subscription, idempotensi checkout, webhook verifier, dan model `Subscription`. |
| **Landing Page (`/`)** | Terdapat emoji (`⭐`, `📷`, `🌸`, `👑`), inline style hex lama. | Menggunakan utility token Tailwind v4 murni, 0 emoji, visual showcase terstandarisasi. | Animasi tirai panggung GSAP/ScrollTrigger dan copywriting inti produk. |
| **Halaman Error (`/errors/404, 500`)** | Menggunakan halaman bawaan framework tanpa token proyek. | Halaman kustom ramah pengguna, gaya netral hangat, tombol touch target 44px. | Kode status HTTP (404 dan 500). |

---

## 5. Gelombang 1: Inti Operasional Vendor (SELESAI & TERVERIFIKASI)

- [x] **Beranda Vendor:**
  - Kartu tugas hari ini dinamis: Pengambilan hari ini, Pengembalian hari ini, Terlambat/overdue.
  - Pendapatan bulan ini: Kalkulasi otomatis omzet integer rupiah per vendor.
  - Menunggu konfirmasi: Tracking jumlah booking baru pending.
  - Barang paling sering disewa: Mode dasar dengan item #1 & teaser Pro edukatif untuk vendor Free, tabel peringkat analitik 5 besar untuk vendor Pro.
- [x] **Katalog Barang:**
  - Field operasional baru: `category`, `item_code`, `unit_status` (tersedia/disewa/laundry/perbaikan), `condition`, dan paket harga sewa 3 hari (`package_price_3d`).
  - Multi-foto galeri dengan drag-and-drop ordering dan upload privat S3 (maks 5 foto Free, maks 8 foto Pro).
  - Pembatasan kuota paket Free: 15 barang aktif (barang ke-16 ke atas menjadi read-only tanpa menghapus data).
  - Tab riwayat transaksi sewa per barang di infolist unit.
- [x] **Alur Booking & Finansial:**
  - Aksi `record_payment`: Pencatatan DP dan Pelunasan penuh dengan nominal integer rupiah, catatan rekening, dan bukti transfer privat dalam transaksi DB dengan `lockForUpdate()`.
  - Komponen visual `activity-timeline`: Linimasa tahapan booking dari dibuat, disetujui, pembayaran DP/lunas, serah-terima, dikembalikan, hingga closed.
- [x] **Kalender Operasional & Smart Buffer:**
  - Switcher tampilan Bulan vs Minggu.
  - Dukungan jeda buffer per kategori barang (`category_buffer_settings`).
  - Indikator status buffer aktif (Pro) vs teaser konfigurasi kategori (Free).
- [x] **Serah-Terima (Handover) & Pengembalian:**
  - Aksi penyerahan unit (`activate`): Checklist kondisi awal, metode pengambilan (pickup/instant courier/ekspedisi), nomor resi/tracking, dan upload multi-foto kondisi sebelum sewa.
  - Aksi pengembalian & penyelesaian (`settle`): Checklist pengembalian, kalkulator denda & kerusakan real-time, dan dokumentasi multi-foto bukti kerusakan.
  - Komponen `inspection-photos`: Komparasi visual kondisi unit sebelum dan sesudah sewa.
- [x] **Test Suite Gelombang 1:**
  - `tests/Feature/OperationalVendorCoreTest.php` (6 tests, 58 assertions lulus 100%).

---

## 6. Gelombang 2: Kepercayaan dan Uang (SELESAI & TERVERIFIKASI)

- [x] **Penyewa / Pelanggan Vendor:**
  - Resource baru `RenterResource` (`/vendor/renters`) dengan isolasi vendor ketat.
  - Tracking profil pelanggan, total sewa, total transaksi, dan penanda reliabilitas (`trusted`, `normal`, `caution`, `problematic`).
  - Fitur memo/catatan internal vendor yang dapat diedit langsung via modal aksi.
  - Rekalkulasi metrik reliabilitas otomatis (`recalculateMetrics()`).
  - Cek blacklist otomatis saat booking baru dibuat dari etalase publik (`BookingForm`).
- [x] **Blacklist Bersama (Shared Blacklist):**
  - Cek database blacklist lintas internal dan komunitas dengan pelindung kuota (Free: 10 cek/bulan, Pro: tanpa batas).
  - Pelaporan ke blacklist bersama eksklusif untuk vendor Pro dengan unggah bukti pendukung ke storage S3 privat.
  - Anonimisasi dan masking identitas (`62812****7890`, `3201**********01`) tanpa membocorkan identitas pelapor lain.
  - Mekanisme sanggahan (*dispute*) entri blacklist.
- [x] **Denda dan Deposit:**
  - Kalkulator keterlambatan lanjutan: tarif per hari dan tarif per jam prorata dengan jam toleransi gratis (*grace hours*).
  - Kalkulasi gabungan keterlambatan, kerusakan (*damage*), dan kehilangan (*loss*).
  - Pengaturan aturan denda otomatis di halaman pengaturan toko (`StoreSettings`) untuk vendor Pro.
- [x] **Dokumen Resmi PDF & WhatsApp Templates:**
  - Generator PDF standar (PDF 1.4 binary) murni dalam PHP tanpa dependensi composer pihak ketiga.
  - Download & cetak **Invoice (PDF)** dan **Surat Perjanjian Sewa (PDF)** dengan proteksi hak akses Pro.
  - Template pesan WhatsApp dinamis dengan variabel `{nama}`, `{barang}`, `{tanggal}`, `{total}`, `{rekening_toko}` yang otomatis membuka tautan `wa.me`.
- [x] **Test Suite Gelombang 2:**
  - `tests/Feature/Gelombang2TrustAndMoneyTest.php` (7 tests, 91 assertions lulus 100%).


