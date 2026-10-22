# Laporan Audit Frontend (01-frontend-audit.md) — JagaSewa

> **Tanggal Audit:** 22 Oktober 2026  
> **Auditor:** Senior Full-Stack Engineer & Product Designer  
> **Fokus:** Halaman Publik Penyewa (`/`, `/b/{slug}`, `/b/{slug}/{item}`, `/b/success/{ulid}`), Dashboard Vendor (Filament + Livewire), dan Halaman Billing (`/vendor/billing`).  
> **Prinsip Utama:** Light mode, mobile-first, zero "AI Slop" (tanpa emoji, tanpa gradien dekoratif/glow, tanpa copy generik, konsistensi design tokens).

---

## 1. Inventaris Arsitektur Frontend

### 1.1. Stack yang Terdeteksi
- **Backend & Server Framework:** Laravel 13 (`laravel/framework: ^13.0`), PHP 8.4
- **Admin/Vendor UI Engine:** Filament v5 (`filament/filament: ^5.0`), Livewire v4 (`livewire/livewire: ^4.0`)
- **Asset Bundler & Styling:** Vite 7 (`vite: ^7.0.7`), Tailwind CSS v4 (`@tailwindcss/vite: ^4.0.0`, `tailwindcss: ^4.0.0`)
- **Client Scripting:** Alpine.js (bawaan Filament & Livewire), Axios (`axios: ^1.11.0`), GSAP 3.12.5 + ScrollTrigger (hanya di `welcome.blade.php`)
- **Database & State Driver:** MySQL 8 via Database driver (`CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`)
- **Penyimpanan Berkas:** S3-compatible Backblaze B2 (bucket privat) via AWS S3 SDK v3

### 1.2. Struktur Folder Antarmuka
```
resources/
  css/
    app.css                                  # Stylesheet utama & token CSS Tailwind v4
  js/
    app.js                                   # Entrypoint Vite JavaScript (hanya import bootstrap.js)
    bootstrap.js                             # Inisialisasi Axios
  views/
    components/
      ui/                                    # Komponen UI Reusable
        alert.blade.php                      # Alert info/warning/danger/success
        badge.blade.php                      # Badge status semantik (5 varian)
        button.blade.php                     # Button serbaguna (primary, secondary, whatsapp, dll.)
        card.blade.php                       # Kontainer kartu standar
    filament/
      vendor/
        bookings/
          settlement-summary.blade.php       # Infolist ringkasan settlement denda/deposit
        pages/
          vendor-booking-calendar.blade.php  # Kalender sewa visual sisi vendor
        widgets/
          onboarding-checklist-widget.blade.php # Checklist onboarding vendor baru
          store-link-and-plan-widget.blade.php  # Kartu etalase publik & kuota paket
    livewire/
      item-availability-calendar.blade.php   # Kalender ketersediaan interaktif publik
      public/
        booking-form.blade.php               # Form pengajuan sewa & KYC identitas publik
      vendor/
        booking-settlement-form.blade.php    # Form input denda keterlambatan & kerusakan
    public/
      bookings/
        success.blade.php                    # Halaman sukses booking & konfirmasi WhatsApp
      items/
        show.blade.php                       # Halaman detail barang, foto, buffer, kalender, form
      partials/
        design-tokens.blade.php              # Definisi token CSS & konfigurasi Tailwind runtime
      vendor/
        catalog.blade.php                    # Etalase katalog produk per vendor (/b/{vendor_slug})
    vendor/
      billing/
        index.blade.php                      # Halaman manajemen paket, checkout Pro, & invoice pending
    welcome.blade.php                        # Landing page utama JagaSewa (1.490 baris)
```

### 1.3. Pemetaan Rute & Halaman per Peran Pengguna
| Peran | URL / Rute | Handler Controller / Page | Deskripsi Tampilan |
|---|---|---|---|
| **Tamu (Guest)** | `GET /` | `welcome.blade.php` | Landing page platform, value proposition, etalase demo |
| **Tamu (Guest)** | `GET /vendor/login` | Filament Login (`VendorPanelProvider`) | Form login vendor |
| **Tamu (Guest)** | `GET /vendor/register` | `App\Filament\Vendor\Pages\Auth\Register` | Form registrasi vendor baru (nama toko, slug, WA, password) |
| **Penyewa (Renter)** | `GET /b/{vendor_slug}` | `ItemController@catalog` | Katalog publik etalase barang milik vendor tertentu |
| **Penyewa (Renter)** | `GET /b/{vendor_slug}/{item_slug}` | `ItemController@show` | Detail barang, foto galeri, buffer, kalender, dan form KYC |
| **Penyewa (Renter)** | `GET /b/success/{ulid}` | `ItemController@bookingSuccess` | Layar konfirmasi kode booking dan tombol chat `wa.me` ke vendor |
| **Vendor Free** | `GET /vendor` | Filament Dashboard (`VendorPanelProvider`) | Dashboard 3 widget: link toko, ringkasan sewa, checklist onboarding |
| **Vendor Free** | `GET /vendor/items` | `Items\Pages\ListItems` | Tabel barang milik vendor (kuota maks 5 barang aktif) |
| **Vendor Free** | `GET /vendor/items/create` | `Items\Pages\CreateItem` | Form tambah barang (maks 5 foto, tolak barang aktif ke-6) |
| **Vendor Free** | `GET /vendor/items/{ulid}/edit` | `Items\Pages\EditItem` | Form edit barang (maks 5 foto, tolak aktivasi jika kuota penuh) |
| **Vendor Free** | `GET /vendor/bookings` | `Bookings\Pages\ListBookings` | Tabel booking penyewa dengan filter status dan barang |
| **Vendor Free** | `GET /vendor/bookings/{ulid}` | `Bookings\Pages\ViewBooking` | Detail pesanan, KTP signed URL, aksi Setujui/Tolak/Sewa/Kembali |
| **Vendor Free** | `GET /vendor/calendar` | `VendorBookingCalendar` | Kalender bulanan visual jadwal sewa dan blok jeda buffer |
| **Vendor Free** | `GET /vendor/store-settings` | `StoreSettings` | Form profil toko; bagian template WhatsApp terkunci (CTA Upgrade) |
| **Vendor Free** | `GET /vendor/blacklist-entries` | `BlacklistEntries\Pages\ListBlacklistEntries` | Tabel blacklist internal vendor (maks 5 entri) |
| **Vendor Free** | `GET /vendor/billing` | `BillingController@index` | Ringkasan kuota terpakai, pilihan paket Pro (Bulanan, 3 Bulan, 1 Tahun) |
| **Vendor Pro** | `GET /vendor/reports/export` | `BillingController@exportReports` | Download laporan transaksi sewa dalam format CSV (`vendor.pro` middleware) |
| **Vendor Pro** | `GET /vendor/store-settings` | `StoreSettings` | Bagian kustomisasi template pesan WhatsApp terbuka untuk diedit |
| **Vendor Pro** | `GET /vendor/items/create` | `Items\Pages\CreateItem` | Form tambah barang tanpa batas kuota barang & kuota hingga 8 foto |
| **Platform Admin** | `GET /admin` | Filament Admin Dashboard | Pusat kendali admin platform |
| **Platform Admin** | `GET /admin/vendors` | `App\Filament\Resources\Vendors\Pages\ListVendors` | Manajemen daftar vendor (nama, slug, email, WA, toggle status) |

### 1.4. Pemanggilan API & Komunikasi Jaringan di Frontend
1. `GET /api/items/{ulid}/availability?month=YYYY-MM`: Diambil oleh komponen kalender untuk menampilkan hari terpesan (*booked*) dan jeda steril (*buffer*).
2. `POST /booking/upload`: Endpoint unggah dokumen identitas KTP & selfie (dibatasi throttle 5 per 15 menit).
3. `GET /vendor/identity-documents/{ulid}/signed-url`: Menghasilkan temporary signed URL Backblaze S3 (TTL 60 menit) untuk pratinjau dokumen penyewa.
4. `GET /vendor/blacklist/check`: Pengecekan status nomor WA/NIK penyewa pada blacklist lokal vendor.
5. `POST /vendor/billing/checkout`: Pembuatan invoice dan tautan hosted payment link Mayar.id.
6. `POST /vendor/billing/cancel-pending`: Pembatalan order tagihan tertunda (*pending payment*).
7. `POST /livewire-3bdb8cba/update`: Transportasi state internal Livewire untuk interaktivitas kalender dan formulir.

---

## 2. Hasil Audit Frontend & Penilaian Kualitas (Skor 1–5)

### 2.1. Konsistensi Visual (Warna, Tipografi, Spacing, Radius, Bayangan)
- **Skor: 2 / 5 (Kurang Konsisten)**
- **Bukti & Temuan:**
  1. **Pemisahan Palet Warna Ekstrem:**
     - Pada `resources/views/public/partials/design-tokens.blade.php:5-72`, sistem menetapkan palet: Warm Neutral (`#F2F2F0`), Hitam Solid (`#111111`), dan Single Accent Lime (`#86F04C`).
     - Namun pada `resources/views/welcome.blade.php:25-38` dan `resources/views/welcome.blade.php:58-62`, landing page menggunakan palet Emas Mewah / Serif (`brand.gold: #A27B5C`, `brand.goldLight: #E8D5C4`, `brand.cream: #F6F4EE`, `brand.dark: #141312`, `brand.border: #C8B7A6`).
     - Sementara pada `resources/views/vendor/billing/index.blade.php:9-26`, tampilan menggunakan palet default Tailwind (`gray-50`, `gray-800`, `emerald-600`, `amber-50`, `teal-700`) tanpa menyentuh token sistem sama sekali.
  2. **Nilai Hardcoded yang Melanggar Token:**
     - `resources/views/public/items/show.blade.php:46`: Menggunakan nilai hardcoded `bg-[#86F04C] text-[#0A0A0A]` alih-alih utility class token `.badge-accent-lime`.
     - `resources/views/public/items/show.blade.php:161`: Warna teks warning hardcoded `text-[#92400e]`.
     - `resources/views/public/items/show.blade.php:301`: Tombol CTA bawah memakai hardcoded `bg-[#111111] hover:bg-[#262626] active:bg-black`.
     - `resources/views/public/vendor/catalog.blade.php:51`: Hardcoded `bg-[#86F04C] text-[#0A0A0A]`.
     - `resources/views/public/vendor/catalog.blade.php:92`: Hardcoded `bg-emerald-50 text-emerald-700 border-emerald-200`.
     - `resources/views/public/bookings/success.blade.php:66`: Hardcoded warna tombol WhatsApp `bg-[#25D366] hover:bg-[#20ba5a] active:bg-[#1da851]`.
     - `resources/views/livewire/public/booking-form.blade.php:163`: Hardcoded `bg-[#111111] hover:bg-[#262626] active:bg-black`.
  3. **Inkonsistensi Tipografi:**
     - Design tokens menetapkan `Inter Tight` sebagai satu-satunya jenis font sans-serif (`design-tokens.blade.php:67`).
     - `resources/views/welcome.blade.php:10-13`: Mengunduh dan menerapkan font luxury serif `Cormorant Garamond` dan `Plus Jakarta Sans`.
     - `resources/views/vendor/billing/index.blade.php:8`: Tidak memuat font `Inter Tight`, melainkan mengandalkan sistem font default CDN Tailwind.
  4. **Radius & Bayangan:**
     - `resources/views/vendor/billing/index.blade.php:31, 37`: Menggunakan radius `rounded` (4px), tidak konsisten dengan standar antarmuka JagaSewa yaitu `rounded-xl` (12px).
     - `resources/views/vendor/billing/index.blade.php:44`: Menggunakan gradasi tebal `bg-gradient-to-r from-emerald-600 to-teal-700` yang secara eksplisit dilarang dalam panduan anti-slop.

---

### 2.2. Komponen Duplikat atau Serupa yang Belum Disatukan
- **Skor: 2 / 5 (Tinggi Duplikasi)**
- **Bukti & Temuan:**
  1. **Header Navigasi Publik:**
     - `resources/views/public/vendor/catalog.blade.php:14-26`
     - `resources/views/public/items/show.blade.php:15-27`
     - `resources/views/public/bookings/success.blade.php:14-26`
     - Ketiga berkas menulis ulang elemen sticky header 14px, logo image dengan inline style identik (`style="height: 36px; width: auto; max-width: 160px; object-fit: contain;"`), dan badge navigasi katalog secara terpisah.
  2. **Komponen UI yang Tersedia Namun Tidak Digunakan (Abandoned Reusable Components):**
     - Komponen `<x-ui.button>` (`resources/views/components/ui/button.blade.php:1-51`) lengkap dengan varian (`primary`, `secondary`, `ghost`, `danger`, `whatsapp`, `accent`) sama sekali **tidak pernah dipanggil** di seluruh halaman aplikasi. Semua tombol ditulis dengan tag `<button>` dan tag `<a>` raw HTML berisi belasan utility class berulang.
     - Komponen `<x-ui.card>` (`resources/views/components/ui/card.blade.php:1-13`) tidak pernah digunakan.
     - Komponen `<x-ui.alert>` (`resources/views/components/ui/alert.blade.php:1-57`) tidak digunakan pada form booking (`booking-form.blade.php:13`) maupun billing (`billing/index.blade.php:31, 37`).
     - Komponen `<x-ui.badge>` (`resources/views/components/ui/badge.blade.php:1-92`) hanya digunakan pada satu tempat (`success.blade.php:39`), sementara badge status lainnya ditulis inline.

---

### 2.3. Halaman Tanpa State Loading, Kosong (Empty), atau Galat (Error)
- **Skor: 3 / 5 (Cukup, Perlu Penyempurnaan)**
- **Bukti & Temuan:**
  1. **Kalender Ketersediaan Publik:**
     - Pada `resources/views/livewire/item-availability-calendar.blade.php:11-31`, saat pengguna menekan tombol ganti bulan (`wire:click="goToNextMonth"` / `goToPreviousMonth`), **tidak terdapat skeleton loading** atau indikator visual `wire:loading` pada kisi grid kalender. Pengguna tidak mendapat umpan balik saat terjadi latensi jaringan.
  2. **Formulir Pengajuan Sewa:**
     - `resources/views/livewire/public/booking-form.blade.php:12-21`: Sudah memiliki banner galat validasi `$errorMessage`.
     - Tombol submit memiliki feedback `wire:loading` teks "Memproses..." (`booking-form.blade.php:165-171`), namun saat mengunggah foto KTP dan selfie via `wire:model="ktpPhoto"` tidak ada indikator progress bar pengunggahan berkas.
  3. **Etalase Katalog Publik:**
     - `resources/views/public/vendor/catalog.blade.php:175-185`: Memiliki state kosong rapi ("Belum Ada Barang Rental").
  4. **Kalender Booking Vendor:**
     - `resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php:98-240`: Jika dalam bulan tersebut tidak ada satupun pesanan, kalender hanya menampilkan grid kosong tanpa infobox ramah yang menerangkan bahwa tidak ada jadwal sewa di bulan tersebut.

---

### 2.4. Uji Responsif (375px, 768px, 1280px) & Ukuran Target Sentuh (Touch Target)
- **Skor: 3 / 5 (Lolos di Ponsel Tertentu, Masalah pada Touch Target)**
- **Bukti & Temuan:**
  1. **Target Sentuh Di Bawah 44px (<44px):**
     - `resources/views/livewire/item-availability-calendar.blade.php:79`: Tombol tanggal kalender memiliki kelas `min-h-[38px]`. Ini melanggar standar WCAG 2.5.5 dan token JagaSewa `--touch-target-min: 44px` (`design-tokens.blade.php:68`).
     - `resources/views/livewire/item-availability-calendar.blade.php:14, 25`: Tombol panah ganti bulan berukuran `w-8 h-8` (32×32px), sangat sulit ditekan dengan jempol pengguna pada layar 375px (iPhone SE/Mini).
     - `resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php:10, 29`: Tombol navigasi kalender vendor berukuran `p-2` dengan ikon 16px, menghasilkan area sentuh ~34px.
  2. **Pecah Layout pada Layar 375px (Mobile Portrait):**
     - `resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php:99`: Menggunakan `grid grid-cols-7`. Pada lebar 375px (dikurangi padding kontainer ~32px), lebar tiap kolom hari hanya ~49px. Teks badge pesanan dan emoji pelindung buffer (`🛡️`) di baris 149 mengalami teks terpotong (*text clipped/truncated*) parah dan tidak dapat dibaca oleh vendor di HP. Tidak tersedia tampilan alternatif *list/agenda view* khusus mobile.
  3. **Layar 1280px (Desktop):**
     - Halaman publik (`catalog.blade.php`, `show.blade.php`, `success.blade.php`) dibatasi kaku dengan `max-w-xl mx-auto` (maksimal 576px). Pada layar desktop 1280px ke atas, antarmuka terlihat seperti kolom sempit di tengah layar dengan margin abu-abu kosong yang sangat masif di kiri dan kanan, tanpa memanfaatkan tata letak 2 kolom (foto di kiri, formulir di kanan).

---

### 2.5. Aksesibilitas (WCAG AA Compliance)
- **Skor: 2 / 5 (Perlu Perbaikan Signifikan)**
- **Bukti & Temuan:**
  1. **Label Formulir Tidak Terhubung ke Input:**
     - Pada `resources/views/livewire/public/booking-form.blade.php:125, 133`:
       ```blade
       <label class="block text-xs font-medium text-neutral-700 mb-1.5">Foto KTP atau identitas resmi</label>
       <input type="file" wire:model="ktpPhoto" accept="image/*" ...>
       ```
       Label tidak memiliki atribut `for="ktpPhoto"` dan input tidak memiliki `id="ktpPhoto"`. Ketika penyewa mengetuk teks label, dialog pemilih berkas tidak terbuka.
  2. **Kontras Warna yang Gagal Standar:**
     - `resources/views/livewire/item-availability-calendar.blade.php:85`: Status buffer menggunakan teks abu-abu `text-neutral-400` di atas pola garis diagonal `bg-buffer-stripes` (`#F2F2F0` / `#E5E5E3`). Rasio kontras terukur ~2.3:1 (jauh di bawah batas minimum WCAG AA 4.5:1).
  3. **Penghilangan Focus Ring Keyboard:**
     - `resources/views/public/items/show.blade.php:70`: Thumbnail foto memiliki `focus:outline-none` tanpa indikator fokus pengganti.
     - `resources/views/livewire/public/booking-form.blade.php:31, 40, 50, 68, 76`: Menggunakan `focus:outline-none focus:ring-1 focus:ring-neutral-900`, meniadakan outline aksesibilitas bawaan browser bagi pengguna navigasi keyboard/tab.
  4. **Semantik ARIA:**
     - Grid kalender pada `item-availability-calendar.blade.php:58` hanya berupa tumpukan `<div>` tanpa `role="grid"`, `role="row"`, `role="gridcell"`, maupun status `aria-selected="true"`. Pembaca layar (screen reader) tidak dapat mengidentifikasi bahwa elemen tersebut adalah kisi penanggalan.

---

### 2.6. Pola "AI Slop", Penggunaan Emoji, dan Copy Generik
- **Skor: 1 / 5 (Pelanggaran Berat Terhadap Panduan Proyek)**
- **Bukti & Lokasi Persis Pelanggaran Emoji:**
  > Berdasarkan aturan `docs/global-rules.md` Baris 16: *"Tidak ada emoji di UI sama sekali (termasuk badge status, tombol, judul, empty state). Gunakan satu set ikon konsisten (Lucide atau Phosphor), satu ketebalan stroke."*
  
  Daftar pelanggaran emoji di kode produksi:
  1. `resources/views/filament/vendor/pages/vendor-booking-calendar.blade.php:149`: `🛡️ {{ $event['item_name'] }} (Buffer)` (Emoji perisai pada badge kalender)
  2. `resources/views/filament/vendor/widgets/onboarding-checklist-widget.blade.php:7`: `<span>🚀 Panduan Memulai Toko Rental</span>` (Emoji roket pada judul widget)
  3. `resources/views/filament/vendor/widgets/store-link-and-plan-widget.blade.php:79`: `💎 PRO` (Emoji berlian pada badge paket)
  4. `app/Filament/Vendor/Pages/StoreSettings.php:171`: `💎 Fitur Kustom Pesan WhatsApp Terkunci.` (Emoji berlian di form settings)
  5. `app/Providers/Filament/VendorPanelProvider.php:67`: `(auth('vendor')->user()?->vendor?->isPro() ? '💎 Paket Pro' : '🟢 Paket Free').' — Kelola'` (Emoji di menu akun Filament)
  6. `resources/views/vendor/billing/index.blade.php:20`: `💎 Paket Pro Aktif`
  7. `resources/views/vendor/billing/index.blade.php:24`: `🟢 Paket Free`
  8. `resources/views/vendor/billing/index.blade.php:47`: `<span class="text-2xl">💎</span>` (Emoji berlian banner Pro)
  9. `resources/views/vendor/billing/index.blade.php:65`: `<span class="text-2xl">⏳</span>` (Emoji jam pasir invoice pending)
  10. `resources/views/vendor/billing/index.blade.php:75`: `💳 Lanjutkan Pembayaran` (Emoji kartu kredit tombol checkout)
  11. `resources/views/vendor/billing/index.blade.php:151, 186, 220`: `⏳ Menunggu Pembayaran` (Emoji jam pasir tabel harga)
  12. `resources/views/welcome.blade.php:499`: `⭐ Paling Laris`
  13. `resources/views/welcome.blade.php:539`: `📷 Pro Gear`
  14. `resources/views/welcome.blade.php:579`: `🌸 Brokat Modern Halus`
  15. `resources/views/welcome.blade.php:619`: `👑 Warisan Nusantara`

- **Tanda Tampilan "Ala AI" (AI Slop UI Patterns):**
  - **Data Palsu Hardcoded di Landing Page:** Pada `resources/views/welcome.blade.php:470-750`, terdapat 8 kartu barang rental fiktif lengkap dengan foto stok Unsplash (Sony Alpha, Fujifilm X-T5, Kebaya Brokat, Beskap Solo, Genshin Raiden Shogun, Frieren, iPhone 15 Pro, Tenda Dome Eiger) yang di-hardcode secara statis dalam HTML, alih-alih menampilkan barang riil dari database vendor.
  - **Glow & Gradien Berlebihan:** `resources/views/vendor/billing/index.blade.php:44` (`bg-gradient-to-r from-emerald-600 to-teal-700`).
  - **Layout Monoton:** Pola kartu bertumpuk dengan list icon centang repetitif di ketiga tier harga billing (`billing/index.blade.php:138-142, 175-177, 209-211`).

---

### 2.7. Dead Code, Aset Tak Terpakai, dan File Raksasa (>300 Baris)
- **Skor: 2 / 5 (Beban File Sangat Besar)**
- **Bukti & Temuan:**
  1. **File Sangat Besar (>300 Baris) yang Harus Dipecah:**
     - `resources/views/welcome.blade.php`: **1.490 baris** dalam satu berkas tunggal! Berisi inline CSS, konfigurasi JS tailwind runtime, GSAP script, 8 kartu barang hardcoded, infobox arsitektur ACID database, diagram alur, dan FAQ.
     - `resources/views/public/items/show.blade.php`: **328 baris**. Berisi header, galeri, pricing card, buffer card, kalender Livewire, formulir booking Livewire, accordion FAQ, dan bottom CTA.
     - `app/Services/Billing/SubscriptionReconciliationService.php`: **322 baris**. Menggabungkan logika jaringan HTTP Mayar/Xendit, pessimistic row lock DB, dan transisi state machine.
  2. **Dead Code & Aset Bundler Tidak Dipakai (Vite Bypass):**
     - `npm run build` menghasilkan bundle CSS `public/build/assets/app-*.css` (86 KB) dan JS `public/build/assets/app-*.js` (51 KB).
     - Namun, **tidak ada satupun berkas blade** di `resources/views` yang memuat arahan `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
     - Seluruh berkas blade publik dan billing malah mengimpor `<script src="https://cdn.tailwindcss.com"></script>` secara mandiri dari CDN. Hasil build Vite 100% menjadi dead code di produksi.
  3. **Berkas JS Kosong:**
     - `resources/js/app.js` (hanya 22 byte) dan `resources/js/bootstrap.js` (127 byte) mengimpor Axios tetapi tidak pernah di-link ke frontend publik.

---

### 2.8. Validasi Form Sisi Klien & Kualitas Pesan Galat
- **Skor: 3.5 / 5 (Cukup Baik di Sisi Server, Minim di Sisi Klien)**
- **Bukti & Temuan:**
  1. **Validasi Klien:**
     - `resources/views/livewire/public/booking-form.blade.php:29-55`: Input nama, nomor WA, dan alamat tidak memiliki atribut validasi HTML5 (`required`, `minlength`, atau `pattern`). Validasi baru berjalan setelah tombol submit ditekan dan request dikirim ke backend Livewire.
     - Tombol submit dinonaktifkan secara reaktif menggunakan `@disabled(! $agreePrivacy)` (`booking-form.blade.php:161`), memandu penyewa agar wajib menyetujui ketentuan privasi KYC terlebih dahulu.
  2. **Pesan Galat:**
     - Pesan kesalahan validasi dalam bahasa Indonesia sudah sangat spesifik dan informatif (misal: *"Format nomor WhatsApp tidak valid (contoh: 081234567890 atau +6281234567890)"*, *"Batas paket Free tercapai (maksimal 5 barang aktif)"*).
     - Namun, form belum memiliki auto-scroll atau auto-focus ke field pertama yang mengalami error saat submit gagal.

---

### 2.9. Performa (Dimensi Gambar, Lazy Load, Render Overhead)
- **Skor: 2 / 5 (Performa Rendah Akibat Runtime Tailwind CDN)**
- **Bukti & Temuan:**
  1. **Penggunaan Tailwind JIT Compiler di Browser (`cdn.tailwindcss.com`):**
     - Ditemukan pada: `welcome.blade.php:16`, `catalog.blade.php:8`, `show.blade.php:8`, `success.blade.php:8`, dan `billing/index.blade.php:7`.
     - Skrip CDN ini mengunduh compiler sebesar ~300 KB dan memindai seluruh DOM setiap kali halaman dimuat untuk men-generate CSS on-the-fly. Ini memicu penundaan render awal (*render-blocking*), penurunan drastis skor Google Lighthouse / Core Web Vitals (FCP dan LCP), serta lonjakan konsumsi baterai/CPU di perangkat ponsel penyewa.
  2. **Gambar Tanpa Atribut Width & Height (Memicu Cumulative Layout Shift / CLS):**
     - `resources/views/public/items/show.blade.php:57`: Gambar utama produk `<img id="main-product-image" src="{{ $photos[0] }}" ...>` tidak memiliki atribut `width` dan `height`.
     - `resources/views/public/vendor/catalog.blade.php:119`: Gambar katalog tidak menyertakan atribut `loading="lazy"` dan dimensi fisik.
     - `resources/views/welcome.blade.php:432, 472, 512, ...`: 8 gambar produk memuat resolusi besar dari Unsplash tanpa dimensi eksplisit.

---

## 3. Daftar Masalah Terurut Berdasarkan Dampak ke Pengguna

| Peringkat | Kategori | Ringkasan Masalah | Lokasi Berkas Utama | Dampak ke Pengguna |
|---|---|---|---|---|
| **1** | **Performa & Kompatibilitas** | Ketergantungan pada `<script src="https://cdn.tailwindcss.com">` alih-alih bundle Vite | `show.blade.php:8`, `catalog.blade.php:8`, `welcome.blade.php:16`, `billing/index.blade.php:7` | Loading halaman di HP penyewa menjadi lambat (300KB JS compile runtime), rawan flash layout patah (FOUC) saat koneksi lemah. |
| **2** | **Kepatuhan Desain (Anti-Slop)** | 15+ lokasi penggunaan emoji di UI produksi dan 8 produk fiktif hardcoded | `vendor-booking-calendar.blade.php:149`, `onboarding-checklist-widget.blade.php:7`, `store-link-and-plan-widget.blade.php:79`, `billing/index.blade.php`, `welcome.blade.php` | Merusak citra profesional B2B SaaS, melanggar kriteria anti-slop, membingungkan vendor dengan data katalog palsu. |
| **3** | **Responsif & Mobile UX** | Kalender vendor (7 kolom) terjepit parah di layar 375px; tombol navigasi <44px | `vendor-booking-calendar.blade.php:99`, `item-availability-calendar.blade.php:14, 25, 79` | Vendor dan penyewa salah tekan tanggal sewa karena target sentuh terlalu kecil (<44px) dan teks pesanan terpotong. |
| **4** | **Inkonsistensi Visual** | 3 tema visual terpisah: Luxury Gold (`welcome`), Warm Neutral Lime (`public`), Generic Tailwind (`billing`) | `welcome.blade.php`, `design-tokens.blade.php`, `billing/index.blade.php` | Pengalaman berpindah halaman terasa seperti menggunakan 3 aplikasi yang berbeda, menurunkan kepercayaan brand. |
| **5** | **Duplikasi & Dead Code** | Komponen UI (`<x-ui.button>`, `<x-ui.alert>`, `<x-ui.card>`) terlantar; header di-copy-paste 3 kali | `resources/views/components/ui/`, `catalog.blade.php:14`, `show.blade.php:15`, `success.blade.php:14` | Menambah beban maintenance; perubahan desain tombol di masa depan harus diubah manual di puluhan baris blade. |
| **6** | **Aksesibilitas (A11y)** | Label unggah KTP/selfie tidak terhubung ke input; kontras buffer gagal WCAG AA | `booking-form.blade.php:125, 133`, `item-availability-calendar.blade.php:85` | Pengguna dengan keterbatasan penglihatan dan pengguna keyboard tidak dapat mengoperasikan alur rental dengan lancar. |
