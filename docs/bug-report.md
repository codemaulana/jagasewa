# Laporan Bug & Temuan Audit (Stabilisasi) — JagaSewa

> Dokumen ini disusun pada tahap **FASE STABILISASI: AUDIT KEBENARAN** (19 Oktober 2026).
> Menginventarisasi semua bug, celah finansial, kegagalan alur inti, dan masalah UX yang ditemukan setelah pengujian langsung pada aplikasi dan penelusuran 5 *User Journeys*.

---

## 1. Ringkasan Temuan Bug

| Prioritas | Definisi | Jumlah |
|-----------|----------|--------|
| **P0** | Kritis: Menyangkut integritas uang, kebocoran data, atau celah keamanan | **1** |
| **P1** | Tinggi: Alur pengguna inti rusak, fitur penting hilang, atau data gagal disimpan | **3** |
| **P2** | Sedang: Masalah UX, tampilan rusak, aset hilang, atau visualisasi kurang optimal | **2** |
| **Total** | | **6** |

---

## 2. Tabel Rincian Bug

| ID | Prioritas | Alur Terkait | Judul Masalah | Dampak Bisnis / Pengguna | Status |
|----|-----------|--------------|---------------|--------------------------|--------|
| **BUG-01** | **P0** (Uang/Finansial) | **J5** (Siklus Langganan) | Langganan Pro dapat dibeli berkali-kali tanpa pembatasan (*Double Subscription*) | Vendor terpotong biaya berkali-kali; *dangling records* di tabel `subscriptions` | Terkonfirmasi |
| **BUG-02** | **P1** (Alur Inti) | **J1** (Katalog Barang) | Form edit barang gagal memperbarui/mengganti foto pada disk S3 privat | Vendor tidak bisa mengganti foto lama barang rental; risiko foto lama hilang | Terkonfirmasi |
| **BUG-03** | **P2** (UX/Tampilan) | **J1, J3, J5** (Dashboard) | Beranda dashboard vendor memuat widget default Filament dan avatar rusak | Tampilan tidak profesional, membingungkan vendor baru, tidak ada ringkasan operasional | Terkonfirmasi |
| **BUG-04** | **P1** (Alur Inti) | **J1** (Profil Toko) | Tidak ada UI/Halaman Pengaturan Profil Toko (Logo, Banner, Deskripsi, No. WA Toko) | Vendor tidak bisa mengkustomisasi etalase `/b/{slug}` atau memperbarui kontak WA setelah registrasi | Terkonfirmasi |
| **BUG-05** | **P2** (UX/Operasional) | **J3** (Kalender Vendor) | Tidak ada tampilan kalender visual jadwal booking di sisi vendor | Vendor kesulitan memantau slot sewa dan jadwal laundry/buffer harian secara visual | Terkonfirmasi |
| **BUG-06** | **P1** (Nilai Produk) | **J5** (Fitur Premium) | Fitur Pro (Kustom Template WA & Kapasitas Foto) dijanjikan tapi belum diimplementasikan | Vendor yang sudah membayar Pro merasa tertipu karena tidak ada fitur baru yang terbuka | Terkonfirmasi |

---

## 3. Detail Analisis per Bug

### BUG-01: Langganan Pro Dapat Dibeli Berkali-Kali (*Double Subscription*)
- **Prioritas:** `P0` (Finansial / Integritas Transaksi)
- **Alur Terkait:** `J5` (Vendor Free upgrade ke Premium -> bayar -> coba berlangganan lagi)
- **Langkah Reproduksi:**
  1. Login sebagai vendor yang masih di paket Free.
  2. Buka halaman billing `/vendor/billing`.
  3. Klik tombol "Pilih Paket" (misal Bulanan Rp 15.000).
  4. Sebelum/sesudah membayar, buka kembali `/vendor/billing` di tab lain.
  5. Klik tombol "Pilih Paket" lagi (atau siklus lain seperti Kuartalan/Tahunan).
  6. Periksa database tabel `subscriptions`.
- **Hasil Aktual:**
  Sistem membuat record baru di tabel `subscriptions` dengan status `pending_payment` atau bahkan membuat payment link baru. Jika vendor sudah berstatus `pro` aktif, tombol pembayaran tetap aktif dan form checkout tetap memproses pembuatan subscription baru tanpa penolakan.
- **Hasil Diharapkan:**
  1. Jika vendor sudah memiliki langganan Pro aktif (`is_pro = true`), tombol checkout dinonaktifkan dan diganti dengan status masa aktif: *"Anda sedang menikmati Paket Pro hingga [Tanggal]"*.
  2. Jika ada pesanan pending pembayaran yang masih valid, vendor diarahkan ke tautan pembayaran yang sudah ada atau diberi tombol *"Batalkan Pesanan Sebelumnya"* sebelum membuat baru.
  3. Di backend (`BillingController::checkout`), wajib dibungkus dalam `DB::transaction()` dengan pessimistic locking `SELECT ... FOR UPDATE` pada baris `vendors` untuk mencegah *race condition* (klik dobel simultan).
- **Dugaan Akar Masalah:**
  Pada [`BillingController.php`](file:///c:/DataCoding/my-saas/app/Http/Controllers/Vendor/BillingController.php#L136-L144), method `checkout()` langsung memanggil `Subscription::create(...)` tanpa mengecek `entitlement->isPro($vendor)` atau status subscription aktif/pending sebelumnya, serta tanpa transaksi dan row-level lock.
- **File Terkait:**
  - [`app/Http/Controllers/Vendor/BillingController.php`](file:///c:/DataCoding/my-saas/app/Http/Controllers/Vendor/BillingController.php)
  - [`resources/views/vendor/billing/index.blade.php`](file:///c:/DataCoding/my-saas/resources/views/vendor/billing/index.blade.php)
  - [`app/Services/Billing/EntitlementService.php`](file:///c:/DataCoding/my-saas/app/Services/Billing/EntitlementService.php)

---

### BUG-02: Foto Barang Gagal Diperbarui/Diganti pada Form Edit Barang
- **Prioritas:** `P1` (Alur Inti Rusak)
- **Alur Terkait:** `J1` (Vendor login -> atur toko -> tambah barang -> edit dan ganti foto)
- **Langkah Reproduksi:**
  1. Login ke dashboard vendor di `/vendor`.
  2. Buka menu **Items** (`/vendor/items`).
  3. Buka salah satu barang yang sudah memiliki foto, klik tombol **Edit**.
  4. Coba hapus salah satu foto yang ada di thumbnail galeri, lalu unggah foto pengganti baru.
  5. Klik tombol **Simpan Perubahan** (*Save*).
  6. Buka kembali halaman barang tersebut atau buka halaman publik `/b/{vendor_slug}/{item_slug}`.
- **Hasil Aktual:**
  Preview foto lama di form Filament pecah/gagal dimuat karena disk S3 privat Backblaze tidak mengizinkan akses URL langsung tanpa signed URL. Saat foto baru diunggah dan disimpan, Livewire me-reset array `photo_paths` atau menyebabkan foto lama terhapus/gagal tersinkronisasi dengan benar.
- **Hasil Diharapkan:**
  - Foto yang sudah ada tampil rapi menggunakan temporary signed preview URL.
  - Vendor dapat menghapus foto spesifik tanpa merusak foto lainnya.
  - Foto baru terunggah ke S3 privat, terdaftar di array `photo_paths`, dan foto lama yang dihapus dibersihkan dari storage.
- **Dugaan Akar Masalah:**
  1. Di [`ItemForm.php`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php#L162-L173), komponen `FileUpload` menggunakan disk S3 privat (`visibility('private')`) tanpa penanganan *temporary preview URL* bawaan Filament untuk disk privat S3.
  2. Tes yang ada di [`VendorItemCrudTest.php`](file:///c:/DataCoding/my-saas/tests/Feature/VendorItemCrudTest.php#L185-L214) hanya menguji pembaruan teks `name` dan `price_per_day`, tanpa pernah menyertakan pengujian penggantian file foto pada halaman edit.
- **File Terkait:**
  - [`app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php)
  - [`app/Filament/Vendor/Resources/Items/Pages/EditItem.php`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Items/Pages/EditItem.php)
  - [`tests/Feature/VendorItemCrudTest.php`](file:///c:/DataCoding/my-saas/tests/Feature/VendorItemCrudTest.php)

---

### BUG-03: Dashboard Vendor Menampilkan Widget Bawaan Filament & Avatar Rusak
- **Prioritas:** `P2` (UX & Desain Antarmuka)
- **Alur Terkait:** `J1, J3, J5` (Semua alur vendor setelah login)
- **Langkah Reproduksi:**
  1. Buka browser dan login ke `/vendor/login`.
  2. Begitu diarahkan ke halaman utama `/vendor` (Dashboard), amati halaman tersebut.
- **Hasil Aktual:**
  1. Terdapat widget bawaan Filament: `AccountWidget` bertuliskan "Welcome, [Nama]" dan `FilamentInfoWidget` yang memuat link dokumentasi dan versi Filament.
  2. Gambar avatar pengguna di samping nama bertanda broken icon / gambar pecah karena `VendorUser` tidak mengimplementasikan interface `HasAvatar` atau method avatar URL yang valid.
  3. Tidak ada informasi operasional sama sekali (tidak tahu berapa order yang menunggu di-approve, barang apa yang harus dikirim hari ini, dan berapa kuota tersisa).
- **Hasil Diharapkan:**
  1. Widget bawaan Filament dihapus seutuhnya.
  2. Menampilkan widget operasional rental JagaSewa:
     - **Kartu Tautan Toko:** Tautan publik toko (`/b/{slug}`) dengan tombol 1-klik "Salin Tautan".
     - **Statistik Cepat (Stats Overview):** Booking Menunggu Persetujuan, Booking Aktif (Sedang Disewa), Pengembalian Hari Ini.
     - **Status Langganan:** Paket Free/Pro dan kuota barang aktif yang terpakai.
     - **Checklist Onboarding:** Langkah awal vendor (Lengkapi Toko → Tambah Barang → Bagikan Link).
  3. Avatar pengguna menampilkan inisial teks bersih (fallback) atau logo toko yang valid.
- **Dugaan Akar Masalah:**
  File [`VendorPanelProvider.php`](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php#L51-L54) secara eksplisit mendaftarkan `AccountWidget::class` dan `FilamentInfoWidget::class`, serta belum ada custom widgets khusus untuk domain rental.
- **File Terkait:**
  - [`app/Providers/Filament/VendorPanelProvider.php`](file:///c:/DataCoding/my-saas/app/Providers/Filament/VendorPanelProvider.php)
  - [`app/Models/VendorUser.php`](file:///c:/DataCoding/my-saas/app/Models/VendorUser.php)

---

### BUG-04: Tidak Ada Halaman/UI Pengaturan Toko (Store Profile Settings)
- **Prioritas:** `P1` (Alur Inti / Celah Produk)
- **Alur Terkait:** `J1` (Vendor daftar/login -> atur toko)
- **Langkah Reproduksi:**
  1. Login ke panel vendor `/vendor`.
  2. Cari menu navigasi di sidebar untuk memperbarui nomor WhatsApp toko, alamat pengembalian barang, nomor rekening penerima deposit, atau banner toko.
- **Hasil Aktual:**
  Tidak ada menu atau halaman pengaturan toko. Data toko hanya dapat diisi sekali saat registrasi awal dan tidak bisa diubah kembali oleh vendor lewat UI dashboard.
- **Hasil Diharapkan:**
  Terdapat halaman "Pengaturan Toko" (`StoreSettings`) di panel Filament yang memungkinkan vendor mengubah:
  - Nama toko & slug publik
  - Nomor WhatsApp resmi toko
  - Alamat toko / lokasi pengambilan barang
  - Informasi rekening bank (nama bank, nomor rekening, atas nama) untuk instruksi transfer sewa/deposit
  - Logo toko & banner etalase
- **Dugaan Akar Masalah:**
  Saat Milestone 1 dan Milestone 2 dibuat, task hanya berfokus pada CRUD barang (`ItemResource`) dan daftar booking (`BookingResource`), tanpa membuat resource atau halaman Filament untuk profil toko vendor.
- **File Terkait:**
  - [`app/Filament/Vendor/Pages/`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Pages/)
  - [`app/Models/Vendor.php`](file:///c:/DataCoding/my-saas/app/Models/Vendor.php)
  - [`database/migrations/2026_10_18_000001_create_vendors_table.php`](file:///c:/DataCoding/my-saas/database/migrations/2026_10_18_000001_create_vendors_table.php)

---

### BUG-05: Tidak Ada Kalender Booking Visual di Sisi Vendor
- **Prioritas:** `P2` (UX / Kebutuhan Operasional Rental)
- **Alur Terkait:** `J3` (Vendor review dan kelola siklus sewa)
- **Langkah Reproduksi:**
  1. Login ke `/vendor`.
  2. Buka menu Bookings.
- **Hasil Aktual:**
  Vendor hanya disajikan tabel daftar booking berbentuk baris teks (`BookingsTable`). Tidak ada visualisasi kalender bulanan yang menunjukkan kapan barang disewa, kapan masuk jeda persiapan (buffer before), dan kapan jeda laundry/inspeksi (buffer after).
- **Hasil Diharapkan:**
  Terdapat tab atau halaman kalender interaktif di panel vendor yang menampilkan jadwal booking per barang secara visual dalam kisi kalender bulanan, dengan warna berbeda untuk hari sewa, buffer, dan pengembalian.
- **Dugaan Akar Masalah:**
  Implementasi kalender Livewire (`Livewire/BookingCalendar.php`) hanya dibuat untuk halaman penyewa publik (`/b/{vendor}/{item}`), belum dibuatkan halaman kalender visual untuk sisi panel vendor.
- **File Terkait:**
  - [`app/Filament/Vendor/Resources/Bookings/`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Bookings/)
  - [`app/Services/BookingCalendarService.php`](file:///c:/DataCoding/my-saas/app/Services/BookingCalendarService.php)

---

### BUG-06: Fitur Paket Pro Dijanjikan di Halaman Billing Namun Belum Dibangun
- **Prioritas:** `P1` (Integritas Produk & Monetisasi)
- **Alur Terkait:** `J5` (Vendor upgrade ke Pro -> cek fitur Pro)
- **Langkah Reproduksi:**
  1. Buka halaman billing `/vendor/billing`.
  2. Baca poin fitur Paket Pro:
     - *"Kustom template chat WhatsApp"*
     - *"6–8 foto per barang"*
  3. Lakukan upgrade akun vendor ke Pro.
  4. Coba edit template WhatsApp atau unggah 8 foto pada barang rental.
- **Hasil Aktual:**
  - Tidak ada UI untuk mengedit template WhatsApp. Notifikasi chat WhatsApp tetap menggunakan teks baku sistem yang di-hardcode di [`WaNotificationHelper.php`](file:///c:/DataCoding/my-saas/app/Helpers/WaNotificationHelper.php).
  - Form upload barang di [`ItemForm.php`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php#L169) memiliki batas `maxFiles(5)` yang bersifat kaku (*hardcoded*) untuk semua vendor, baik Free maupun Pro.
- **Hasil Diharapkan:**
  - Vendor Pro dapat menyusun template pesan WhatsApp dengan variabel dinamis (misal: `{nama_penyewa}`, `{nama_barang}`, `{total_sewa}`, `{rekening_toko}`).
  - Form barang mengizinkan hingga 8 foto untuk vendor Pro, dan maksimal 5 foto untuk vendor Free.
- **Dugaan Akar Masalah:**
  Fitur entitlement backend (`EntitlementService`) baru mendefinisikan method `canCustomizeWaTemplate()` secara boolean, tetapi tidak pernah dibuatkan skema penyimpanan DB, input UI, maupun integrasi ke `WaNotificationHelper`.
- **File Terkait:**
  - [`app/Services/Billing/EntitlementService.php`](file:///c:/DataCoding/my-saas/app/Services/Billing/EntitlementService.php)
  - [`app/Helpers/WaNotificationHelper.php`](file:///c:/DataCoding/my-saas/app/Helpers/WaNotificationHelper.php)
  - [`app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php`](file:///c:/DataCoding/my-saas/app/Filament/Vendor/Resources/Items/Schemas/ItemForm.php)
