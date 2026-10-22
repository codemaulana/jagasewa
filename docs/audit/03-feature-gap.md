# Analisis Kesenjangan Fitur (03-feature-gap.md) — JagaSewa

> **Tanggal Audit:** 22 Oktober 2026  
> **Tolak Ukur Perbandingan:** Solusi SaaS Manajemen Rental Terkemuka (*Booqable, Twice/Rentle, EZRentOut, Rentman*) dan dinamika operasional nyata pemilik rental barang di Indonesia (kostum cosplay, kamera mirrorless, iPhone konser, kebaya adat, alat outdoor).  
> **Fokus Evaluasi:** Seluruh alur nyata pemilik rental: etalase katalog, penjadwalan kalender, pengajuan booking, serah-terima unit, pengembalian, kalkulasi denda, pengelolaan uang jaminan (deposit), mitigasi risiko (blacklist KYC), dan pelaporan pembukuan.

---

## 1. Ringkasan Evaluasi Kematangan Produk

| Domain Siklus Sewa | Tingkat Kelengkapan Saat Ini | Hambatan Terbesar bagi Pemilik Rental |
|---|---|---|
| **1. Katalog & Manajemen Barang** | 🟡 **Sebagian** (Ada form item, kelengkapan, foto) | Tidak mendukung varian/multi-unit (misal punya 3 kamera seri sama) atau nomor seri unik (barcode/SN). |
| **2. Kalender & Penjadwalan** | 🟢 **Matang** (Smart buffer before & after, visual kalender) | Tidak ada tampilan linimasa horizontal (*Gantt chart view*) dan tidak ada pengaturan hari libur toko (*blackout dates*). |
| **3. Booking & Verifikasi KYC** | 🟢 **Matang** (Form publik, signed URL, watermark dinamis) | Penyewa tidak memiliki portal lacak status booking mandiri setelah meninggalkan layar konfirmasi. |
| **4. Serah-Terima Barang (*Handover*)** | 🔴 **Tidak Ada** (Hanya tombol status 'Aktifkan') | Tidak ada checklist fisik serah-terima dan tidak ada fitur input nomor resi kurir (Paxel/JNE/GoSend). |
| **5. Pengembalian & Inspeksi** | 🟡 **Sebagian** (Status returned + form settlement) | Tidak ada form unggah foto bukti kerusakan fisik barang saat unit diterima kembali dari penyewa. |
| **6. Uang Jaminan & Denda** | 🟢 **Matang** (Kalkulasi denda otomatis, potong deposit) | Pengembalian deposit (refund) masih dilakukan manual via m-banking tanpa pencatatan bukti transfer di sistem. |
| **7. KYC Fraud & Blacklist** | 🟢 **Matang untuk MVP** (Private hash SHA-256) | Blacklist masih bersifat privat per vendor (belum ada shared network Fase 2 atau impor kontak penipu). |
| **8. Pembukuan & Laporan** | 🔴 **Minimal** (Hanya dump CSV sederhana) | Tombol download CSV tidak muncul di navigasi; tidak ada filter rentang tanggal, dan tidak ada grafik tren pendapatan. |

---

## 2. Matriks Perbandingan Kesenjangan Fitur (Feature Gap Matrix)

| Domain | Fitur | Status | Tier | Usaha | Prioritas | Alasan & Kebutuhan Riil Rental |
|---|---|---|---|---|---|---|
| **Katalog** | Multi-Unit / Multi-Stok per Barang | **Tidak Ada** | Pro | **L** | **P1** | Rental kamera sering memiliki 2-3 bodi kamera yang sama. Saat ini vendor terpaksa membuat 3 item berbeda di katalog. |
| **Katalog** | Paket Bundling (Set Rental) | **Tidak Ada** | Pro | **M** | **P2** | Rental kostum menyewakan paket "Kostum + Wig + Sepatu + Senjata" dengan diskon paket dibanding sewa terpisah. |
| **Katalog** | Custom Rental Rates (Paket 3 Hari / Weekend) | **Tidak Ada** | Free/Pro | **M** | **P1** | Kebiasaan rental cosplay/kamera menggunakan tarif paket Jumat–Senin (3 hari) lebih murah daripada hitungan harian flat. |
| **Katalog** | Tag Kategori & Filter Etalase | **Sebagian** | Free/Pro | **S** | **P0** | Pada `/b/{vendor_slug}`, saat barang banyak, penyewa tidak bisa memfilter berdasarkan kategori (Kamera, Lensa, Kostum). |
| **Kalender** | Smart Buffer Calendar (Laundry/Prep) | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* Mencegah double-booking dengan jeda otomatis sebelum dan sesudah sewa. |
| **Kalender** | Linimasa Horizontal (Gantt Timeline View) | **Tidak Ada** | Pro | **L** | **P1** | Vendor dengan >10 barang membutuhkan tampilan horizontal untuk memantau semua unit dalam satu layar bentang. |
| **Kalender** | Toko Tutup / Blackout Dates | **Tidak Ada** | Free/Pro | **S** | **P1** | Vendor ingin menutup jadwal rental saat libur Idul Fitri, cuti bersama, atau maintenance toko tanpa menonaktifkan barang. |
| **Booking** | Form Pemesanan Publik Tanpa Login | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* Penyewa memesan cepat tanpa hambatan registrasi. |
| **Booking** | Watermark Dinamis & Auto-Strip EXIF KTP | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* Dokumen identitas dibubuhi watermark nama vendor dan tanggal. |
| **Booking** | Portal Lacak Status Pesanan Penyewa | **Tidak Ada** | Free/Pro | **M** | **P0** | Setelah keluar dari halaman sukses `/b/success/{ulid}`, penyewa tidak punya cara mengecek apakah booking-nya disetujui tanpa bertanya via WA. |
| **Booking** | Batas Usia Minimal / Syarat Khusus Sewa | **Tidak Ada** | Free/Pro | **S** | **P2** | Rental kamera mensyaratkan penyewa minimal berusia 18 tahun atau melampirkan akun Instagram aktif > 1 tahun. |
| **Serah-Terima** | Checklist Fisik Kondisi Serah-Terima | **Tidak Ada** | Free/Pro | **M** | **P1** | Menghindari sengketa kerusakan; vendor mencentang kondisi awal (lensa bebas jamur, resleting kostum utuh) sebelum barang keluar. |
| **Serah-Terima** | Pelacakan Pengiriman / Input Resi Kurir | **Tidak Ada** | Free/Pro | **S** | **P1** | Banyak rental kamera/cosplay menggunakan Paxel atau GoSend; butuh kolom simpan no. resi pengiriman dan tautan lacak. |
| **Serah-Terima** | Berita Acara Serah Terima (BAST) Digital | **Tidak Ada** | Pro | **M** | **P2** | Format PDF ringkas yang memuat foto barang dan tanda tangan penerimaan saat serah terima di toko fisik. |
| **Pengembalian** | Unggah Foto Bukti Kerusakan Barang | **Tidak Ada** | Free/Pro | **S** | **P0** | Saat vendor mengisi form denda kerusakan (`BookingSettlementForm`), vendor tidak bisa melampirkan foto kain robek atau lensa pecah sebagai bukti ke penyewa. |
| **Pengembalian** | Pengembalian Sebagian (Partial Return) | **Tidak Ada** | Pro | **M** | **P2** | Penyewa mengembalikan kostum tepat waktu tetapi menahan wig/senjata untuk event tambahan. |
| **Denda & Deposit** | Kalkulasi Denda Keterlambatan Otomatis | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* `FineCalculatorService` menghitung telat 24 jam secara presisi dalam integer rupiah. |
| **Denda & Deposit** | Form Settlement & Pemotongan Deposit | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* Mengkalkulasi uang kembali atau tagihan kurang bayar (*shortfall*). |
| **Denda & Deposit** | Pencatatan Bukti Transfer Pengembalian Deposit | **Tidak Ada** | Free/Pro | **S** | **P1** | Vendor mengembalikan deposit via transfer bank, namun sistem tidak memiliki kolom upload/simpan bukti transfer refund. |
| **Blacklist KYC** | Deteksi Otomatis Blacklist (NIK/WA/IG) | **Ada** | Free/Pro | — | — | *Selesai & Berfungsi.* Mencegah penyewa bermasalah memesan dengan mencocokkan hash HMAC-SHA256. |
| **Blacklist KYC** | Impor Daftar Penipu Komunitas (CSV) | **Tidak Ada** | Pro | **S** | **P1** | Pemilik rental memiliki database Excel berisi ratusan nomor penipu dari grup WhatsApp komunitas; saat ini harus input manual satu per satu. |
| **Blacklist KYC** | Shared Blacklist Antar-Vendor (Fase 2) | **Direncanakan**| Pro | **L** | **P2** | Sesuai aturan arsitektur, fitur ini sengaja ditunda ke Fase 2 untuk menghindari risiko hukum UU PDP. |
| **Laporan** | Ekspor Transaksi Sewa ke CSV | **Ada** | Pro | **S** | **P0** | *Fitur ada di backend tetapi tombol hilang di UI navigasi panel vendor.* |
| **Laporan** | Filter Rentang Tanggal Ekspor Laporan | **Tidak Ada** | Pro | **S** | **P0** | Saat ini ekspor CSV melakukan dump seluruh data sewa; vendor membutuhkan filter rekap per bulan (misal: 1–31 Oktober 2026). |
| **Laporan** | Grafik Analitik Omzet & Barang Terlaris | **Tidak Ada** | Pro | **M** | **P1** | Vendor ingin melihat tren pendapatan bulanan dan barang mana yang paling sering disewa secara visual di dashboard. |

---

## 3. Analisis Mendalam Kesenjangan Kritis Alur Nyata Rental

### 3.1. Kesenjangan Serah-Terima (*Handover Gap*)
- **Kondisi Nyata Pemilik Rental:**  
  Titik paling rawan dalam bisnis rental bukan saat pemesanan, melainkan saat barang diserahkan ke penyewa (terutama via kurir ekspedisi Paxel/GoSend). Jika barang tiba dalam kondisi rusak atau kelengkapan kurang (misal wig cap tertinggal), penyewa akan menyalahkan vendor.
- **Kondisi Aplikasi Saat Ini:**  
  Di `BookingInfolist` / detail pesanan, vendor hanya memiliki tombol status "Mulai Sewa (Aktifkan)". Tidak ada tempat mencatat nomor resi ekspedisi atau catatan kelengkapan saat pengiriman.
- **Rekomendasi Solusi:**  
  Tambahkan modal "Serah Terima / Pengiriman Barang" yang memuat:
  1. Metode pengambilan (Ambil Sendiri di Toko / Ekspedisi / Kurir Instan).
  2. Nomor resi pengiriman atau tautan live-tracking GoSend/Paxel.
  3. Konfirmasi checklist kelengkapan (*included items*) sebelum diserahkan.

---

### 3.2. Kesenjangan Bukti Kerusakan saat Settlement Pengembalian
- **Kondisi Nyata Pemilik Rental:**  
  Saat penyewa mengembalikan kostum yang terkena noda cat face-painting permanen atau kamera dengan bodi lecet, vendor memotong uang jaminan (deposit). Penyewa kerap protes dan meminta bukti jika nominal deposit dipotong.
- **Kondisi Aplikasi Saat Ini:**  
  Komponen `BookingSettlementForm` (`app/Livewire/Vendor/BookingSettlementForm.php`) memungkinkan input denda kerusakan dalam rupiah dan deskripsi kerusakan, tetapi **tidak menyediakan input unggah foto bukti kerusakan**.
- **Rekomendasi Solusi:**  
  Tambahkan field upload foto bukti kerusakan di `BookingSettlementForm` yang otomatis dimasukkan ke dalam pesan notifikasi WhatsApp settlement ke penyewa.

---

### 3.3. Kesenjangan Portal Lacak Mandiri Penyewa (*Renter Order Portal*)
- **Kondisi Nyata Pemilik Rental:**  
  Penyewa yang telah mengisi formulir sering bertanya berulang kali di WhatsApp: *"Kak, pesanan saya nomor JS-2610-ABCD sudah disetujui belum ya?"*. Hal ini membebani admin vendor yang harus membalas chat satu per satu.
- **Kondisi Aplikasi Saat Ini:**  
  Tautan publik `/b/success/{ulid}` hanya tampil sekali setelah form disubmit. Jika tab tertutup, penyewa tidak memiliki portal sederhana untuk memeriksa status pesanan mereka secara mandiri.
- **Rekomendasi Solusi:**  
  Buat halaman publik `/b/track/{reference_code}` atau pertahankan akses permanen ke `/b/success/{ulid}` di mana penyewa dapat melihat:
  1. Status terkini: Menunggu Verifikasi / Disetujui / Sedang Disewa / Selesai.
  2. Informasi rekening pembayaran vendor (hanya tampil jika status `approved`).
  3. Nomor resi pengiriman (jika barang sudah dikirim).

---

### 3.4. Kesenjangan Kategori & Multi-Unit Stok
- **Kondisi Nyata Pemilik Rental:**  
  Vendor rental kamera di Yogyakarta memiliki 3 unit kamera Sony A7IV dan 5 unit lensa Sony 24-70mm GM. Saat ini vendor harus membuat 3 produk berbeda: "Sony A7IV Unit 1", "Sony A7IV Unit 2", "Sony A7IV Unit 3". Hal ini membuat katalog toko terlihat berantakan dan menghabiskan batas kuota 5 barang di paket Free.
- **Kondisi Aplikasi Saat Ini:**  
  Tabel `items` memetakan 1 baris = 1 slot ketersediaan tunggal.
- **Rekomendasi Solusi:**  
  Di masa depan, tambahkan kolom `quantity` atau tabel relasi `item_units` untuk melacak nomor seri barang secara individual di bawah satu entitas katalog.

---

## 4. Prioritas Roadmap Penutupan Kesenjangan (Actionable Roadmap)

### Fase A — Quick Wins (Prioritas P0, Usaha Rendah/Sedang)
1. **Pasang Tombol Ekspor Laporan CSV di Sidebar Vendor:**  
   Menambahkan `NavigationItem` di `VendorPanelProvider.php` agar fitur Pro yang sudah dibangun dapat langsung dipakai vendor.
2. **Unggah Foto Bukti Kerusakan di Settlement:**  
   Menambahkan kolom unggah foto pada `BookingSettlementForm` agar vendor memiliki bukti kuat saat memotong deposit penyewa.
3. **Filter Kategori Sederhana di Katalog Publik:**  
   Menambahkan filter dropdown atau chip kategori di `/b/{vendor_slug}` agar penyewa mudah mencari barang.

### Fase B — Peningkatan Alur Operasional (Prioritas P1, Usaha Sedang)
1. **Pelacakan Resi & Serah-Terima Barang:**  
   Menyediakan field nomor resi ekspedisi saat aktivasi booking.
2. **Pencatatan Bukti Transfer Refund Deposit:**  
   Menambahkan kolom unggahan struk transfer saat pengembalian deposit ke penyewa.
3. **Halaman Lacak Status Booking Mandiri Penyewa:**  
   Memungkinkan penyewa mengecek status persetujuan pesanan via tautan kode referensi.
4. **Filter Rentang Tanggal Ekspor Laporan CSV:**  
   Memberikan form filter tanggal awal dan akhir sebelum mengunduh rekap omzet sewa.

### Fase C — Fitur SaaS Skala Lanjut (Prioritas P2, Usaha Besar)
1. **Multi-Unit Inventory & Serial Number Tracking:**  
   Mendukung stok > 1 untuk satu jenis barang.
2. **Toko Tutup / Blackout Dates:**  
   Pengaturan tanggal libur toko pada kalender ketersediaan.
3. **Impor CSV Blacklist Komunitas:**  
   Memudahkan vendor mengunggah ratusan nomor penipu sekaligus dari file Excel komunitas.
