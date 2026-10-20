# Analisis Kesenjangan (Gap Analysis) — JagaSewa

> Dokumen ini membandingkan spesifikasi perencanaan produk pada [`docs/prd.md`](file:///c:/DataCoding/my-saas/docs/prd.md) dan [`docs/user-stories.md`](file:///c:/DataCoding/my-saas/docs/user-stories.md) dengan implementasi nyata pada aplikasi per 19 Oktober 2026.
> Tujuan: Mengidentifikasi fitur yang dijanjikan dalam spesifikasi namun belum memiliki antarmuka (UI) atau alur logika yang lengkap di aplikasi.

---

## 1. Matriks Kesenjangan Fitur (PRD / User Stories vs Realita)

| Domain | Spesifikasi (PRD & User Stories) | Kondisi Aktual di Kode & Browser | Status Kesenjangan | Tingkat Urgensi |
|---|---|---|---|---|
| **Alur Billing & Checkout** | Vendor Free upgrade ke Pro; checkout terarah ke payment gateway; status satu langganan aktif per vendor. | Controller langsung `Subscription::create()` tanpa pengecekan status Pro aktif/pending; tombol checkout tidak terkunci; vendor bisa punya multi-subscription. | **Kritis (Celah Finansial)** | **P0** |
| **Pengaturan Profil Toko** | Vendor mengatur etalase bio link IG/X (logo, banner, deskripsi toko, no. WhatsApp, info rekening transfer). | **TIDAK ADA UI**. Tidak ada menu Pengaturan Toko di Filament; vendor tidak bisa edit nama, WA, atau deskripsi toko setelah daftar. | **Fitur Hilang** | **P1** |
| **Kustom Template WhatsApp** | Vendor Pro bebas mengkustomisasi template chat WhatsApp (info rekening, alamat pickup/return, aturan denda, format salam brand). | **TIDAK ADA UI / HARDCODED**. Notifikasi wa.me memakai string hardcoded di `WaNotificationHelper.php`. Tidak ada kolom DB untuk template kustom. | **Fitur Hilang** | **P1** |
| **Kapasitas Foto Barang Pro** | Vendor Free: 3–5 foto; Vendor Pro: 6–8 foto per barang rental. | **HARDCODED**. `ItemForm.php` membatasi `maxFiles(5)` untuk semua vendor tanpa membedakan status Free atau Pro. | **Fitur Belum Terhubung** | **P1** |
| **Beranda Dashboard Vendor** | US-11 & PRD: Dashboard operasional harian (pesanan pending, barang disewa hari ini, pengembalian hari ini, kuota, tombol salin link toko). | Dashboard hanya berisi `AccountWidget` (avatar rusak) dan `FilamentInfoWidget` (dokumentasi Filament bawaan). Kosong dari data operasional. | **Desain Rusak / Hilang** | **P2** |
| **Kalender Sisi Vendor** | US-11 (Prioritas M): Vendor melihat kalender visual barang dengan warna status (pending/approved/active/returned) untuk koordinasi laundry & buffer. | **TIDAK ADA UI**. Kalender interaktif hanya dibuat untuk penyewa publik (`/b/{vendor}/{item}`). Vendor hanya disajikan tabel teks biasa (`BookingsTable`). | **Fitur Hilang** | **P2** |
| **Alur Onboarding Vendor** | PRD (Cold Start): Onboarding mudah (< 30 menit) memandu vendor baru: Lengkapi Profil Toko → Tambah Barang Pertama → Bagikan Link Katalog. | **TIDAK ADA**. Setelah registrasi, vendor langsung dilempar ke dashboard kosong tanpa panduan langkah atau checklist awal. | **Fitur Hilang** | **P2** |
| **Peringatan Batas Kuota Interaktif** | Vendor Free yang mencapai batas 5 barang atau 5 blacklist mendapat panduan edukatif untuk upgrade ke Pro. | Di tabel items/blacklist, create button diblokir atau di-redirect tanpa visualisasi kuota real-time yang jelas di resource terkait. | **UX Belum Optimal** | **P2** |

---

## 2. Rincian Analisis Celah per Modul

### 2.1. Celah Alur Langganan & Checkout (P0)
- **Yang Dijanjikan di Dokumen:**
  - Vendor memilih paket langganan (Bulanan Rp 15rb, Kuartalan Rp 39rb, Tahunan Rp 129rb).
  - Satu vendor hanya boleh memiliki **satu langganan aktif**.
  - Jika ada pesanan pending, pembayaran harus diselesaikan atau dibatalkan sebelum membuat order baru.
  - Penolakan tegas jika vendor sudah aktif Pro dan mencoba checkout ulang.
- **Kondisi Nyata:**
  - Halaman `/vendor/billing` menampilkan tombol "Pilih Paket" bahkan saat `$is_pro` sudah bernilai `true`.
  - Endpoint `POST /vendor/billing/checkout` tidak memeriksa apakah vendor sudah berstatus Pro atau memiliki order `pending_payment`.
  - Tidak ada `DB::transaction()` dan tidak ada `lockForUpdate()` pada record vendor, membuka celah *race condition* jika tombol ditekan berkali-kali secara simultan.
  - Rekonsiliasi harian ada, tetapi tidak ada UI riwayat tagihan (*invoice history*) untuk vendor melihat masa berlaku langganan mereka.

### 2.2. Ketiadaan Pengaturan Profil Toko (P1)
- **Yang Dijanjikan di Dokumen:**
  - JagaSewa menyasar vendor rental IG/X yang membutuhkan link katalog toko tunggal (`/b/{vendor_slug}`) di bio profil media sosial mereka.
  - Toko memiliki identitas: nama brand, logo, banner, deskripsi singkat, nomor WhatsApp admin/toko, dan alamat lokasi pickup/pengembalian barang.
- **Kondisi Nyata:**
  - Tabel `vendors` hanya memiliki kolom dasar: `name`, `slug`, `wa_number`, `email`, `is_active`. Tidak ada kolom `logo_path`, `banner_path`, `description`, `address`, atau `bank_account_info`.
  - Di Filament Vendor Panel, tidak ada Resource atau Page untuk "Pengaturan Toko". Sekali vendor mendaftar, mereka tidak memiliki antarmuka untuk mengubah nomor WhatsApp toko atau mengunggah logo.

### 2.3. Definisi & Implementasi Fitur Paket Pro yang Belum Nyata (P1)
- **Yang Dijanjikan di Dokumen:**
  Halaman pricing dan PRD menjanjikan 5 pembeda Paket Pro:
  1. *Unlimited barang aktif* (Free max 5) → **Sudah ada di backend, dicek di middleware**.
  2. *Unlimited blacklist pribadi* (Free max 5) → **Sudah ada di backend, dicek di service**.
  3. *White-label (tanpa watermark "Powered by JagaSewa")* → **Sudah ada di blade katalog**.
  4. *Kustom template chat WhatsApp* (rekening toko, aturan sewa, sapaan brand) → **BELUM ADA SAMA SEKALI**.
  5. *6–8 foto per barang* (Free hanya 3–5) → **BELUM DIHUBUNGKAN (masih hardcoded 5)**.
- **Kondisi Nyata:**
  Vendor yang membayar Paket Pro saat ini hanya mendapatkan kuota barang dan watermark yang hilang, sementara dua nilai jual utama (template WA dan kapasitas foto galeri) belum berfungsi di aplikasi.

### 2.4. Beranda Dashboard Vendor yang Tidak Bermanfaat (P2)
- **Yang Dijanjikan di Dokumen:**
  - Dashboard harus menjadi pusat komando harian vendor: pesanan yang butuh verifikasi (pending), barang yang harus dikirim hari ini, dan barang yang harus diterima kembali hari ini.
  - Menyediakan tautan cepat toko publik (`/b/{vendor_slug}`) untuk disalin ke Instagram bio.
- **Kondisi Nyata:**
  - Provider Filament mendaftarkan `AccountWidget` dan `FilamentInfoWidget` bawaan.
  - Avatar pengguna pecah karena tidak ada implementasi asset avatar yang valid.
  - Vendor tidak mendapat ringkasan apa pun tentang aktivitas rental mereka saat pertama kali membuka aplikasi.

### 2.5. Ketiadaan Kalender Visual di Sisi Vendor (US-11, P2)
- **Yang Dijanjikan di Dokumen:**
  - *US-11 (Prioritas M):* Sebagai vendor, saya ingin melihat kalender barang milik saya dengan status booking (pending, approved, active, returned) dan jeda buffer.
- **Kondisi Nyata:**
  - Fitur kalender Livewire hanya dipasang di halaman publik penyewa.
  - Sisi vendor hanya memiliki tabel data biasa (`BookingsTable`). Jika vendor memiliki puluhan penyewaan, sangat sulit melihat slot kosong atau jadwal laundry tanpa representasi kisi kalender visual.

### 2.6. Alur Onboarding Vendor Baru (P2)
- **Yang Dijanjikan di Dokumen:**
  - *PRD Cold Start Mitigation:* Onboarding cepat (< 30 menit).
- **Kondisi Nyata:**
  - Vendor baru setelah daftar dihadapkan pada layar kosong tanpa petunjuk langkah pertama (misal: "1. Atur Nama & Logo Toko", "2. Masukkan Barang Pertama", "3. Salin Link Toko ke Bio Instagram").

---

## 3. Rekomendasi Rencana Aksi (Penyelarasan Gap)

Untuk menutup kesenjangan ini, diperlukan tonggak tugas baru (**Milestone 13 — Stabilisasi & Penutupan Celah**) yang disusun berdasarkan prioritas risiko:
1. **P0 (Finansial):** Kunci checkout langganan ganda, wajibkan 1 subscription aktif per vendor, terapkan transaksi atomik dengan `lockForUpdate()`.
2. **P1 (Alur Inti):** Perbaiki form edit foto barang S3 privat; bangun halaman Pengaturan Toko di Filament; selesaikan implementasi kustom template WhatsApp dan limit 8 foto untuk Pro.
3. **P2 (UX & Operasional):** Ganti widget default Filament dengan Dashboard Rental Kustom (stats pending/aktif/kembali, salin link toko, onboarding checklist); buat halaman Kalender Sewa visual untuk vendor.
