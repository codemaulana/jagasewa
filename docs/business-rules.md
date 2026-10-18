# Business Rules — JagaSewa MVP

> Tiap aturan memiliki ID **BR-xx** yang dirujuk dari `tasks.md` dan `user-stories.md`.

---

## BR-01 — Smart Buffer Calendar

### Definisi
Setiap booking menempati rentang kalender yang diperluas:
```
rentang_efektif = (start_date - buffer_before) s.d. (end_date + buffer_after)
```
Dua booking **bentrok** jika rentang efektif keduanya beririsan (overlap inklusif).

### Rumus Formal
```
Booking A: [A_start - A_before, A_end + A_after]
Booking B: [B_start - B_before, B_end + B_after]

Bentrok ⟺ (A_start - A_before) ≤ (B_end + B_after)
          AND (B_start - B_before) ≤ (A_end + A_after)
```

### Contoh Dasar
- Sewa: 10–12 Jan, `buffer_before=2`, `buffer_after=2`
- Rentang efektif: **8–14 Jan**
- Tanggal 8–9 Jan (buffer sebelum) dan 13–14 Jan (buffer sesudah) = **terkunci**
- Booking baru dengan tanggal 13 Jan akan **ditolak**

### Tabel Kasus Uji Buffer

| ID | Deskripsi | Booking Existing | buffer_before | buffer_after | Booking Baru | Hasil |
|----|-----------|-----------------|:---:|:---:|------------|-------|
| BT-01 | Overlap langsung | 10–12 Jan | 2 | 2 | 11–13 Jan | **BENTROK** |
| BT-02 | Irisan di zona buffer before | 10–12 Jan | 2 | 2 | 7–9 Jan | **BENTROK** (9 Jan ≤ efektif 8 Jan? 9 = dalam buffer) |
| BT-03 | Irisan di zona buffer after | 10–12 Jan | 2 | 2 | 13–15 Jan | **BENTROK** (13 Jan dalam buffer after) |
| BT-04 | Back-to-back tepat | 10–12 Jan | 2 | 2 | 15–17 Jan | **AMAN** (efektif baru: 13–19; existing: 8–14 → 14 vs 13: overlap!) → **BENTROK** |
| BT-05 | Aman di luar buffer | 10–12 Jan | 2 | 2 | 17–20 Jan | **AMAN** (efektif existing: 8–14; efektif baru: 15–22 → tidak beririsan) |
| BT-06 | Rentang satu hari | 15 Jan–15 Jan | 1 | 1 | 16 Jan–16 Jan | **BENTROK** (16 dalam buffer after: 15+1=16) |
| BT-07 | Pergantian bulan | 29 Jan–2 Feb | 2 | 2 | 4 Feb–6 Feb | **AMAN** (efektif existing: 27 Jan–4 Feb; efektif baru: 2–8 Feb → 4 Feb = 4 Feb: **BENTROK**) |
| BT-08 | Pergantian tahun | 30 Des–1 Jan | 2 | 2 | 3 Jan–5 Jan | **BENTROK** (efektif existing: 28 Des–3 Jan; 3 Jan masuk batas) |
| BT-09 | Pembatalan membebaskan slot | 10–12 Jan (cancelled) | 2 | 2 | 10–12 Jan | **AMAN** (booking cancelled tidak mengunci kalender) |
| BT-10 | Buffer = 0 back-to-back | 10–12 Jan | 0 | 0 | 13–15 Jan | **AMAN** (efektif: 10–12 & 13–15; tidak beririsan) |

> **Catatan BT-04:** Dengan `buffer_before=2, buffer_after=2`, booking 15–17 Jan punya efektif 13–19. Existing 10–12 punya efektif 8–14. Titik 13 dan 14 beririsan → **BENTROK**. Untuk back-to-back aman, booking baru harus mulai ≥ `end + buffer_after + buffer_before_baru + 1`.

---

## BR-02 — Transisi Status Booking

### Tabel Transisi

| Status Asal | Aksi | Status Tujuan | Aktor | Kondisi |
|-------------|------|---------------|-------|---------|
| *(tidak ada)* | Penyewa submit form | `pending` | Sistem | Slot tersedia |
| `pending` | Vendor approve | `approved` | Vendor | Dalam 24 jam |
| `pending` | Vendor tolak | `rejected` | Vendor | Kapan saja |
| `pending` | Timeout 24 jam | `cancelled_timeout` | Scheduler | Otomatis |
| `pending` | Penyewa batalkan | `cancelled_renter` | Sistem [ASUMSI: lewat link di WA] | Sebelum approved |
| `approved` | Barang dikirim/diserahkan | `active` | Vendor | Manual |
| `active` | Barang diterima kembali | `returned` | Vendor | Manual |
| `returned` | Vendor submit settlement | `closed` | Vendor | Setelah cek fisik |
| `approved` / `active` | Vendor batalkan | `cancelled_vendor` | Vendor | Dengan catatan alasan |

### Aturan Kunci
- Status `pending`, `approved`, `active`, dan `returned` **mengunci kalender** (slot tidak tersedia).
- Status `rejected`, `cancelled_*`, dan `closed` **membebaskan kalender**.
- Transisi status dicatat di `audit_logs` dengan timestamp dan aktor.

---

## BR-03 — Rumus Denda & Deposit

### Variabel
| Variabel | Keterangan |
|----------|-----------|
| `end_date` | Tanggal sewa selesai yang disepakati |
| `return_date` | Tanggal aktual barang dikembalikan |
| `fine_per_day` | Denda per hari keterlambatan (Rp, integer) |
| `damage_deduction` | Potongan kerusakan yang diisi vendor (Rp, integer) |
| `deposit` | Deposit awal (Rp, integer) |

### Rumus
```
late_days          = max(0, return_date - end_date)
late_fine          = late_days × fine_per_day
total_deduction    = late_fine + damage_deduction
deposit_remaining  = deposit - total_deduction
shortfall          = max(0, -deposit_remaining)   ← kekurangan bayar penyewa
refund             = max(0, deposit_remaining)    ← uang kembali ke penyewa
```

### Tabel Kasus Uji Denda

| ID | Skenario | `end_date` | `return_date` | `fine_per_day` | `damage_deduction` | `deposit` | `late_days` | `total_deduction` | `deposit_remaining` | Hasil |
|----|----------|-----------|--------------|:---------:|:----------:|:-------:|:---------:|:--------------:|:------------------:|-------|
| DT-01 | Tepat waktu, tanpa kerusakan | 12 Jan | 12 Jan | Rp 50.000 | Rp 0 | Rp 200.000 | 0 | Rp 0 | **Rp 200.000** | Refund penuh |
| DT-02 | Terlambat 2 hari, tanpa kerusakan | 12 Jan | 14 Jan | Rp 50.000 | Rp 0 | Rp 200.000 | 2 | Rp 100.000 | **Rp 100.000** | Refund Rp 100.000 |
| DT-03 | Tepat waktu, ada kerusakan | 12 Jan | 12 Jan | Rp 50.000 | Rp 150.000 | Rp 200.000 | 0 | Rp 150.000 | **Rp 50.000** | Refund Rp 50.000 |
| DT-04 | Terlambat 3 hari + kerusakan → melebihi deposit | 12 Jan | 15 Jan | Rp 75.000 | Rp 100.000 | Rp 200.000 | 3 | Rp 325.000 | **−Rp 125.000** | Shortfall Rp 125.000 |
| DT-05 | Dikembalikan sebelum jatuh tempo (tidak ada pengembalian lebih awal) | 12 Jan | 10 Jan | Rp 50.000 | Rp 0 | Rp 200.000 | 0 (max 0) | Rp 0 | **Rp 200.000** | Refund penuh; tidak ada diskon |

---

## BR-04 — Blacklist Pribadi per Vendor

### Proses Normalisasi Identitas
Sebelum disimpan, setiap identitas **dinormalisasi** lalu di-hash:

| Tipe | Normalisasi | Contoh Input | Hasil Normalisasi |
|------|------------|-------------|------------------|
| Nomor WA | Ganti `0` awal → `+62`; hapus spasi/strip | `08123456789` | `+628123456789` |
| NIK | Pastikan 16 digit angka; hapus spasi | `3 2 7 1 ...` | `3271...` (16 digit) |
| Instagram | Lowercase; hapus `@` awal | `@BudiRental` | `budiRental` → `budirental` |

### Proses Hashing
```
identity_hash = HMAC-SHA256(normalized_identity, SERVER_SECRET)
```
- `SERVER_SECRET` disimpan di `.env`, tidak pernah di database atau log.
- Perbandingan dilakukan di level hash; **nilai asli tidak disimpan** di `blacklist_entries`.
- Peringatan **hanya tampil kepada vendor pemilik entri**, tidak kepada vendor lain.

### Alur Pencocokan
1. Penyewa mengisi form booking (nomor WA / NIK).
2. Sistem normalisasi + hash identitas penyewa saat runtime.
3. Hash dibandingkan dengan `blacklist_entries` milik vendor yang bersangkutan.
4. Jika cocok → flag `is_blacklisted = true` pada objek booking (tidak disimpan permanen di booking).
5. Vendor melihat peringatan di halaman persetujuan; keputusan tetap di tangan vendor.

> **[ASUMSI]** Blacklist bersama antar-vendor ditunda ke Fase 2. Kolom `scope` pada tabel `blacklist_entries` disiapkan untuk fase 2 (nilai default: `private`).

---

## BR-05 — Retensi & Auto-Delete Dokumen

- Foto KTP dan selfie disimpan di object storage **privat** (tidak bisa diakses via URL langsung).
- Akses menggunakan **signed URL** dengan TTL maksimal 60 menit [ASUMSI].
- Setelah booking mencapai status `closed` atau `cancelled_*`, sistem menjadwalkan penghapusan otomatis setelah **N hari** (default: `N = 30`, dapat diubah admin).
- Penghapusan dicatat di `audit_logs` dengan entri: `{ event: "document_deleted", booking_id, timestamp }` tanpa menyebut konten file.
- Jika booking tidak pernah ditutup (edge case), penghapusan dipicu setelah **90 hari** sejak tanggal upload [ASUMSI].

---

## BR-06 — Isolasi Multi-Vendor

- Setiap resource (item, booking, blacklist_entry, dll.) memiliki kolom `vendor_id`.
- Query wajib selalu menyertakan `WHERE vendor_id = auth()->user()->vendor_id`.
- Vendor tidak boleh mengakses, mengubah, atau menghapus data vendor lain.
- Isolasi diuji dengan test otomatis: request dengan autentikasi vendor A terhadap resource vendor B harus mengembalikan **404 (bukan 403)** untuk menghindari enumerasi data.

---

*Referensi: setiap BR memiliki kasus uji di tabel di atas. Kode implementasi mengacu pada ID BR-xx.*
