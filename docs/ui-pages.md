# Inventaris & Tata Letak Halaman JagaSewa (docs/ui-pages.md)

> Dokumen inventaris lengkap seluruh layar antarmuka JagaSewa, mencakup tujuan layar, komponen yang digunakan, tata letak responsif per breakpoint (Mobile, Tablet, Desktop), serta penanganan seluruh status interaksi (Empty, Loading, Error, Success).

---

## 1. Ikhtisar Arsitektur Halaman

| Jalur URL | Nama Halaman | Peran Pengguna | Engine / Renderer |
| :--- | :--- | :--- | :--- |
| `/` | Halaman Pengenalan (Landing Page) | Pengunjung Umum | Blade Template |
| `/b/{vendor_slug}` | Katalog Publik Toko Vendor | Penyewa (Anonim) | Blade Template |
| `/b/{vendor_slug}/{item_slug}` | Detail Barang, Kalender & Form Booking | Penyewa (Anonim) | Blade + Livewire 4 |
| `/b/success/{ulid}` | Konfirmasi Sukses & Pengalihan WhatsApp | Penyewa (Anonim) | Blade Template |
| `/vendor/login` | Masuk Akun Vendor | Pengelola Rental | Filament Auth |
| `/vendor/register` | Pendaftaran Akun & Toko Rental | Pemilik Rental Baru | Filament Custom Page |
| `/vendor` | Dashboard Ringkasan Operasional | Pengelola Rental | Filament Dashboard |
| `/vendor/items` | Manajemen Katalog Barang (CRUD) | Pengelola Rental | Filament Resource |
| `/vendor/bookings` | Manajemen Pesanan & Verifikasi | Pengelola Rental | Filament Resource |
| `/vendor/blacklist-entries`| Catatan Riwayat Blacklist Internal | Pengelola Rental | Filament Resource |
| `errors/404, 500, 429` | Halaman Status Error Sistem | Seluruh Pengguna | Blade Error Views |

---

## 2. Rincian Spesifikasi per Halaman

### 2.1 Halaman Katalog Publik Toko (`/b/{vendor_slug}`)

- **Tujuan Halaman:** Menampilkan profil resmi vendor dan etalase daftar barang rental yang dapat disewa penyewa yang datang melalui tautan profil/story Instagram atau media sosial vendor.
- **Pengguna:** Penyewa (kebanyakan mengakses via smartphone).
- **Komponen yang Digunakan:**
  - Header navigasi mini sticky (Logo JagaSewa + status "Rental Terverifikasi")
  - Kartu Profil Vendor (Avatar/Inisial, Nama Toko, Badge Resmi, Tombol Bagikan Toko)
  - Grid Kartu Barang Rental (Foto aspect 4:3, Badge Ukuran, Nama Barang, Harga/Hari, Keterangan Deposit, Jeda Buffer, Tombol Aksi)
  - Kontrol Paginasi ramah sentuhan (min 44px)
  - Footer informasi keamanan JagaSewa
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Satu kolom terpusat (max-width 480px). Kartu barang tampil vertikal memanjang. Tombol aksi kartu memenuhi lebar kartu untuk mempermudah sentuhan jempol.
  - **Tablet (640px – 1023px):** Dua kolom grid barang. Profil vendor membentang horizontal di bagian atas dengan statistik jumlah barang aktif.
  - **Desktop (≥ 1024px):** Tiga kolom grid barang (max-width 1200px terpusat), padding lapang, efek hover mengangkat kartu dengan bayangan lembut.
- **Penanganan State:**
  - *Empty State:* Menampilkan ilustrasi kotak barang minimalis dengan teks: *"Vendor ini sedang menyiapkan katalog rentalnya. Cek kembali nanti."*
  - *Error State (Vendor Tidak Ditemukan):* 404 khusus dengan pesan ramah *"Toko rental tidak ditemukan atau sedang dinonaktifkan"*.
  - *Loading State:* Skeleton shimmer kartu barang saat proses paginasi.

---

### 2.2 Halaman Detail Barang, Kalender & Form Booking (`/b/{vendor_slug}/{item_slug}`)

- **Tujuan Halaman:** Halaman inti konversi penyewa. Menampilkan spesifikasi barang rental, memeriksa ketersediaan tanggal via Smart Buffer Calendar, dan mengajukan pesanan sewa secara langsung.
- **Pengguna:** Penyewa (anonim, tanpa login).
- **Komponen yang Digunakan:**
  - Header Navigasi + Breadcrumb ringkas
  - Galeri Foto Interaktif (Foto utama 4:3 + Thumbnail baris bawah)
  - Blok Finansial Utama (Tarif Sewa Harian, Uang Jaminan/Deposit, Denda Telat/Hari)
  - Blok Spesifikasi Rental (Ukuran, Kelengkapan Set Included, Petunjuk Perawatan)
  - Komponen Livewire `item-availability-calendar` (Grid tanggal, multi-modal status booked/buffer/available, legenda warna+ikon)
  - Komponen Livewire `booking-form` (Stepper 3 langkah: Tanggal & Biaya -> Data Kontak -> KYC Foto KTP & Selfie + Edukasi Privasi UU PDP + Persetujuan Eksplisit)
  - Bottom Sticky Bar (Harga harian + tombol "Pesan Sekarang" yang otomatis scroll ke formulir)
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Kolom tunggal bertingkat. Foto di atas, diikuti harga, spesifikasi, kalender, lalu form booking. Tombol "Pesan Sekarang" melayang di bagian bawah (sticky footer) dengan backdrop blur.
  - **Tablet (640px – 1023px):** Tata letak 2 kolom selektif: Galeri foto dan rincian barang di kolom kiri, Kalender ketersediaan dan Form Booking di kolom kanan.
  - **Desktop (≥ 1024px):** Layout 2 panel seimbang: Panel kiri statis (Galeri foto besar + Deskripsi & Instruksi Perawatan), Panel kanan sticky (Ringkasan tarif, Kalender interaktif, dan Form Booking multi-langkah).
- **Penanganan State:**
  - *State Pemilihan Kalender:* Tanggal mulai dan selesai disorot dengan warna aksen hijau zamrud (`--color-primary-600`), estimasi durasi dan total biaya terhitung seketika.
  - *State Bentrok Buffer:* Muncul peringatan inline *"Tanggal tersebut termasuk dalam jeda persiapan/laundry vendor. Silakan pilih tanggal lain."*
  - *State Validasi Form:* Kotak input bermasalah diberi border merah dan fokus visual langsung ke input yang perlu diperbaiki.
  - *State Mengunggah KTP:* Indikator proses upload (progress bar / spinner) dengan preview instan setelah sukses.
  - *State Form Submitting:* Tombol kirim menampilkan status "Memproses Booking..." dengan spinner berputar dan tombol disabled mencegah klik ganda.

---

### 2.3 Halaman Konfirmasi Sukses Booking (`/b/success/{ulid}`)

- **Tujuan Halaman:** Memberikan kepastian bahwa pesanan sewa telah tersimpan, nomor referensi telah diterbitkan, dan mengarahkan penyewa untuk membuka obrolan WhatsApp resmi dengan vendor.
- **Pengguna:** Penyewa yang baru saja selesai mengisi formulir.
- **Komponen yang Digunakan:**
  - Kartu Hero Sukses dengan animasi perayaan halus dan badge status "⏳ Menunggu Persetujuan Vendor"
  - Kotak Nomor Referensi Booking (Teks besar tebal dengan tombol satu klik "Salin Kode")
  - Tombol Utama WhatsApp Official (`#25D366`) dengan deep-link `wa.me` berisi pesan konfirmasi terformat rapi
  - Rincian Pesanan (Barang, Vendor, Tanggal Mulai & Selesai, Total Biaya, Deposit)
  - Kartu Edukasi "Langkah Selanjutnya" (Timeline 24 jam verifikasi vendor)
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Lebar penuh terpusat (max-width 480px), tombol WhatsApp besar dan mudah dijangkau di bagian atas tepat setelah nomor referensi.
  - **Tablet & Desktop (≥ 640px):** Kontainer kartu elegan di tengah layar (max-width 640px) dengan bayangan lembut dan tombol WhatsApp menonjol.
- **Penanganan State:**
  - *Feedback Salin Kode:* Teks berubah menjadi *"✓ Tersalin ke clipboard!"* selama 2 detik saat tombol salin ditekan.
  - *Fallback Kontak:* Jika URL WhatsApp gagal digenerate karena nomor vendor tidak valid, sistem menampilkan nomor kontak manual yang dapat disalin.

---

### 2.4 Halaman Pendaftaran & Masuk Vendor (`/vendor/register`, `/vendor/login`)

- **Tujuan Halaman:** Onboarding pemilik bisnis rental baru dan otentikasi login vendor terdaftar.
- **Pengguna:** Pemilik atau pengelola toko rental.
- **Komponen yang Digunakan:**
  - Header Logo JagaSewa + Slogan "SaaS Manajemen Rental Tepercaya"
  - Form Masuk / Daftar (Nama Toko, Nama Pemilik, No. WhatsApp, Email, Kata Sandi)
  - Notifikasi Validasi & Error Alert
  - Tautan Bantuan / Lupa Sandi
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Form satu kolom penuh, spasi input nyaman, keyboard type sesuai (mis. `type="tel"` untuk WA).
  - **Desktop (≥ 1024px):** Layout split-screen SaaS: Sisi kiri berupa testimoni/keunggulan fitur JagaSewa (Smart Buffer, Anti-Double Booking, Watermark KYC), sisi kanan form pendaftaran bersih berpusat.

---

### 2.5 Halaman Dashboard Ringkasan Vendor (`/vendor`)

- **Tujuan Halaman:** Pusat kendali harian vendor saat di toko maupun di lapangan via ponsel.
- **Pengguna:** Pengelola toko rental.
- **Komponen yang Digunakan:**
  - Widget Statistik Cepat (Pesanan Menunggu Persetujuan, Pesanan Aktif Berjalan, Barang Perlu Diambil Hari Ini, Total Barang Aktif)
  - Daftar Aksi Cepat (Pesanan terbaru yang butuh persetujuan segera dalam batas 24 jam)
  - Tombol Salin Tautan Katalog Toko publik untuk dipasang di Bio Instagram
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Widget statistik vertikal atau grid 2×2 padat. Tombol aksi pesanan berupa kartu aksi geser atau tombol jelas.
  - **Desktop (≥ 1024px):** Grid statistik 4 kolom di atas, diikuti tabel ringkasan pesanan dan kalender aktivitas.

---

### 2.6 Halaman Manajemen Pesanan Vendor (`/vendor/bookings`)

- **Tujuan Halaman:** Mengelola siklus hidup pesanan rental: memeriksa data & foto KTP penyewa, menyetujui/menolak, mengaktifkan status barang disewa, mencatat pengembalian, dan menghitung denda/deposit.
- **Pengguna:** Pengelola toko rental.
- **Komponen yang Digunakan:**
  - Tabel Pesanan Responsif (Kolom: Kode Booking, Barang, Penyewa, Tanggal Sewa, Total & Deposit, Status Badge, Aksi)
  - Filter Status Tab (Semua, Menunggu, Disetujui, Aktif, Selesai)
  - Halaman Rincian Pesanan (Infolist):
    - Banner Peringatan Blacklist (jika identitas penyewa cocok dengan catatan blacklist vendor)
    - Pratinjau Dokumen KTP & Selfie (Watermark otomatis + tombol buka URL aman berbatas waktu 60 menit)
    - Modal Aksi Konfirmasi (Setujui, Tolak dengan Alasan, Tandai Sedang Disewa)
    - Form Penyelesaian Sewa (Input hari keterlambatan, kalkulasi otomatis denda harian, pemotongan deposit, catatan kerusakan)
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Tabel otomatis berubah menjadi **Daftar Kartu Pesanan**. Tiap kartu menampilkan kode booking tebal, status badge di kanan, foto kecil barang, nama penyewa, dan tombol aksi "Tinjau Pesanan".
  - **Tablet (640px – 1023px):** Tabel kompak dengan kolom kritis; kolom tanggal dibuat dua baris.
  - **Desktop (≥ 1024px):** Tabel data lengkap dengan fitur sorting, pencarian teks instan, dan aksi langsung per baris.
- **Penanganan State:**
  - *Blacklist Alert Terdeteksi:* Banner Amber menyala di atas rincian pesanan dengan catatan faktual internal vendor.
  - *Dokumen Telah Terhapus:* Menampilkan label abu-abu *"Dokumen identitas telah dihapus permanen sesuai jadwal retensi 30 hari"*.

---

### 2.7 Halaman Manajemen Katalog Barang Vendor (`/vendor/items`)

- **Tujuan Halaman:** Menambah, mengubah, mengatur jeda Smart Buffer, dan mengunggah foto barang rental.
- **Pengguna:** Pengelola toko rental.
- **Komponen yang Digunakan:**
  - Tabel Daftar Barang (Thumbnail foto, Nama barang, Ukuran, Harga sewa, Deposit, Ringkasan buffer, Switch aktif/nonaktif)
  - Formulir Barang Multi-Bagian (Informasi Utama, Spesifikasi & Ukuran, Finansial Rupiah, Pengaturan Buffer Sebelum & Sesudah Sewa, Upload Foto S3 Privat)
- **Tata Letak per Breakpoint:**
  - **Mobile (< 640px):** Tampilan kartu barang ringkas dengan toggle switch aktif/nonaktif mudah ditekan satu jempol.
  - **Desktop (≥ 1024px):** Tabel administrasi rapi dengan thumbnail foto barang yang dapat diklik untuk pratinjau.

---

### 2.8 Halaman Catatan Blacklist Vendor (`/vendor/blacklist-entries`)

- **Tujuan Halaman:** Mengelola catatan riwayat penyewa bermasalah internal vendor (WhatsApp, NIK) untuk perlindungan aset toko.
- **Pengguna:** Pemilik rental.
- **Komponen yang Digunakan:**
  - Tabel Catatan (Tipe Identitas, Alasan Faktual, Blind Index Hash tersimpan, Tanggal Penambahan, Aksi Hapus)
  - Form Tambah Entri Blacklist (Pilihan Tipe: WhatsApp / NIK, Input Identitas Asli, Input Alasan Kejadian)
  - Modal Konfirmasi Hapus Data
- **Tata Letak per Breakpoint:**
  - Menyesuaikan pola Filament standar dengan penekanan pada penyembunyian data mentah (hashing blind index) untuk menjaga kepatuhan privasi.

---

### 2.9 Halaman Error Sistem (404, 500, 429)

- **404 - Halaman / Barang Tidak Ditemukan:**
  - Pesan: *"Halaman atau barang rental yang Anda cari tidak tersedia atau tautan sudah kedaluwarsa."*
  - Aksi: Tombol "Kembali ke Beranda" atau "Lihat Katalog Toko".
- **429 - Batas Permintaan Terlampaui:**
  - Pesan: *"Aktivitas terlalu sering terdeteksi demi keamanan sistem. Silakan tunggu 1 menit sebelum mencoba kembali."*
  - Aksi: Countdown timer mundur otomatis.
- **500 - Kesalahan Sistem Internal:**
  - Pesan: *"Terjadi kendala teknis pada server kami. Tim teknis JagaSewa telah menerima notifikasi perbaikan."*
  - Aksi: Tombol "Muat Ulang Halaman".
