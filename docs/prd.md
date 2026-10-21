# PRD — JagaSewa MVP

> **Versi:** 0.1 | **Tanggal:** 2026-10-18 | **Status:** Draft

---

## 1. Ringkasan

JagaSewa adalah platform booking berbasis web (mobile-first) untuk pemilik rental barang yang memasarkan lewat Instagram/X. Tujuan utama: menghilangkan bentrok jadwal, memverifikasi identitas penyewa, dan membuat perhitungan denda serta deposit transparan — tanpa payment gateway dan tanpa chat internal.

---

## 2. Masalah

| # | Masalah | Dampak |
|---|---------|--------|
| P1 | Jadwal bentrok karena jeda pengiriman & laundry tidak dihitung | Reputasi vendor rusak, barang tidak siap |
| P2 | Identitas penyewa tidak terverifikasi → kabur/merusak barang | Kerugian finansial langsung |
| P3 | Denda & potongan deposit tidak transparan → sengketa | Waktu vendor habis untuk klarifikasi |

---

## 3. Persona

| Persona | Deskripsi Singkat | Perangkat |
|---------|-------------------|-----------|
| **Penyewa (Rina)** | Mahasiswi, 22 th, ingin sewa kostum cosplay. Datang dari link bio IG, tidak mau buat akun | HP, tanpa login |
| **Vendor (Pak Budi)** | Pemilik rental kamera, 35 th, kelola 10–30 item, aktif di Instagram. Butuh dashboard simpel | HP & tablet |
| **Admin Platform** | Tim internal JagaSewa, mengawasi vendor, mengelola konfigurasi platform | Desktop |

---

## 4. Tujuan & Metrik Sukses MVP

| Tujuan | Metrik | Target 3 Bulan |
|--------|--------|---------------|
| Eliminasi bentrok jadwal | % booking bentrok per vendor | < 1% |
| Verifikasi identitas | % booking dengan KTP + selfie terunggah | ≥ 90% |
| Transparansi denda | % sengketa yang diselesaikan lewat platform | ≥ 80% |
| Adopsi vendor | Vendor aktif (≥1 booking/bulan) | 20 vendor |
| Waktu aktivasi vendor | Dari daftar ke barang pertama online | < 30 menit |

---

## 5. Ruang Lingkup

### MVP (Fase 1)
- Smart Buffer Calendar (buffer sebelum & sesudah booking)
- Form booking publik (tanpa login) + unggah KTP/selfie
- Dashboard vendor (Filament): kelola barang, persetujuan booking, penyelesaian sewa
- Blacklist **pribadi** per vendor (HMAC-SHA256, tanpa berbagi antar-vendor)
- Kalkulator denda & deposit otomatis
- Notifikasi lewat tautan `wa.me` (bukan API WhatsApp berbayar)
- Auto-delete foto KTP/selfie N hari setelah booking ditutup [ASUMSI: N = 30]
- 1 item = 1 unit; satuan hari penuh inklusif; zona `Asia/Jakarta`
- Uang dalam bilangan bulat rupiah

### Fase 1.5 — Monetisasi & Billing Langganan (Micro-SaaS)
- **Model Freemium (Opsi B):**
  - **Paket Free:** Maksimal 5 barang aktif, 5 entri blacklist pribadi, 2 foto/item, watermark *"Powered by JagaSewa"* di katalog.
  - **Paket Pro (Rp 15.000/bulan, Rp 39.000/3 bln, Rp 129.000/thn):** Unlimited barang aktif, white-label (tanpa watermark JagaSewa), kustom template chat WhatsApp (info rekening & aturan toko), 6–8 foto/item, unlimited blacklist + catatan riwayat risiko penyewa, download rekap keuangan Excel/CSV.
- **Jalur Pembayaran Langganan Vendor:** Transfer manual (verifikasi admin) dan Payment Gateway otomatis (Xendit hosted checkout + webhook).

### Fase 2 (Ditunda)
- Blacklist bersama antar-vendor (ditunda karena risiko UU PDP & cold start)
- Pembayaran sewa otomatis renter-ke-vendor via gateway (saat ini transfer langsung di luar sistem)
- Chat internal
- Multi-unit per item

### Di Luar Lingkup (Selamanya)
- Aplikasi native (iOS/Android)
- Marketplace (JagaSewa bukan perantara jual-beli)
- Layanan pengiriman terintegrasi

---

## 6. Asumsi

| ID | Asumsi |
|----|--------|
| A-01 | Stack: Laravel + Filament + Livewire, MySQL/PostgreSQL |
| A-02 | Batas waktu pending booking: 24 jam; lewat batas → otomatis dibatalkan |
| A-03 | Retensi foto KTP/selfie: 7 hari setelah booking ditutup (3 hari jika batal/ditolak) |
| A-04 | Vendor mengelola pengiriman sendiri; JagaSewa tidak mengatur kurir |
| A-05 | WhatsApp tersedia dan nomor vendor valid; `wa.me` tidak dijamin terkirim |
| A-06 | Satu akun vendor = satu toko; multi-toko per akun ditunda ke Fase 2 |
| A-07 | Admin platform dikelola internal; tidak ada self-service onboarding admin |

---

## 7. Risiko

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| **Hukum UU PDP** | Foto KTP/NIK adalah data sensitif; kebocoran = sanksi | Storage privat, enkripsi, auto-delete, persetujuan eksplisit penyewa |
| **Cold start vendor** | Tanpa konten, penyewa tidak datang | Onboarding mudah (< 30 mnt), template pesan WA siap pakai |
| **Ketergantungan WhatsApp** | `wa.me` bisa diblokir atau nomor tidak aktif | Tampilkan nomor telepon sebagai fallback; [ASUMSI] tidak ada plan B lain di MVP |
| **Double booking** | Dua penyewa pesan item yang sama bersamaan | Transaksi DB + `SELECT FOR UPDATE` pada slot kalender (lihat `erd.md`) |
| **Penyalahgunaan data blacklist** | Vendor memfitnah penyewa | Blacklist pribadi (tidak dibagikan), hanya peringatan bukan pemblokiran otomatis |

---

## 8. Dependensi

- Layanan object storage (S3-compatible) untuk foto KTP/selfie
- Server email/SMTP untuk notifikasi internal [ASUMSI: Mailgun atau SMTP sendiri]
- Domain + SSL sebelum diluncurkan (wajib untuk keamanan unggahan)
- Persetujuan penyewa eksplisit (checkbox + timestamp) sebelum unggah dokumen

---

*Dokumen ini bersifat living document. Perubahan ruang lingkup harus melalui persetujuan tertulis.*
