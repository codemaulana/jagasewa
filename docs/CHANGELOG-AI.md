# CHANGELOG-AI — JagaSewa

Dokumen riwayat penambahan fitur, perubahan skema, mitigasi bug, dan kepatuhan aturan bisnis yang dikerjakan oleh AI Agent.

---

## [Gelombang 1: Inti Operasional Vendor] — 22 Oktober 2026

### 1. Ringkasan Ruang Lingkup
Implementasi lengkap end-to-end fitur Inti Operasional Vendor sesuai roadmap `docs/audit/03-feature-gap.md` dengan pembatasan hak akses berbasis helper entitlement backend (`canUse`, `limitFor`, `isPro`).

### 2. Perubahan Database & Migrasi
- **Migrasi:** `database/migrations/2026_10_22_000012_add_operational_features_to_items_and_bookings.php`
  - **Tabel `items`:**
    - `category`: Kategori rental barang (`Kostum & Cosplay`, `Kebaya & Busana Adat`, `Kamera & Lensa`, `Gaun & Pakaian Pesta`, `Outdoor & Camping`, `Elektronik & Audio`, `Lainnya`).
    - `item_code`: Kode unik inventaris toko / SKU / barcode (misal: `CAM-001`, `KOS-02`).
    - `package_price_3d`: Tarif khusus sewa paket durasi 3 hari (weekend / bundling), nullable integer rupiah.
    - `condition`: Kondisi fisik barang (`Sangat Baik`, `Baik`, `Cukup`, `Perlu Perbaikan`).
    - `unit_status`: Status operasional unit (`available`, `rented`, `laundry`, `maintenance`).
  - **Tabel `bookings`:**
    - `down_payment_amount`: Nilai rupiah pembayaran uang muka (DP).
    - `is_dp_paid`, `dp_paid_at`: Flag dan timestamp pelunasan uang muka.
    - `is_paid_in_full`, `paid_in_full_at`: Flag dan timestamp pelunasan penuh biaya sewa + deposit.
    - `payment_proof_path`: Path file bukti transfer/kwitansi (storage privat S3).
    - `payment_notes`: Catatan rekening bank / metode transfer pembayaran.
    - `handover_method`: Pilihan metode serah terima (`pickup`, `instant_courier`, `delivery_courier`).
    - `tracking_number`: Nomor resi pengiriman / tautan pelacakan kurir instan.
    - `handover_checklist`: Array JSON checklist kondisi fisik dan kelengkapan awal saat diserahkan.
    - `handover_notes`: Catatan kondisi unit saat penyerahan.
    - `handover_photos`: Array JSON path dokumentasi foto barang sebelum sewa.
    - `return_checklist`: Array JSON checklist kondisi barang saat diterima kembali.
    - `return_notes`: Catatan kondisi fisik saat pengembalian.
    - `return_photos`: Array JSON path dokumentasi foto barang sesudah sewa / bukti kerusakan.
  - **Tabel `vendors`:**
    - `category_buffer_settings`: Konfigurasi JSON default jeda buffer per kategori barang.

### 3. Model & Logika Bisnis
- **`app/Models/Item.php`:**
  - Menambahkan konstanta `CATEGORIES`, `CONDITIONS`, `UNIT_STATUS_*`.
  - Menambahkan casts integer rupiah untuk `package_price_3d`.
- **`app/Models/Booking.php`:**
  - Casts JSON array untuk `handover_checklist`, `handover_photos`, `return_checklist`, `return_photos`.
  - Accessors: `payment_proof_url`, `handover_photo_urls`, `return_photo_urls` dengan signed temporary URL S3 privat (TTL 60 menit).
- **`app/Models/Vendor.php`:**
  - Menambahkan helper `getCategoryBuffer(string $category)` dengan fallback default 1 hari buffer sebelum dan sesudah.
- **`config/plans.php`:**
  - Memperbarui batas kuota `active_items` paket Free dari 5 menjadi 15 barang aktif sesuai roadmap Gelombang 1.

### 4. Antarmuka Vendor (UI / UX)
- **Beranda Vendor (`RentalStatsOverviewWidget` & `TopRentedItemsWidget`):**
  - **Statistik Operasional Harian:** Kartu "Tugas Hari Ini" (kalkulasi dinamis: pengambilan hari ini, pengembalian hari ini, pengembalian terlambat/overdue), "Pendapatan Bulan Ini" (rupiah tabular integer), dan "Menunggu Konfirmasi".
  - **Analitik Barang Paling Sering Disewa:**
    - **Free:** Menampilkan barang peringkat #1 dan kartu edukatif fitur Pro tanpa tombol mati.
    - **Pro:** Menampilkan tabel analitik lengkap 5 besar barang dengan kategori, frekuensi sewa, dan akumulasi omzet.
- **Katalog Barang (`ItemForm`, `ItemsTable`, `ItemInfolist`):**
  - Input kategori, kode inventaris, status operasional, paket harga 3 hari, kelengkapan komponen (`TagsInput`), petunjuk perawatan, dan kondisi fisik.
  - Galeri foto multi-upload (hingga 5 foto untuk Free, hingga 8 foto untuk Pro) dengan reorderable drag-and-drop.
  - Notifikasi visual ramah untuk barang berstatus *read-only* jika akun vendor melebihi batas 15 barang.
  - Tab riwayat sewa per barang (`rental-history.blade.php`) di halaman infolist barang.
- **Operasional Booking & Serah-Terima (`ViewBooking` & `BookingInfolist`):**
  - Aksi `record_payment`: Catat pembayaran DP atau pelunasan penuh, nominal rupiah, upload bukti transfer privat S3, dan catatan rekening dalam transaksi database dengan `lockForUpdate()`.
  - Aksi `activate` (Serah Terima): Catat metode pengambilan (pickup/instant courier/ekspedisi), nomor resi/tracking link, checklist kelengkapan awal, dan upload multi-foto kondisi sebelum sewa.
  - Aksi `settle` (Penyelesaian & Denda): Perhitungan otomatis denda telat + kalkulator kerusakan multi-item dengan checklist penerimaan kembali dan upload multi-foto bukti kerusakan.
  - Komponen Linimasa Aktivitas (`activity-timeline.blade.php`): Visualisasi tahapan pesanan dari diajukan, disetujui, pembayaran DP/lunas, serah-terima, dikembalikan, hingga closed.
  - Komponen Inspeksi Foto (`inspection-photos.blade.php`): Komparasi foto kondisi unit sebelum sewa dan sesudah sewa.
- **Kalender Sewa Vendor (`VendorBookingCalendar`):**
  - Switcher tampilan **Bulan** dan **Minggu**.
  - Smart buffer status indicator: Banner aktif untuk Pro, teaser edukatif untuk Free.
  - Filter barang responsif dengan touch target ≥ 44px.

### 5. Pengujian & Kualitas Kode
- **Test Baru:** `tests/Feature/OperationalVendorCoreTest.php` (6 test, 58 assertions):
  - `test_rental_stats_overview_widget_today_tasks_and_monthly_revenue`
  - `test_top_rented_items_widget_free_vs_pro`
  - `test_item_catalog_attributes_and_15_item_limit`
  - `test_booking_payment_and_handover_workflow`
  - `test_booking_return_and_settlement_with_checklist`
  - `test_calendar_page_and_category_buffer_integration`
- **Pembaruan Test Eksisting:**
  - `VendorDashboardWidgetsTest`: Disesuaikan dengan kartu operasional baru.
  - `VendorCalendarPageTest`: Verifikasi Livewire single root template.
  - `EntitlementCheckTest` & `SubscriptionLifecycleAndRegressionsTest`: Batas 15 barang aktif pada paket Free.

---

## [Gelombang 2: Kepercayaan dan Uang] — 22 Oktober 2026

### 1. Ringkasan Ruang Lingkup
Implementasi lengkap end-to-end fitur Kepercayaan dan Uang mencakup manajemen pelanggan (penyewa) milik vendor dengan isolasi data ketat, Blacklist Bersama terenkripsi HMAC-SHA256 dengan batas kuota pencarian & pelaporan Pro, kalkulator denda keterlambatan/kerusakan/kehilangan otomatis [P] & manual [F], dokumen resmi PDF Invoice & Surat Perjanjian Sewa [P], dan kustomisasi template WhatsApp dengan variabel dinamis.

### 2. Perubahan Database & Migrasi
- **Migrasi:** `database/migrations/2026_10_22_000013_create_gelombang_2_trust_and_money_tables.php`
  - **Tabel `vendor_renters`:**
    - `vendor_id`, `renter_id` (foreign keys, unique constraint `uq_vendor_renter`).
    - `internal_notes`: Catatan/memo rahasia vendor mengenai penyewa.
    - `reliability_tag`: Status keandalan penyewa (`trusted`, `normal`, `caution`, `problematic`).
    - `total_completed_rentals`, `total_spent`: Agregasi transaksi penyewa pada vendor tersebut.
  - **Tabel `blacklist_entries`:**
    - `masked_identity`: Versi aman tersamarkan (contoh: `62812****7890`, `3201**********01`) agar tidak membocorkan PII ke vendor lain.
    - `verification_status`: Status verifikasi (`verified`, `pending_review`, `disputed`, `rejected`).
    - `evidence_file_path`: Path bukti foto/nota sengketa di storage privat S3.
    - `is_disputed`, `dispute_reason`, `disputed_at`, `dispute_status`: Bidang penanganan mekanisme sanggahan.
  - **Tabel `vendor_blacklist_checks`:**
    - `vendor_id`, `month_year` (`YYYY-MM`), `checks_count`: Pelacak kuota pencarian bulanan (Free: 10 cek/bulan, Pro: tanpa batas).
  - **Tabel `vendors`:**
    - `fine_rules`: Konfigurasi JSON aturan denda otomatis (`grace_hours`, `late_rate_type`, `hourly_rate`).

### 3. Model & Logika Bisnis
- **`app/Models/VendorRenter.php`:**
  - Relasi `vendor()` dan `renter()`.
  - Metode `recalculateMetrics()`: Menghitung metrik sewa dan mengkategorikan tingkat reliabilitas secara otomatis (`trusted`, `normal`, `caution`, `problematic`).
- **`app/Models/BlacklistEntry.php`:**
  - Helper `maskValue(type, raw)`: Menyamarkan nomor telepon, NIK, dan akun IG untuk direktori komunitas.
  - Accessor `evidence_url`: Signed temporary URL S3 privat (TTL 60 menit).
- **`app/Services/BlacklistService.php`:**
  - `searchBlacklist(vendor, type, value)`: Pencarian lintas blacklist toko dan komunitas bersama dengan penegakan kuota 10 cek/bulan untuk Free dan tanpa batas untuk Pro. Mencegah kebocoran PII dan nama pelapor lain.
  - `reportToSharedBlacklist(vendor, data)`: Fitur pelaporan eksklusif Pro dengan bukti pendukung.
  - `submitDispute(ulid, reason)`: Mekanisme pengajuan sanggahan terhadap entri blacklist.
- **`app/Services/FineCalculatorService.php`:**
  - `calculateAdvanced(...)`: Mendukung kalkulasi keterlambatan per hari dan per jam prorata dengan toleransi jam gratis (*grace period*).
  - Mendukung kalkulasi gabungan denda telat, kerusakan (*damage*), dan kehilangan unit/aksesoris (*loss*).
  - `calculateForBookingWithVendorRules(...)`: Otomasi aturan denda untuk vendor Pro.
- **`app/Services/PdfDocumentService.php`:**
  - Generator PDF standar (PDF 1.4 binary) mandiri tanpa dependensi pihak ketiga (`%PDF-1.4 ... %%EOF`).
  - Menghasilkan **Invoice Sewa** dan **Surat Perjanjian Sewa Menyewa** resmi dengan hak akses eksklusif Pro.
- **`app/Helpers/WaNotificationHelper.php`:**
  - Mendukung variabel alias pendek: `{nama}`, `{barang}`, `{tanggal}`, `{total}` selain tag lengkap.

### 4. Antarmuka Vendor (UI / UX)
- **Manajemen Pelanggan (`RenterResource`):**
  - Rute `/vendor/renters` dengan isolasi vendor ketat (hanya menampilkan penyewa yang pernah sewa di toko vendor tersebut).
  - Kolom: Nama, WhatsApp, status reliabilitas badge token, total pesanan, dan akumulasi omzet.
  - Aksi `edit_notes`: Modal pembaruan catatan internal vendor dan penanda reliabilitas.
  - Infolist: Riwayat pesanan sewa pelanggan di toko vendor bersangkutan.
- **Pencarian & Pelaporan Blacklist Bersama (`BlacklistEntryResource` & `ListBlacklistEntries`):**
  - Aksi header "Cek Blacklist Bersama": Modal pencarian instan dengan pelacak sisa kuota bulanan.
  - Dukungan pelaporan cakupan "Internal Toko" vs "Komunitas Bersama [Pro]" dengan unggah bukti S3.
  - Kolom identitas tersamarkan dan status verifikasi di tabel.
- **Pengaturan Toko (`StoreSettings`):**
  - Section baru "Aturan Denda Otomatis & Toleransi Pengembalian (Fitur Pro)": Toleransi jam gratis (*grace hours*), pilihan tarif harian/per jam, dan tarif per jam.
  - Teaser edukatif bagi pengguna paket Free.
- **Dokumen Booking PDF & Cetak:**
  - Aksi "Invoice (PDF)" dan "Surat Perjanjian (PDF)" di halaman rincian booking vendor (`ViewBooking.php`).
  - Halaman cetak ramah cetak (`resources/views/documents/invoice.blade.php` dan `rental-agreement.blade.php`) dengan tombol `window.print()` dan unduh file PDF langsung.
- **Deteksi Otomatis Saat Booking Masuk (`BookingForm`):**
  - Saat penyewa mengirim permohonan sewa di etalase publik, sistem otomatis mencocokkan nomor WA / NIK ke database blacklist dan menyematkan peringatan sistem pada `vendor_notes`.

### 5. Pengujian & Kualitas Kode
- **Test Baru:** `tests/Feature/Gelombang2TrustAndMoneyTest.php` (7 test, 91 assertions):
  - `test_vendor_customer_list_and_internal_notes_with_isolation`
  - `test_renter_reliability_recalculated_automatically`
  - `test_shared_blacklist_search_quota_free_vs_pro`
  - `test_pro_vendor_shared_blacklist_reporting_and_dispute`
  - `test_advanced_fine_calculator_hourly_and_loss_deductions`
  - `test_document_generation_entitlement_and_pdf_binary`
  - `test_wa_template_tag_replacements`

---

## [Gelombang 3: Pertumbuhan] — 22 Oktober 2026

### 1. Ringkasan Ruang Lingkup
Implementasi lengkap end-to-end fitur Pertumbuhan sesuai roadmap `docs/audit/03-feature-gap.md`:
1. **Laporan & Analitik [Pro]:** Pendapatan per periode, utilisasi barang (hari disewa vs menganggur/idle, rasio utilisasi %), pelanggan terbaik (*top renters*), dan ekspor data ke CSV.
2. **Multi-Staf Toko [Pro]:** Penambahan staf operasional dengan peran terbatas (`owner` vs `operational`). Staf operasional tidak dapat melihat data omzet/finansial atau mengelola tagihan/pengaturan toko. Batasan kuota staf ditegakkan di backend: Free 1 staf (owner), Pro hingga 5 staf.
3. **Kustomisasi Toko & White-Label [Pro]:** Banner toko, domain sendiri (*custom domain*), dan penghapusan watermark/branding "Didukung oleh JagaSewa" (*white-label*) pada etalase katalog publik dan halaman detail barang.

### 2. Perubahan Database & Migrasi
- **Migrasi:** `database/migrations/2026_10_22_000014_add_growth_features_to_vendors_table.php`
  - **Tabel `vendors`:**
    - `custom_domain`: Subdomain atau domain kustom toko (string 150, unique, nullable, indexed).
    - `custom_domain_verified`: Status verifikasi DNS CNAME domain kustom (boolean, default false).
    - `white_label_enabled`: Saklar kendali aktivasi white-label branding toko (boolean, default true).

### 3. Model & Logika Bisnis
- **`app/Models/Vendor.php`:**
  - Menambahkan kolom `custom_domain`, `custom_domain_verified`, `white_label_enabled` ke `$fillable` dan casts boolean.
  - Helper `isWhiteLabelActive()`: Mengembalikan `true` hanya jika vendor memiliki paket Pro dan `white_label_enabled` aktif.
- **`app/Models/VendorUser.php`:**
  - Menambahkan konstanta peran `ROLE_OWNER = 'owner'` dan `ROLE_OPERATIONAL = 'operational'`.
  - Helper otorisasi: `isOperational()`, `canViewFinancials()`, `canManageSettings()`.
- **`app/Http/Controllers/Public/ItemController.php`:**
  - Metode `catalog` dan `show` mendukung pencarian vendor via `slug` maupun `custom_domain`.
- **`app/Http/Controllers/Vendor/BillingController.php`:**
  - Melindungi rute `index`, `checkout`, `cancelPending`, dan `exportReports` dari akses staf operasional (HTTP 403 Forbidden).
  - Metode `exportReports` mendukung filter rentang tanggal (`start_date`, `end_date`), nama penyewa, durasi sewa, total harga, dan deposit.

### 4. Antarmuka Vendor & Publik (UI / UX)
- **Halaman Laporan & Analitik (`app/Filament/Vendor/Pages/Reports.php` & `reports.blade.php`):**
  - Rute `/vendor/reports` dengan filter periode instan (Bulan Ini, 7 Hari Terakhir, 30 Hari Terakhir, Bulan Lalu).
  - Kartu metrik: Total Pendapatan, Total Booking Selesai, Total Hari Sewa, dan Rata-rata Utilisasi Toko.
  - Tabel Utilisasi Barang: Hari Tersewa, Hari Menganggur (Idle), Persentase Utilisasi, Total Omzet, dan Jumlah Transaksi per unit.
  - Tabel 5 Penyewa Terbaik: Nama pelanggan, nomor kontak, jumlah booking selesai, dan total kontribusi omzet.
  - Tautan unduh CSV streaming langsung dengan rentang tanggal aktif.
  - Tampilan edukasi teaser Pro untuk vendor paket Free.
- **Manajemen Multi-Staf (`VendorUserResource` & Pages):**
  - Hak akses resource dibatasi hanya untuk pemilik toko (`owner`).
  - Halaman Create/Edit menampilkan pilihan peran: Pemilik Toko (`owner`) vs Staf Operasional (`operational`).
  - Penegakan kuota staf (`staff_users`) di backend: Free 1 staf, Pro hingga 5 staf.
  - Tombol tambah staf disembunyikan otomatis jika kuota telah tercapai.
- **Sensor Data Finansial Staf Operasional:**
  - Widget dashboard `RentalStatsOverviewWidget` secara otomatis menyembunyikan kartu "Pendapatan Bulan Ini" dan menggantinya dengan "Pesanan Sedang Aktif" saat akun staf operasional sedang login.
  - Akses navigasi ke halaman Laporan Finansial dan Pengaturan Toko disembunyikan bagi staf operasional via `canAccess()`.
- **Pengaturan Toko (`StoreSettings.php`):**
  - Section baru "Kustomisasi Domain & White-Label (Fitur Pro)".
  - Form input CNAME domain kustom dan toggle white-label.
- **Etalase Publik & Halaman Barang:**
  - Halaman katalog publik (`resources/views/public/vendor/catalog.blade.php`) dan halaman detail barang (`items/show.blade.php`) secara otomatis menyembunyikan footer "Didukung oleh JagaSewa" dan watermark jika vendor Pro mengaktifkan white-label.

### 5. Pengujian & Kualitas Kode
- **Test Baru:** `tests/Feature/Gelombang3GrowthTest.php` (5 test, 51 assertions):
  - `test_multi_staf_quota_limits_free_vs_pro`
  - `test_pro_vendor_can_add_operational_staff`
  - `test_operational_staff_role_financial_masking_and_access_restrictions`
  - `test_reports_analytics_calculation_and_csv_export`
  - `test_custom_domain_and_white_label_branding_suppression`
- **Hasil Uji:** 408 tests lulus 100% tanpa kegagalan (2.255 assertions). Linting bersih via Laravel Pint.


