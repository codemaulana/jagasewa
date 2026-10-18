# User Stories — JagaSewa MVP

> Format: "Sebagai \<peran\>, saya ingin \<aksi\>, agar \<manfaat\>"
> Prioritas: **M** = Must, **S** = Should, **C** = Could, **W** = Won't (MVP)

---

## 🧑‍💼 Penyewa (tanpa login)

### US-01 — Melihat Kalender Ketersediaan
**Prioritas:** M
Sebagai penyewa, saya ingin melihat kalender ketersediaan barang sebelum memesan, agar saya tidak memilih tanggal yang sudah terisi atau terkunci buffer.

**Kriteria Penerimaan:**
- **Given** penyewa membuka halaman barang, **When** halaman dimuat, **Then** kalender menampilkan hari tersedia (hijau), hari terpesan (merah), dan hari buffer (abu-abu) dengan legenda jelas.
- **Given** ada booking pending yang belum disetujui, **When** penyewa melihat kalender, **Then** tanggal pending tetap ditampilkan sebagai tidak tersedia (kunci kalender aktif).

---

### US-02 — Mengisi Form Booking Publik
**Prioritas:** M
Sebagai penyewa, saya ingin mengisi form booking (nama, nomor WA, tanggal sewa) tanpa membuat akun, agar proses pemesanan cepat lewat HP.

**Kriteria Penerimaan:**
- **Given** penyewa mengisi form dengan tanggal valid & tersedia, **When** menekan Kirim, **Then** sistem membuat booking berstatus `pending` dan menampilkan nomor referensi.
- **Given** penyewa memilih tanggal yang bertabrakan (termasuk zona buffer), **When** menekan Kirim, **Then** sistem menolak dengan pesan error spesifik: "Tanggal tidak tersedia: [tanggal]".

---

### US-03 — Mengunggah KTP dan Selfie
**Prioritas:** M
Sebagai penyewa, saya ingin mengunggah foto KTP dan selfie saat booking, agar identitas saya terverifikasi dan vendor mau menyetujui pesanan saya.

**Kriteria Penerimaan:**
- **Given** penyewa mengunggah file KTP (JPG/PNG < 5 MB) dan selfie, **When** form dikirim, **Then** file tersimpan di storage privat dan URL publik tidak bisa diakses langsung.
- **Given** penyewa mengunggah file berekstensi `.exe` atau melebihi 5 MB, **When** form dikirim, **Then** sistem menolak dengan pesan "Format atau ukuran file tidak valid".
- **Given** penyewa belum mencentang persetujuan privasi, **When** mencoba mengirim form, **Then** tombol kirim dinonaktifkan dan muncul pesan "Harap setujui kebijakan privasi".

---

### US-04 — Menerima Konfirmasi via WhatsApp
**Prioritas:** M
Sebagai penyewa, saya ingin menerima tautan `wa.me` setelah booking dibuat, agar saya bisa langsung menghubungi vendor untuk konfirmasi.

**Kriteria Penerimaan:**
- **Given** booking berhasil dibuat, **When** halaman sukses ditampilkan, **Then** ada tombol "Hubungi Vendor via WhatsApp" yang membuka `wa.me/62xxx?text=...` dengan pesan pre-filled berisi nomor referensi booking.
- **Given** vendor belum mengisi nomor WA, **When** booking dibuat, **Then** tampilkan nomor telepon vendor sebagai fallback (teks biasa, bisa disalin).

---

### US-05 — Melihat Rincian Denda/Deposit Sebelum Booking
**Prioritas:** S
Sebagai penyewa, saya ingin melihat rincian deposit, denda per hari keterlambatan, dan syarat kerusakan sebelum memesan, agar tidak ada kejutan saat pengembalian.

**Kriteria Penerimaan:**
- **Given** penyewa membuka halaman barang, **When** halaman dimuat, **Then** ditampilkan: jumlah deposit, denda per hari, dan kebijakan kerusakan vendor.

---

## 🏪 Vendor (login, HP)

### US-06 — Mendaftar & Login sebagai Vendor
**Prioritas:** M
Sebagai vendor, saya ingin mendaftar dan login ke dashboard, agar saya bisa mengelola barang dan booking saya sendiri.

**Kriteria Penerimaan:**
- **Given** vendor mengisi email & password valid, **When** menekan Daftar, **Then** akun dibuat dan vendor diarahkan ke dashboard.
- **Given** vendor login dari perangkat berbeda, **When** sesi lama masih aktif, **Then** sesi lama tetap aktif [ASUMSI: tidak ada batasan sesi paralel di MVP].

---

### US-07 — Menambah & Mengelola Barang
**Prioritas:** M
Sebagai vendor, saya ingin menambah barang dengan foto, harga sewa per hari, deposit, buffer, dan denda, agar penyewa bisa memesan dengan informasi lengkap.

**Kriteria Penerimaan:**
- **Given** vendor mengisi semua field wajib (nama, harga, deposit, buffer_before, buffer_after, denda_per_hari), **When** menekan Simpan, **Then** barang aktif dan muncul di halaman publik.
- **Given** vendor memasukkan harga = 0 atau deposit negatif, **When** menekan Simpan, **Then** form ditolak dengan validasi: "Nilai harus lebih besar dari 0".

---

### US-08 — Menyetujui atau Menolak Booking
**Prioritas:** M
Sebagai vendor, saya ingin menyetujui atau menolak booking yang masuk, agar hanya penyewa terverifikasi yang mendapatkan barang saya.

**Kriteria Penerimaan:**
- **Given** ada booking `pending` baru, **When** vendor membuka dashboard, **Then** ada notifikasi badge dan tombol "Setujui" / "Tolak" beserta link foto KTP/selfie.
- **Given** vendor menekan "Setujui", **When** dikonfirmasi, **Then** status booking berubah ke `approved` dan tautan `wa.me` ke penyewa ditampilkan.
- **Given** booking pending melebihi 24 jam tanpa tindakan, **When** scheduler berjalan, **Then** status berubah ke `cancelled_timeout` dan kalender terbuka kembali.

---

### US-09 — Memproses Pengembalian & Menghitung Denda
**Prioritas:** M
Sebagai vendor, saya ingin mengisi form pengembalian (tanggal aktual kembali, catatan kerusakan) agar sistem menghitung denda secara otomatis dan transparan.

**Kriteria Penerimaan:**
- **Given** vendor mengisi tanggal kembali aktual dan nominal kerusakan, **When** menekan Hitung, **Then** sistem menampilkan: hari telat, denda telat, potongan kerusakan, total potongan, sisa deposit, dan kekurangan bayar (jika ada).
- **Given** barang dikembalikan tepat waktu tanpa kerusakan, **When** vendor submit form, **Then** sistem menampilkan denda = Rp 0 dan deposit dikembalikan penuh.

---

### US-10 — Menandai Penyewa ke Blacklist Pribadi
**Prioritas:** M
Sebagai vendor, saya ingin menambahkan penyewa bermasalah ke blacklist pribadi saya, agar saya mendapat peringatan jika orang yang sama mencoba memesan lagi.

**Kriteria Penerimaan:**
- **Given** vendor menambah entri blacklist (NIK atau nomor WA), **When** penyewa lain dengan identitas sama mengisi form booking, **Then** vendor melihat peringatan "⚠️ Penyewa ini ada di blacklist Anda" di halaman persetujuan.
- **Given** vendor menambah entri blacklist, **When** penyewa berbeda (identitas berbeda) memesan, **Then** tidak ada peringatan.
- **Given** entri blacklist disimpan, **When** vendor lain (berbeda akun) melihat dashboard, **Then** entri blacklist vendor A tidak terlihat oleh vendor B.

---

### US-11 — Melihat Kalender Barang
**Prioritas:** M
Sebagai vendor, saya ingin melihat kalender barang milik saya dengan status booking, agar saya bisa merencanakan pengiriman.

**Kriteria Penerimaan:**
- **Given** vendor membuka kalender, **When** kalender dimuat, **Then** setiap booking ditampilkan dengan warna status (pending/approved/active/returned) dan nama penyewa.
- **Given** vendor memiliki beberapa barang, **When** membuka kalender, **Then** bisa memfilter per barang.

---

### US-12 — Menerima Peringatan via WhatsApp ke Penyewa
**Prioritas:** S
Sebagai vendor, saya ingin bisa mengirim pengingat pengembalian ke penyewa lewat tautan `wa.me`, agar pengembalian tepat waktu.

**Kriteria Penerimaan:**
- **Given** vendor membuka detail booking `active`, **When** menekan "Kirim Pengingat", **Then** browser membuka `wa.me` dengan pesan pre-filled berisi nama barang, tanggal jatuh tempo, dan nominal denda jika terlambat.

---

## 🔧 Admin Platform

### US-13 — Mengelola Vendor
**Prioritas:** M
Sebagai admin, saya ingin melihat daftar vendor, menonaktifkan akun vendor, dan melihat log aktivitas, agar platform terjaga kualitasnya.

**Kriteria Penerimaan:**
- **Given** admin menonaktifkan vendor, **When** vendor mencoba login, **Then** muncul pesan "Akun Anda dinonaktifkan. Hubungi support."
- **Given** admin membuka log aktivitas, **When** memfilter per vendor, **Then** tampil audit log tanpa PII (tanpa NIK, tanpa path foto mentah).

---

### US-14 — Mengatur Konfigurasi Platform
**Prioritas:** S
Sebagai admin, saya ingin mengatur nilai default (batas pending, retensi dokumen), agar operasional platform konsisten.

**Kriteria Penerimaan:**
- **Given** admin mengubah nilai retensi dokumen (misal dari 7 ke 14 hari), **When** disimpan, **Then** booking baru menggunakan nilai 14 hari; booking lama tidak terpengaruh.

---

### US-15 — Melihat Statistik Platform
**Prioritas:** C
Sebagai admin, saya ingin melihat statistik (total booking, vendor aktif, booking bentrok), agar bisa mengukur kesehatan platform.

---

*Referensi silang: setiap story M memiliki tugas di `tasks.md` dengan ID terkait.*
