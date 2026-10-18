# phase2-shared-blacklist.md — JagaSewa

> ⛔ Fitur ini **TIDAK BOLEH** diaktifkan sampai **semua** prasyarat di bawah ini terpenuhi dan terdokumentasi.
> Status saat ini: **Fase 2 — Belum dimulai.**

---

## 1. Status Saat Ini (MVP)

| Aspek | Kondisi MVP |
|-------|------------|
| Scope blacklist | `private` per vendor — tidak ada berbagi data |
| Kolom DB | Sudah disiapkan: `scope`, `consent_basis`, `evidence`, `expires_at` (semua nullable) |
| Hash HMAC | Menggunakan `BLACKLIST_SECRET` per-server; hash tidak kompatibel antar-vendor |
| Implementasi Fase 2 | **Diblokir** sampai prasyarat hukum dan teknis terpenuhi |

---

## 2. Prasyarat Sebelum Fitur Boleh Aktif

### Prasyarat Hukum 🔍
- [ ] Tinjauan hukum selesai: dasar pemrosesan data identitas penyewa yang dibagikan antar-vendor sesuai UU PDP 🔍
- [ ] Privacy Impact Assessment (PIA) selesai dan terdokumentasi 🔍
- [ ] Kebijakan Privasi diperbarui dan secara eksplisit mencakup blacklist bersama 🔍
- [ ] Mekanisme persetujuan vendor (pemberi laporan dan penerima peringatan) terdokumentasi dan disetujui hukum 🔍
- [ ] Kewajiban notifikasi ke subjek data (penyewa) yang masuk blacklist bersama: dikonfirmasi dengan ahli hukum 🔍
- [ ] DPO (jika wajib) menyetujui fitur ini sebelum go-live 🔍

### Prasyarat Teknis
- [ ] Mekanisme keberatan dan koreksi tersedia untuk penyewa yang merasa salah masuk blacklist
- [ ] Masa kedaluwarsa entri (`expires_at`) diimplementasikan — tidak boleh `NULL` untuk `scope='shared'`
- [ ] Moderasi: proses review entri sebelum berlaku ke seluruh vendor (atau ambang jumlah konfirmasi)
- [ ] Fitur dikontrol via feature flag: `FEATURE_SHARED_BLACKLIST=false` di `.env` (default off)
- [ ] Masalah teknis hash HMAC antar-vendor diselesaikan (lihat seksi 5)
- [ ] Tes isolasi tetap lulus: vendor A tidak bisa baca raw blacklist vendor B

### Prasyarat Bisnis
- [ ] Minimum ambang vendor aktif terpenuhi: [ASUMSI: ≥ 10 vendor aktif dengan masing-masing ≥ 5 booking]
- [ ] Metrik keberhasilan pilot (seksi 10) terdefinisi dan disetujui

---

## 3. Dasar Pemrosesan & Persetujuan 🔍

> Semua baris di tabel ini adalah **perkiraan** yang wajib dikonfirmasi ahli hukum. Jangan klaim dasar pemrosesan ini sudah valid.

| Pihak | Data yang Terlibat | Kemungkinan Dasar Pemrosesan | Status |
|-------|------------------|------------------------------|--------|
| Vendor pelapor (membuat entri) | Hash identitas penyewa bermasalah | Kepentingan sah / kewajiban hukum 🔍 | Perlu konfirmasi |
| Vendor penerima (menerima peringatan) | Hash identitas (read-only) | Kepentingan sah 🔍 | Perlu konfirmasi |
| Penyewa yang di-blacklist | Tidak diberitahu secara aktif [ASUMSI] | Hak informasi UU PDP 🔍 | Perlu konfirmasi |
| JagaSewa sebagai platform | Memfasilitasi pertukaran hash | Prosesor atau pengendali bersama 🔍 | Perlu konfirmasi |

> **Catatan:** "Kepentingan sah" kemungkinan tidak cukup sebagai dasar pemrosesan untuk data sensitif seperti identitas. Konfirmasi wajib ke ahli hukum sebelum implementasi.

---

## 4. Minimisasi Data

| Prinsip | Implementasi |
|---------|-------------|
| **Hanya hash** | Hanya `identity_hash` (HMAC-SHA256) yang dibagikan, bukan nama, foto KTP, WA plaintext, atau NIK |
| **One-way** | Hash tidak bisa di-reverse ke identitas asli |
| **Tujuan terbatas** | Hash hanya digunakan untuk pencocokan peringatan; tidak digunakan untuk tujuan lain |
| **Tidak ada profiling** | Platform tidak menggabungkan data blacklist dengan data perilaku lain |

### Masalah Teknis Hash Kompatibilitas

Di MVP, setiap server menggunakan `BLACKLIST_SECRET` sendiri → hash yang dihasilkan vendor A tidak bisa dibandingkan dengan hash vendor B secara langsung.

**Opsi untuk Fase 2:**

| Opsi | Deskripsi | Risiko |
|------|-----------|--------|
| **Opsi A (Direkomendasikan)** | Re-hash di server JagaSewa dengan shared secret: server menerima identitas asli, hash ulang dengan `SHARED_BLACKLIST_SECRET` | JagaSewa menjadi pemroses hash; perlu analisis hukum tambahan 🔍 |
| **Opsi B** | Semua vendor gunakan secret yang sama (`SHARED_BLACKLIST_SECRET`) dari awal | Mengurangi isolasi; jika secret bocor, semua blacklist entry terdampak |
| **Opsi C** | Token deterministik tanpa secret (SHA-256 dari nilai ternormalisasi saja) | Lebih mudah di-brute-force jika ada data bocor |

**Rekomendasi:** Opsi A. Implikasi hukumnya perlu dikonfirmasi 🔍.

---

## 5. Aturan Bukti & Kualitas Data

| Aturan | Deskripsi |
|--------|-----------|
| **Berbasis fakta** | Entri hanya boleh dibuat berdasarkan kejadian nyata dan terdokumentasi, bukan dugaan atau kecurigaan |
| **Bukti wajib** | Kolom `evidence`: minimal nomor referensi booking (`booking_id`) dan deskripsi kejadian |
| **Verifikasi JagaSewa** | [ASUMSI: spot-check oleh admin, bukan verifikasi penuh setiap entri] — vendor tetap bertanggung jawab atas akurasi |
| **Bukan tuduhan** | Teks peringatan yang ditampilkan: "⚠️ Ada riwayat laporan dari vendor lain" — bukan "penipu" atau "kriminal" |
| **Anonimisasi sumber** | [ASUMSI] Vendor penerima peringatan tidak tahu vendor mana yang membuat laporan |
| **Hak konfirmasi** | Vendor yang menerima peringatan dapat memilih untuk mengabaikannya; tidak ada pemblokiran otomatis |

---

## 6. Mekanisme Keberatan & Koreksi

Penyewa yang merasa salah masuk blacklist bersama dapat mengajukan keberatan:

1. Penyewa kirim keberatan via email ke [ASUMSI: privasi@jagasewa.id] dengan informasi booking relevan
2. JagaSewa identifikasi vendor pelapor (tanpa mengungkap identitas vendor ke penyewa)
3. JagaSewa minta klarifikasi dan bukti dari vendor pelapor dalam 7 hari kerja [ASUMSI]
4. **Jika tidak ada bukti valid:** entri dihapus; vendor pelapor diberi peringatan; catat di `audit_logs`
5. **Jika ada bukti:** keberatan ditolak; penyewa diberi penjelasan umum (tidak mengungkap bukti pihak ketiga)
6. Semua langkah dicatat di `audit_logs` tanpa PII
7. Penyewa dapat mengajukan banding ke otoritas yang berwenang 🔍
8. Target response time: 14 hari kerja [ASUMSI — konfirmasi ke ahli hukum apakah ada kewajiban regulasi]

---

## 7. Masa Kedaluwarsa Entri

> Semua nilai di bawah ini adalah [ASUMSI] dan harus dikonfirmasi dengan prinsip *storage limitation* UU PDP 🔍.

| Kategori | Masa Berlaku [ASUMSI] |
|----------|----------------------|
| Keterlambatan ringan (< 3 hari) | 1 tahun |
| Keterlambatan berat (≥ 3 hari) | 2 tahun |
| Kerusakan ringan (< 50% nilai barang) | 2 tahun |
| Kerusakan berat (≥ 50% nilai barang) | 3 tahun |
| Penipuan / kabur dengan barang | 5 tahun |

**Setelah `expires_at` terlewati:**
- Entri otomatis dinonaktifkan (soft delete via flag `is_active = false`)
- Tidak dihapus permanen agar ada jejak audit
- Penghapusan permanen: [ASUMSI: 1 tahun setelah expired, sesuai kebijakan retensi]

---

## 8. Moderasi & Governance

| Aspek | Mekanisme [ASUMSI] |
|-------|-------------------|
| **Review entri baru** | Entri `scope='shared'` perlu: (a) minimal 2 konfirmasi dari vendor berbeda, ATAU (b) review manual admin JagaSewa |
| **Abuse detection** | Vendor yang menambah > 10 entri shared dalam 7 hari tanpa booking terkait → di-flag untuk review admin |
| **Vendor reputation** | Vendor yang entri blacklist-nya sering dikoreksi (> 3x) → kemampuan tambah entri shared dibatasi |
| **Transparansi sistem** | Laporan agregat tersedia untuk admin (jumlah entri per kategori, bukan per identitas penyewa) |
| **Audit trail** | Semua aksi moderasi dicatat di `audit_logs` |

---

## 9. Ambang Vendor Aktif (Anti-Cold-Start & Anti-Bias)

- **Minimum:** [ASUMSI: ≥ 10 vendor aktif, masing-masing memiliki ≥ 5 booking yang sudah `closed`]
- **Alasan:** Mencegah 1–2 vendor mendominasi blacklist bersama
- **Metrik yang dipantau:** jumlah vendor unik yang berkontribusi entri ÷ total entri shared; idealnya tidak ada vendor dengan kontribusi > 30%

---

## 10. Metrik Keberhasilan Fase 2

| Metrik | Target [ASUMSI] | Cara Ukur |
|--------|----------------|-----------|
| % booking yang mendapat peringatan blacklist bersama | < 5% | Query DB |
| % keberatan yang terbukti valid (entri salah) | < 10% dari total keberatan | Log moderasi |
| Response time penyelesaian keberatan | < 14 hari kerja | Log moderasi |
| Kepuasan vendor dengan fitur (survei opsional) | > 80% menyatakan berguna [ASUMSI] | Survei internal |
| Insiden kebocoran data terkait shared blacklist | 0 | Audit log |
| Entri expired yang berhasil dinonaktifkan tepat waktu | 100% | Scheduler job |

---

## 11. Implikasi pada ERD & Kode

Kolom yang sudah disiapkan di tabel `blacklist_entries` (dari `erd.md`):

| Kolom | Tipe | Default MVP | Penggunaan Fase 2 |
|-------|------|------------|------------------|
| `scope` | `enum('private','shared')` | `'private'` | `'shared'` untuk entri bersama |
| `consent_basis` | `string nullable` | `null` | Dasar hukum pemrosesan (dikonfirmasi hukum) |
| `evidence` | `text nullable` | `null` | Referensi booking + deskripsi kejadian |
| `expires_at` | `date nullable` | `null` | Wajib diisi untuk `scope='shared'` |

**Constraint yang perlu ditambahkan saat Fase 2:**
```sql
-- Pastikan entri shared selalu punya expires_at
ALTER TABLE blacklist_entries
ADD CONSTRAINT chk_shared_requires_expiry
CHECK (scope = 'private' OR expires_at IS NOT NULL);
```

---

*Dokumen ini adalah panduan perencanaan, bukan spesifikasi implementasi. Implementasi hanya dimulai setelah semua prasyarat di Seksi 2 terpenuhi dan terdokumentasi.*
