# JagaSewa Design System (v1.0)

> Dokumen spesifikasi tunggal (Single Source of Truth) untuk antarmuka pengguna (UI/UX) JagaSewa di seluruh kanal: Halaman Publik Penyewa (Mobile-First Web) dan Dashboard Vendor/Admin (Filament + Livewire).
> Semua nilai visual didefinisikan melalui Design Tokens (CSS Variables). Dilarang keras menggunakan nilai warna, margin, padding, atau ukuran hard-coded di komponen.

---

## 1. Filosofi & Arah Visual

JagaSewa mengusung prinsip **Clean, Modern SaaS**:
1. **Ruang Kosong Lega (Generous Whitespace):** Memberikan ruang bernapas antar blok konten agar pengguna mobile tidak merasa terintimidasi oleh form yang panjang atau persyaratan identitas.
2. **Hierarki Visual Jelas:** Perbedaan kontras tegas antara judul, sub-judul, data nominal, dan label pembantu. Data finansial (harga, deposit, denda) selalu tampil menonjol dan transparan.
3. **Elevasi Halus (Subtle Elevation):** Border abu-abu tipis (1px) dipadukan dengan drop shadow lembut bertingkat. Menghindari border tebal, warna gradasi norak, atau efek visual yang mengalihkan fokus.
4. **Sentuhan Ramah & Aman (Trust & Reliability):** Alur verifikasi identitas (KTP & Selfie) disajikan dengan penjelasan ramah, transparan, dan terstruktur tanpa memicu kekhawatiran privasi.
5. **Mobile-First & Touch-Target Compliance:** Seluruh tombol aksi utama, input formulir, dan elemen kalender didesain khusus agar nyaman ditekan satu jempol di layar HP (< 640px) dengan target sentuh minimal 44×44px.

---

## 2. Usulan Warna Aksen Utama & Alternatif

Untuk SaaS booking rental yang melibatkan transaksi barang berharga dan verifikasi dokumen identitas (KTP):

### 🌟 Rekomendasi Utama: **Trust Emerald (Hijau Zamrud Keamanan)**
- **Token:** `--color-primary-600: #059669` (Dominan), `--color-primary-500: #10b981`, `--color-primary-700: #047857`
- **Rasional:** Hijau zamrud memancarkan rasa aman, persetujuan resmi, dan ketenangan finansial. Di Indonesia, hijau memiliki resonansi kedekatan dengan ekosistem WhatsApp (kanal notifikasi utama JagaSewa) dan platform finansial terpercaya. Sangat cocok meredakan resistensi pengguna saat diminta mengunggah foto KTP.

### 🔷 Alternatif 1: **Royal Navy / Corporate Blue (Biru Kepercayaan Otoritatif)**
- **Token:** `--color-primary-600: #2563eb`, `--color-primary-700: #1d4ed8`
- **Rasional:** Standar industri SaaS finansial & legal global (mis. Stripe, DocuSign). Memberikan kesan sistem keamanan tinggi, formal, dan reliabilitas tinggi untuk perlindungan aset vendor bernilai jutaan rupiah.

### 🌊 Alternatif 2: **Deep Teal (Teal Samudra Modern)**
- **Token:** `--color-primary-600: #0891b2`, `--color-primary-700: #0e7490`
- **Rasional:** Kombinasi stabilitas warna biru dan kesegaran warna hijau. Terkesan modern, estetik, dan luwes untuk pasar rental kreatif (kamera fotografi, kostum cosplay, kebaya wisuda/pernikahan).

---

## 3. Design Tokens (CSS Variables)

Semua token dideklarasikan pada `:root` agar dapat diakses oleh Tailwind CSS v4, Blade templates, dan konfigurasi tema Filament v5.

```css
:root {
  /* ==========================================================================
     PALET WARNA (COLOR SYSTEM)
     ========================================================================== */

  /* Primary Brand (Trust Emerald - Rekomendasi Utama) */
  --color-primary-50:  #ecfdf5;
  --color-primary-100: #d1fae5;
  --color-primary-200: #a7f3d0;
  --color-primary-300: #6ee7b7;
  --color-primary-400: #34d399;
  --color-primary-500: #10b981;
  --color-primary-600: #059669; /* Default Primary CTA & Accent */
  --color-primary-700: #047857; /* Hover / Active State */
  --color-primary-800: #065f46;
  --color-primary-900: #064e3b;
  --color-primary-950: #022c22;

  /* Neutral Scale (Slate Gray - Dingin, Bersih, Kontras Tinggi) */
  --color-neutral-0:   #ffffff;
  --color-neutral-50:  #f8fafc; /* Background App Light */
  --color-neutral-100: #f1f5f9; /* Surface Secondary / Card Muted */
  --color-neutral-200: #e2e8f0; /* Default Border Light */
  --color-neutral-300: #cbd5e1; /* Hover Border / Inactive Control */
  --color-neutral-400: #94a3b8; /* Placeholder / Subtle Icon */
  --color-neutral-500: #64748b; /* Secondary Caption Text */
  --color-neutral-600: #475569; /* Body Secondary */
  --color-neutral-700: #334155; /* Body Main */
  --color-neutral-800: #1e293b; /* Heading Sub */
  --color-neutral-900: #0f172a; /* Heading Main / High Contrast Text */
  --color-neutral-950: #020617;

  /* Semantic Colors (WCAG 2.2 AA Contrast Compliant) */
  /* Success: Konfirmasi, Disetujui, Tersedia */
  --color-success-50:  #f0fdf4;
  --color-success-100: #dcfce7;
  --color-success-500: #22c55e;
  --color-success-600: #16a34a;
  --color-success-700: #15803d;

  /* Warning: Menunggu Persetujuan, Peringatan Buffer, Informasi Blacklist */
  --color-warning-50:  #fffbeb;
  --color-warning-100: #fef3c7;
  --color-warning-500: #f59e0b;
  --color-warning-600: #d97706;
  --color-warning-700: #b45309;

  /* Danger: Denda, Keterlambatan, Ditolak, Dibatalkan, Hapus */
  --color-danger-50:   #fef2f2;
  --color-danger-100:  #fee2e2;
  --color-danger-500:  #ef4444;
  --color-danger-600:  #dc2626;
  --color-danger-700:  #b91c1c;

  /* Info: Panduan, Instruksi Identitas, Detail Pesanan */
  --color-info-50:     #f0f9ff;
  --color-info-100:    #e0f2fe;
  --color-info-500:    #0ea5e9;
  --color-info-600:    #0284c7;
  --color-info-700:    #0369a1;

  /* Buffer Calendar Specific */
  --color-buffer-bg:     #f1f5f9; /* Slate 100 */
  --color-buffer-border: #cbd5e1; /* Slate 300 */
  --color-buffer-text:   #475569; /* Slate 600 */

  /* WhatsApp Brand Color (Official) */
  --color-brand-whatsapp: #25d366;
  --color-brand-whatsapp-hover: #20ba5a;

  /* ==========================================================================
     TIPOGRAFI (TYPOGRAPHY)
     ========================================================================== */
  --font-family-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
  --font-family-mono: 'JetBrains Mono', 'Fira Code', ui-monospace, SFMono-Regular, monospace;

  /* Skala Ukuran Font & Line Height Terukur */
  --font-size-2xs: 0.6875rem; /* 11px */
  --line-height-2xs: 1rem;    /* 16px */

  --font-size-xs: 0.75rem;    /* 12px */
  --line-height-xs: 1.125rem; /* 18px */

  --font-size-sm: 0.875rem;   /* 14px */
  --line-height-sm: 1.375rem; /* 22px */

  --font-size-base: 1rem;     /* 16px */
  --line-height-base: 1.5rem; /* 24px */

  --font-size-lg: 1.125rem;   /* 18px */
  --line-height-lg: 1.75rem;  /* 28px */

  --font-size-xl: 1.25rem;    /* 20px */
  --line-height-xl: 1.875rem; /* 30px */

  --font-size-2xl: 1.5rem;    /* 24px */
  --line-height-2xl: 2rem;    /* 32px */

  --font-size-3xl: 1.875rem;  /* 30px */
  --line-height-3xl: 2.25rem; /* 36px */

  /* Ketebalan Font (Font Weight) Terbatas */
  --font-weight-regular:  400;
  --font-weight-medium:   500;
  --font-weight-semibold: 600;
  --font-weight-bold:     700;
  --font-weight-extrabold:800;

  /* Letter Spacing */
  --letter-spacing-tight: -0.025em;
  --letter-spacing-normal: 0em;
  --letter-spacing-wide:  0.025em;
  --letter-spacing-wider: 0.05em;

  /* ==========================================================================
     SPASI & TATA LETAK (SPACING SYSTEM - KELIPATAN 4PX)
     ========================================================================== */
  --space-0:   0px;
  --space-1:   0.25rem;  /* 4px */
  --space-1-5: 0.375rem; /* 6px */
  --space-2:   0.5rem;   /* 8px */
  --space-2-5: 0.625rem; /* 10px */
  --space-3:   0.75rem;  /* 12px */
  --space-4:   1rem;     /* 16px */
  --space-5:   1.25rem;  /* 20px */
  --space-6:   1.5rem;   /* 24px */
  --space-8:   2rem;     /* 32px */
  --space-10:  2.5rem;   /* 40px */
  --space-12:  3rem;     /* 48px */
  --space-16:  4rem;     /* 64px */
  --space-20:  5rem;     /* 80px */

  /* Lebar Kontainer Maksimum */
  --container-public-mobile: 480px;
  --container-public-max:    1200px;
  --container-admin-max:     1440px;

  /* Target Sentuh Minimal Mobile (WCAG 2.5.5) */
  --touch-target-min: 44px;

  /* ==========================================================================
     RADIUS SUDUT (BORDER RADIUS)
     ========================================================================== */
  --radius-none: 0px;
  --radius-xs:   0.25rem;  /* 4px - badge kecil */
  --radius-sm:   0.375rem; /* 6px - tag, label */
  --radius-md:   0.5rem;   /* 8px - input field, tombol sekunder */
  --radius-lg:   0.75rem;  /* 12px - kartu kecil, thumbnail */
  --radius-xl:   1rem;     /* 16px - kartu utama, dialog */
  --radius-2xl:  1.25rem;  /* 20px - bottom sheet, container card */
  --radius-3xl:  1.5rem;   /* 24px - hero banner container */
  --radius-full: 9999px;   /* pill / bulat penuh */

  /* ==========================================================================
     ELEVASI & BAYANGAN (SHADOWS - SUBTLE SAAS)
     ========================================================================== */
  --shadow-xs: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
  --shadow-sm: 0 1px 3px 0 rgba(15, 23, 42, 0.06), 0 1px 2px -1px rgba(15, 23, 42, 0.04);
  --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.07), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
  --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
  --shadow-xl: 0 20px 25px -5px rgba(15, 23, 42, 0.10), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
  --shadow-inner: inset 0 2px 4px 0 rgba(15, 23, 42, 0.04);

  /* Aksen Glow Halus untuk CTA */
  --shadow-primary-glow: 0 4px 14px 0 rgba(5, 150, 105, 0.28);
  --shadow-wa-glow:      0 4px 14px 0 rgba(37, 211, 102, 0.28);

  /* ==========================================================================
     UKURAN IKON (ICONOGRAPHY)
     ========================================================================== */
  --icon-xs: 14px;
  --icon-sm: 16px;
  --icon-md: 20px;
  --icon-lg: 24px;
  --icon-xl: 32px;

  /* ==========================================================================
     TRANSISI & ANIMASI (MICRO-INTERACTIONS)
     ========================================================================== */
  --duration-fast:   150ms;
  --duration-normal: 200ms;
  --duration-slow:   300ms;
  --ease-standard:   cubic-bezier(0.16, 1, 0.3, 1); /* Apple-like swift ease */
  --ease-in:         cubic-bezier(0.4, 0, 1, 1);
  --ease-out:        cubic-bezier(0, 0, 0.2, 1);

  /* ==========================================================================
     Z-INDEX HIERARCHY
     ========================================================================== */
  --z-base:       0;
  --z-card:       1;
  --z-sticky-nav: 30;
  --z-sticky-cta: 40;
  --z-backdrop:   50;
  --z-drawer:     60;
  --z-modal:      70;
  --z-popover:    80;
  --z-toast:      90;
  --z-tooltip:    100;
}
```

---

## 4. Breakpoints & Aturan Layout Responsif (Mobile-First)

| Breakpoint | Rentang Lebar | Karakteristik Perangkat | Perilaku Layout | Target Validasi |
| :--- | :--- | :--- | :--- | :--- |
| **Mobile XS** | 360px – 389px | Android ekonomis, iPhone mini | Kolom tunggal 100%, spasi kompak (12-16px), font body 14px, tombol sticky bawah | **360px** |
| **Mobile Standard** | 390px – 639px | iPhone standard, Android modern | Kolom tunggal terpusat (max 480px), padding horizontal 16px | **390px** |
| **Tablet Portrait** | 640px – 767px | iPad Mini, Tablet 8" | 2 kolom pada daftar kartu, kalender full-width | **640px** |
| **Tablet Landscape**| 768px – 1023px | iPad 10", Galaxy Tab | 2-3 kolom grid kartu, modal standar, sidebar dashboard ikonik | **768px** |
| **Desktop Small** | 1024px – 1279px | Laptop 13-14", monitor HD | Layout 2 panel (Kiri: Galeri + Info, Kanan: Sticky Booking Form), sidebar admin terbuka | **1024px** |
| **Desktop Standard**| 1280px – 1439px | MacBook Pro, Monitor FHD | Konten terpusat max 1200px, padding lapang | **1280px** |
| **Desktop Wide** | ≥ 1440px | Monitor QHD/4K | Konten max 1280-1440px, margin horizontal auto, tanpa layout stretch | **1440px** |

### Aturan Ketat Layout:
1. **Dilarang keras ada Horizontal Scrollbar** di level `window` atau `body` pada seluruh lebar layar (360px s.d. 1440px). Scroll horizontal hanya diperbolehkan pada kontainer foto carousel atau kontainer tabel data yang sengaja dibungkus `overflow-x-auto`.
2. **Sticky CTA Mobile:** Pada halaman publik item di bawah 640px, tombol "Pesan Sekarang" atau ringkasan biaya wajib berada di bottom sticky bar (`position: fixed; bottom: 0; left: 0; right: 0; height: ~72px; padding: 12px 16px;`) dengan backdrop blur dan pemisah border halus di atasnya.
3. **Penyusutan Tabel ke Kartu:** Pada mobile (< 640px), tabel pesanan dan tabel barang di dashboard vendor wajib beralih menjadi format daftar kartu (`stacked card list`) agar tidak memerlukan geser horizontal yang menyulitkan vendor di lapangan.

---

## 5. Komponen Inti & Spesifikasi Semua State

Setiap komponen wajib mengimplementasikan state lengkap:
- `Default`: Kondisi netral
- `Hover`: Kursor diarahkan (hanya aktif pada pointer: fine)
- `Focus-Visible`: Navigasi keyboard atau tab (`outline: 2px solid var(--color-primary-600); outline-offset: 2px;`)
- `Active / Pressed`: Saat tombol disentuh/ditekan (`transform: scale(0.98);`)
- `Disabled`: Tidak dapat diklik (`opacity: 0.5; cursor: not-allowed;`)
- `Loading`: Memproses aksi (`spinner icon`, teks tertahan, tombol disabled)
- `Error`: Validasi tidak valid (border merah, teks bantuan merah, `aria-invalid="true"`)
- `Empty`: Data kosong (ilustrasi netral, pesan solutif, aksi buat baru)

---

### 5.1 Tombol (Button System)

| Varian | Style Default | Hover / Active | Disabled | Focus-Visible |
| :--- | :--- | :--- | :--- | :--- |
| **Primary** | `bg-[var(--color-primary-600)] text-white font-semibold rounded-xl shadow-xs` | `bg-[var(--color-primary-700)] active:scale-[0.98]` | `bg-[var(--color-neutral-200)] text-[var(--color-neutral-400)] shadow-none` | Ring 2px Primary, Offset 2px |
| **Secondary** | `bg-white border border-[var(--color-neutral-200)] text-[var(--color-neutral-700)] rounded-xl font-medium` | `bg-[var(--color-neutral-50)] border-[var(--color-neutral-300)] text-[var(--color-neutral-900)]` | `opacity-50 border-[var(--color-neutral-100)]` | Ring 2px Primary |
| **Ghost / Tertiary** | `bg-transparent text-[var(--color-neutral-600)] rounded-xl font-medium` | `bg-[var(--color-neutral-100)] text-[var(--color-neutral-900)]` | `opacity-40` | Ring 2px Primary |
| **Danger** | `bg-[var(--color-danger-50)] border border-[var(--color-danger-200)] text-[var(--color-danger-700)] rounded-xl font-semibold` | `bg-[var(--color-danger-600)] text-white border-transparent` | `opacity-50` | Ring 2px Danger |
| **WhatsApp Brand** | `bg-[var(--color-brand-whatsapp)] text-white font-bold rounded-xl shadow-sm` | `bg-[var(--color-brand-whatsapp-hover)] shadow-md` | `opacity-50` | Ring 2px WhatsApp |

- **Ukuran:**
  - `Small (sm)`: Tinggi 36px, teks 13px, padding horizontal 12px
  - `Medium (md)`: Tinggi 44px (memenuhi target sentuh), teks 14px, padding 16px
  - `Large (lg)`: Tinggi 52px, teks 16px, padding 20px (CTA utama mobile)
- **Loading State:** Teks disembunyikan atau diapit, spinner SVG inline 16/20px berputar mulus (`animate-spin`), tombol berstatus disabled tanpa perubahan tata letak layout.

---

### 5.2 Input Formulir (Form Fields & Formatting)

- **Input Teks, Angka & Textarea:**
  - Tinggi default: 44px (textarea: auto min 88px)
  - Radius: `var(--radius-xl)` (12px)
  - Latar: `var(--color-neutral-50)` beralih ke `var(--color-neutral-0)` saat fokus
  - Border: 1px `var(--color-neutral-200)`, saat fokus: border `var(--color-primary-600)` + ring 3px `var(--color-primary-100)`
  - State Error: border `var(--color-danger-500)` + ring 3px `var(--color-danger-100)` + ikon seru kecil + teks galat di bawah input (`text-xs text-danger-600`)
- **Input Rupiah Otomatis:**
  - Prefix permanen: "Rp" berwarna neutral-500 di dalam kotak input
  - Format angka otomatis ribuan: `150.000` (saat blur/ketik tanpa desimal)
- **Select / Dropdown:**
  - Ikon chevron bawah rapi, tinggi seragam 44px, opsi dropdown memiliki padding sentuh minimal 40px per baris.
- **Checkbox & Radio:**
  - Ukuran: 20×20px (bukan 14px mini), radius 6px (checkbox) atau bundar (radio), aksen warna primary-600.
  - Label terhubung (`cursor-pointer`) dengan baris teks sejajar.
- **Toggle / Switch:**
  - Lebar 44px, tinggi 24px, knob 20px dengan transisi halus 200ms.

---

### 5.3 Komponen Upload Dokumen Identitas (KYC)

Dirancang khusus untuk membimbing penyewa mengunggah foto KTP & Selfie secara nyaman dan jelas:
- **Card Pratinjau Terstruktur:**
  - Rasio kartu KTP 3:2 (sesuai proporsi KTP fisik), area selfie 1:1 atau 3:4.
  - Border putus-putus (`border-dashed border-2 border-neutral-300`) sebelum diunggah.
  - Tombol aksi jelas: "Buka Kamera / Pilih File" (target sentuh 44px).
  - Indikator panduan kualitas foto:
    - 💡 *"Pastikan tulisan NIK & Nama terbaca jelas dan tidak buram"*
    - 💡 *"Hindari pantulan cahaya kilat (flash) langsung pada KTP"*
    - 💡 *"Foto tidak terpotong di keempat sudutnya"*
- **State Setelah Upload:**
  - Thumbnail gambar tampil dengan tombol "Ganti Foto" dan tombol "Hapus" di pojok.
  - Tanda centang hijau "✓ Foto Siap Digunakan".
  - Banner Privasi Terintegrasi:
    > 🔒 *Data dienkripsi secara aman & dihapus otomatis 30 hari setelah sewa selesai sesuai UU Perlindungan Data Pribadi.*

---

### 5.4 Badge Status Pesanan (Maksimal 5 Varian Semantik)

Badge status menggunakan maksimal 5 varian semantik baku tanpa emoji dekoratif. Perbedaan antar-status dinyatakan secara eksplisit melalui label teks:

| Status Kode | Label Teks Indonesia | Varian Semantik | Token Latar | Token Teks & Border |
| :--- | :--- | :--- | :--- | :--- |
| `pending` | Menunggu persetujuan | **Peringatan (Warning)** | `var(--color-status-warning-bg)` | `text-[#92400e] border-[#fde68a]` |
| `returned` | Dikembalikan | **Peringatan (Warning)** | `var(--color-status-warning-bg)` | `text-[#92400e] border-[#fde68a]` |
| `approved` | Disetujui | **Info** | `var(--color-status-info-bg)` | `text-[#0369a1] border-[#bae6fd]` |
| `active` | Sedang disewa | **Info** | `var(--color-status-info-bg)` | `text-[#0369a1] border-[#bae6fd]` |
| `closed` | Selesai | **Sukses (Success)** | `var(--color-status-success-bg)` | `text-[#166534] border-[#bbf7d0]` |
| `rejected` | Ditolak | **Bahaya (Danger)** | `var(--color-status-danger-bg)` | `text-[#991b1b] border-[#fecaca]` |
| `cancelled_*`| Batal (Batas waktu / Renter / Vendor)| **Netral** | `var(--color-status-neutral-bg)` | `text-[#475569] border-[#e2e8f0]` |
| `expired` | Kedaluwarsa | **Netral** | `var(--color-status-neutral-bg)` | `text-[#475569] border-[#e2e8f0]` |

---

### 5.5 Kartu Barang (Item Card) & Galeri

- **Rasio Foto:** 4:3 dengan objek `object-cover`.
- **Badges Melayang di Atas Foto:** Ukuran (misal: "Size: M") dan Status Ketersediaan.
- **Harga Menonjol:** Huruf tebal 18px (`font-extrabold text-primary-700`) diikuti teks pendukung `/ hari`.
- **Sub-info Transparan:** Deposit ("Bebas Deposit" atau "Dep: Rp 100.000") dan Smart Buffer ("Jeda 2 hari").
- **Galeri Foto:** Pratinjau foto utama besar dengan baris thumbnail di bawahnya yang aktif saat ditekan. Pada mobile mendukung swipe horizontal alami.

---

### 5.6 Kalender Ketersediaan Cerdas (Smart Buffer Calendar)

Pola desain khusus JagaSewa yang membedakan tanggal secara multi-modal (Warna + Simbol + Pola):

```
+----+  Tersedia: Kotak putih/hijau terang, angka tegas, indikator titik hijau kecil
| 15 |  [● Tersedia]
+----+

+----+  Dipesan (Booked): Latar merah muda, teks tercoret garis, titik merah tua
| 16 |  [✕ Terpesan - Dibatasi booking lain]
+----+

+----+  Buffer Otomatis: Latar abu-abu garis miring (striped pattern), ikon perisai/jam kecil
| 17 |  [🛡️ Buffer - Jeda laundry & pengiriman vendor]
+----+

+----+  Menunggu Persetujuan (Pending): Latar kuning garis putus-putus
| 18 |  [⏳ Menunggu Konfirmasi]
+----+
```

- **Legenda Terbaca:** Selalu ditempatkan di bawah kalender dengan label teks eksplisit.
- **Responsivitas Kalender:**
  - *Mobile (< 640px):* Tampilan kalender grid bulan yang ringkas dengan target klik tanggal minimal 40×40px, ditambah opsi beralih ke tampilan agenda daftar tanggal per minggu jika layar sangat sempit (360px).
  - *Tablet & Desktop (≥ 640px):* Kalender grid bulan penuh dengan tooltip keterangan durasi booking saat kursor diarahkan ke tanggal terpesan.

---

### 5.7 Formulir Booking Multi-Langkah (Stepper Form)

Untuk memecah kompleksitas pemesanan menjadi 3 langkah terstruktur yang tidak melelahkan:

```
[ 1. Jadwal Sewa ] ─── [ 2. Kontak & Alamat ] ─── [ 3. Identitas & Privasi ]
       ✓                        ●                              ○
    Selesai                 Sedang Diisi                  Selanjutnya
```

- **Penyimpanan Progres (Livewire State):** Data tanggal yang dipilih di kalender otomatis terisi ke formulir langkah 1.
- **Validasi Inline Seketika:** Feedback langsung saat nomor WhatsApp tidak valid atau tanggal bertabrakan dengan jadwal buffer.
- **Transparansi Biaya Selalu Terlihat:** Kotak ringkasan biaya (`Tarif Sewa × Hari`, `Uang Deposit`, `Total Bayar Awal`) selalu diperbarui secara real-time.

---

### 5.8 Peringatan Blacklist (Blacklist Warning Banner)

Nada bahasa wajib **Netral, Faktual, dan Objektif** (tidak menuduh atau menghakimi):
- **Wujud Komponen:** Banner Amber/Kuning bergaris batas tegas, hanya tampil di dashboard vendor pemilik data.
- **UX Copy Baku:**
  > ⚠️ **Catatan Riwayat Penyewa Ditemukan**  
  > *Nomor WhatsApp atau identitas ini terdaftar dalam catatan internal rental Anda dengan keterangan: "[Keterlambatan 3 hari pada booking sebelumnya]".*
- **Aksi Cepat & Jelas (Actionable):**
  - Tombol 1 (Primer Netral): "Lanjutkan & Setujui Pesanan"
  - Tombol 2 (Sekunder): "Tolak Permohonan Sewa"
  - Tautan Pembantu: "Lihat Detail Catatan Blacklist"

---

### 5.9 Feedback, Modal, Empty States, & Error Pages

- **Alert / Banner:** Berisi ikon semantik, judul singkat tebal, deskripsi pesan, dan tombol silang tutup opsional.
- **Modal vs Bottom Sheet:**
  - *Desktop (≥ 640px):* Modal pop-up di tengah layar dengan backdrop blur `bg-neutral-900/40`.
  - *Mobile (< 640px):* Otomatis berubah menjadi **Bottom Sheet** yang meluncur dari bawah layar (`slide-up`), memiliki drag handle di bagian atas, dan tombol aksi mudah dijangkau jempol.
- **Empty State:**
  - Ikon line-art minimalis atau emoji tematik (mis. 📦 untuk barang, 📋 untuk pesanan).
  - Judul tegas: *"Belum Ada Pesanan Masuk"*.
  - Deskripsi panduan: *"Ketika ada penyewa memesan lewat tautan katalog Anda, pesanan akan muncul di sini untuk Anda verifikasi."*
  - CTA aksi jika relevan (misal: "Salin Link Katalog Toko").
- **Halaman Error (404, 500, 429):**
  - Desain bersih terpusat dengan logo JagaSewa, kode error jelas, penjelasan ramah tanpa jargon teknis asing, dan tombol "Kembali ke Beranda" atau "Hubungi Bantuan".

---

## 6. Glosarium UX Copy (Bahasa Indonesia Baku & Ramah)

Untuk memastikan konsistensi istilah di seluruh aplikasi, dilarang mencampuradukkan padanan kata yang berbeda:

| Konsep / Fitur | Istilah Baku yang Dipakai | Istilah yang Dihindari |
| :--- | :--- | :--- |
| Transaksi sewa | **Pesanan** / **Permohonan Sewa** | *Orderan, Cart, Checkout* |
| Barang sewa | **Barang Rental** / **Katalog** | *Produk, Items jualan* |
| Toko persewaan | **Vendor** / **Rental** | *Merchant, Seller, Lapak* |
| Uang jaminan | **Uang Jaminan (Deposit)** | *Uang muka, DP, Panjar* |
| Jeda persiapan | **Jeda Jadwal (Smart Buffer)** | *Waktu jeda, Cooldown, Gap* |
| Biaya telat | **Denda Keterlambatan** | *Penalti, Charge telat* |
| Penyetelan sewa | **Penyelesaian Sewa** | *Closing, Settlement, Finalisasi* |
| Foto identitas | **Verifikasi KTP & Selfie** | *KYC, Upload dokumen* |
| Nomor pesanan | **Nomor Referensi Booking** | *Invoice ID, Token ID* |

### Format Standar:
- **Mata Uang:** `Rp 1.250.000` (Menggunakan spasi setelah Rp, titik sebagai pemisah ribuan, tanpa `,00`).
- **Tanggal:** `19 Okt 2026` atau `19 Oktober 2026, 14:00 WIB` (Mengacu ke timezone `Asia/Jakarta`).
- **Nomor Telepon:** Tampilan visual `0812-3456-7890` (disimpan dalam format normalisasi `62812...`).

---

## 7. Aksesibilitas (WCAG 2.2 AA) & Performa

1. **Rasio Kontras Warna (Minimum 4.5:1):**
   - Teks gelap (`var(--color-neutral-900)` dan `var(--color-neutral-700)`) di atas latar putih atau abu-50 memiliki rasio kontras > 10:1 (melebihi standar AA).
   - Teks warna primer (`var(--color-primary-700)`) di atas latar hijau-50 memiliki rasio > 5.5:1.
2. **Navigasi Keyboard Penuh:**
   - Semua elemen interaktif (`<button>`, `<a>`, `<input>`) dapat diakses via tombol `Tab` dan `Enter/Space`.
   - Focus outline terlihat jelas pada mode keyboard tanpa mengganggu klik mouse (`:focus-visible`).
3. **Preferensi Gerakan (Reduced Motion):**
   - Mendukung media query `@media (prefers-reduced-motion: reduce)` yang mematikan transisi berat dan animasi berputar.
4. **Target Core Web Vitals:**
   - **LCP (Largest Contentful Paint):** ≤ 2.5 detik (mengoptimalkan kompresi WebP gambar barang dan lazy loading).
   - **INP (Interaction to Next Paint):** ≤ 200 ms (komponen Livewire reaktif dengan feedback visual instan saat tombol ditekan).
   - **CLS (Cumulative Layout Shift):** ≤ 0.1 (semua kontainer gambar memiliki `aspect-ratio` eksplisit agar tata letak tidak bergeser saat gambar selesai dimuat).
5. **Tipografi Self-Hosted:**
   - Menggunakan font `Plus Jakarta Sans` berbobot terbatas (400, 500, 600, 700) yang dilayani secara lokal/self-hosted untuk menjamin kecepatan render pada jaringan 4G seluler di Indonesia.
