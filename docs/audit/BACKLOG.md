# Backlog Masalah & Perbaikan Lanjutan (docs/audit/BACKLOG.md) — JagaSewa

> Disusun berdasarkan temuan audit menyeluruh pada tanggal 22 Oktober 2026.  
> Sesuai aturan kerja, seluruh temuan ini dicatat dan diprioritaskan di sini tanpa mengubah kode aplikasi pada sesi audit.

---

## 1. Prioritas P0 (Kritis & Finansial)
- [ ] **BL-001 (BUG-001):** Implementasi penonaktifan otomatis barang kelebihan (`> 5 item`) saat vendor Pro di-downgrade ke tier Free pada `SubscriptionReconciliationService` dan `SubscriptionStateMachine`.
- [ ] **BL-002 (BUG-002):** Bangun UI dan formulir verifikasi aktivasi pembayaran manual (*bank transfer verification*) di Panel Admin Filament (`VendorResource` / `SubscriptionResource`) agar admin dapat mengaktifkan paket Pro dan mengisi tabel `manual_activations`.
- [ ] **BL-003:** Ganti `<script src="https://cdn.tailwindcss.com"></script>` di seluruh berkas blade publik (`welcome`, `catalog`, `show`, `success`, `billing`) dengan asset bundle resmi Vite `@vite(['resources/css/app.css', 'resources/js/app.js'])` untuk memulihkan kecepatan rendering dan Core Web Vitals.
- [ ] **BL-004:** Tautkan menu navigasi "Ekspor Laporan CSV" ke sidebar Filament Vendor Panel (`VendorPanelProvider`) agar fitur berbayar Pro dapat diakses secara langsung.

---

## 2. Prioritas P1 (Alur Inti & Pengalaman Pengguna)
- [ ] **BL-005 (BUG-003):** Sesuaikan logika `EntitlementService::isPro()` agar memperhitungkan toleransi masa tenggang (*grace period*) 7 hari saat langganan berstatus `past_due`.
- [ ] **BL-006 (BUG-005):** Atur strategi penyimpanan terpisah atau naikkan TTL temporary signed URL untuk foto katalog barang publik menjadi 24 jam dengan header `Cache-Control` yang aman agar tidak terjadi HTTP 403 (*broken images*).
- [ ] **BL-007 (A11y & Anti-Slop):** Bersihkan seluruh emoji dari UI produksi (15+ titik temuan) dan gantikan dengan ikon Heroicon/Lucide resmi sesuai `docs/global-rules.md`.
- [ ] **BL-008 (A11y Touch Target):** Perbesar ukuran tombol navigasi kalender publik (`min-h-[38px]` dan `w-8 h-8`) menjadi minimal 44×44px sesuai WCAG 2.5.5 dan token JagaSewa.
- [ ] **BL-009:** Tambahkan atribut `for` dan `id` pada label formulir unggah KTP dan selfie di `resources/views/livewire/public/booking-form.blade.php`.
- [ ] **BL-010:** Tambahkan kolom unggah foto bukti kerusakan barang pada formulir `BookingSettlementForm`.

---

## 3. Prioritas P2 (Peningkatan Kualitas Kode & Pemeliharaan)
- [ ] **BL-011:** Pecah berkas raksasa `resources/views/welcome.blade.php` (1.490 baris) menjadi modul-modul parsial di `resources/views/public/welcome/`.
- [ ] **BL-012:** Gantikan penulisan tombol raw HTML berulang dengan komponen reusable `<x-ui.button>`, `<x-ui.card>`, dan `<x-ui.alert>`.
- [ ] **BL-013 (BUG-007):** Tambahkan accessor `getPrimaryPhotoUrlAttribute(): ?string` pada model `Item.php`.
- [ ] **BL-014 (BUG-004):** Pasang polling reaktif Livewire ringan (`wire:poll.3s`) pada widget dashboard vendor saat terdapat pesanan langganan baru berstatus `pending_payment`.
- [ ] **BL-015:** Sediakan tampilan alternatif linimasa/agenda view pada kalender booking vendor untuk layar ponsel sempit (375px).
