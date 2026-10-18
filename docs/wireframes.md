# Wireframes — JagaSewa MVP

> Sketsa tata letak ASCII, mobile-first (lebar ~390px / ~40 karakter).
> Bukan desain final. Tujuan: memperjelas struktur halaman untuk AI coding agent.

---

## Layar 1 — Halaman Barang Publik

```
┌─────────────────────────────────┐
│ 🏷️  JagaSewa               [≡] │  ← header minimal
├─────────────────────────────────┤
│ [← Kembali]                    │
│                                 │
│ ┌─────────────────────────────┐ │
│ │        [FOTO BARANG]        │ │  ← aspect ratio 4:3
│ │         640 × 480           │ │
│ └─────────────────────────────┘ │
│                                 │
│ **Kamera Mirrorless Sony A7C**  │
│ ⭐ Rental by Pak Budi Studio    │
│                                 │
│ Rp 150.000 / hari               │
│ Deposit: Rp 500.000             │
│ Denda telat: Rp 75.000 / hari   │
│                                 │
│ ─────── KETERSEDIAAN ─────────  │
│                                 │
│  Okt 2026     [<]          [>]  │
│  ┌──┬──┬──┬──┬──┬──┬──┐        │
│  │Sn│Sl│Rb│Km│Jm│Sb│Mg│        │
│  ├──┼──┼──┼──┼──┼──┼──┤        │
│  │  │  │  │01│02│03│04│        │
│  │05│06│07│08│09│10│11│        │  ← 08-09 abu (buffer)
│  │12│13│14│15│16│17│18│        │  ← 10-12 merah (booked)
│  │..│  │  │  │  │  │  │        │  ← 13-14 abu (buffer)
│  └──┴──┴──┴──┴──┴──┴──┘        │
│                                 │
│  ■ Tersedia  ■ Terpesan  ■ Buffer│
│                                 │
│ ─────── DESKRIPSI ────────────  │
│ Lorem ipsum deskripsi barang... │
│                                 │
│ ┌─────────────────────────────┐ │
│ │   [  PESAN SEKARANG  ]      │ │  ← CTA utama, sticky bottom
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

---

## Layar 2 — Form Booking + Unggah Dokumen

```
┌─────────────────────────────────┐
│ ← Form Booking                  │
│ Kamera Mirrorless Sony A7C      │
├─────────────────────────────────┤
│                                 │
│ INFORMASI PENYEWA               │
│ ┌─────────────────────────────┐ │
│ │ Nama Lengkap                │ │
│ │ ________________________    │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ Nomor WhatsApp              │ │
│ │ +62 ____________________    │ │
│ └─────────────────────────────┘ │
│ ┌─────────────────────────────┐ │
│ │ Alamat Pengiriman           │ │
│ │ ________________________    │ │
│ └─────────────────────────────┘ │
│                                 │
│ TANGGAL SEWA                    │
│ ┌──────────────┐ ┌────────────┐ │
│ │ Mulai        │ │ Selesai    │ │
│ │ [dd/mm/yyyy] │ │[dd/mm/yyyy]│ │
│ └──────────────┘ └────────────┘ │
│ Durasi: 3 hari | Total: Rp 450K │
│ Deposit: Rp 500.000             │
│                                 │
│ CATATAN TAMBAHAN                │
│ ┌─────────────────────────────┐ │
│ │ ________________________    │ │
│ │ ________________________    │ │
│ └─────────────────────────────┘ │
│                                 │
│ VERIFIKASI IDENTITAS            │
│                                 │
│ Foto KTP *                      │
│ ┌─────────────────────────────┐ │
│ │  [📷 Ambil Foto / Unggah]  │ │
│ │  JPG/PNG, maks 5 MB         │ │
│ └─────────────────────────────┘ │
│                                 │
│ Selfie dengan KTP *             │
│ ┌─────────────────────────────┐ │
│ │  [📷 Ambil Foto / Unggah]  │ │
│ └─────────────────────────────┘ │
│                                 │
│ ☐ Saya setuju data identitas    │
│   saya digunakan untuk          │
│   verifikasi dan akan dihapus   │
│   otomatis dalam 30 hari.       │
│   [Baca Kebijakan Privasi]      │
│                                 │
│ ┌─────────────────────────────┐ │
│ │    [ KIRIM PERMINTAAN ]     │ │  ← disabled jika checkbox kosong
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

---

## Layar 3 — Daftar Booking Vendor (Dashboard)

```
┌─────────────────────────────────┐
│ 🏷️ JagaSewa Vendor     [logout] │
│ Pak Budi Studio                 │
├─────────────────────────────────┤
│ [Booking] [Barang] [Kalender]   │  ← tab navigasi
├─────────────────────────────────┤
│                                 │
│ PERLU TINDAKAN  🔴 2            │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ JS-20261015-0042  🟡 PENDING│ │
│ │ Sony A7C                    │ │
│ │ 20–25 Okt 2026              │ │
│ │ Rina Kusuma · +6281234xxxxx │ │
│ │ ⚠️ Ada di blacklist Anda    │ │  ← hanya tampil jika blacklisted
│ │ [Lihat Detail] [Tolak]      │ │
│ └─────────────────────────────┘ │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ JS-20261016-0043  🟡 PENDING│ │
│ │ Kebaya Batik Merah          │ │
│ │ 22–24 Okt 2026              │ │
│ │ Siti Rahayu · +6289876xxxxx │ │
│ │ [Lihat Detail] [Tolak]      │ │
│ └─────────────────────────────┘ │
│                                 │
│ AKTIF  🟢 1                     │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ JS-20261010-0038  🟢 ACTIVE │ │
│ │ iPhone 15 Pro               │ │
│ │ 15–20 Okt 2026              │ │
│ │ Budi Santoso                │ │
│ │ [Proses Pengembalian]       │ │
│ └─────────────────────────────┘ │
│                                 │
│ ── Filter: [Semua ▼] [Cari...] ─│
│                                 │
│ [Muat Lebih Banyak]             │
└─────────────────────────────────┘
```

---

## Layar 4 — Kalender Barang (Vendor)

```
┌─────────────────────────────────┐
│ ← Kalender Barang               │
│ Filter: [Sony A7C          ▼]   │
├─────────────────────────────────┤
│                                 │
│  Oktober 2026    [<]       [>]  │
│                                 │
│  ┌─────────────────────────┐    │
│  │Sn │Sl │Rb │Km │Jm │Sb │Mg│   │
│  ├───┼───┼───┼───┼───┼───┼──┤   │
│  │   │   │   │ 1 │ 2 │ 3 │4 │   │
│  │ 5 │ 6 │ 7 │[8]│[9]│10 │11│   │  [8][9] = buffer
│  │   │   │   │░░░│░░░│███│  │   │  ███ = booked
│  │12 │13 │14 │[15│16]│17 │18│   │
│  │███│███│[17│18]│   │   │  │   │
│  │19 │20 │21 │22 │23 │24 │25│   │
│  │   │   │   │   │   │   │  │   │
│  │26 │27 │28 │29 │30 │31 │  │   │
│  └───┴───┴───┴───┴───┴───┴──┘   │
│                                 │
│  Legend:                        │
│  ■ Booked   ░ Buffer   □ Bebas  │
│                                 │
│  BOOKING BULAN INI              │
│  ┌───────────────────────────┐  │
│  │ 10–12 Okt · Rina · ACTIVE │  │
│  │ [Lihat Detail]            │  │
│  └───────────────────────────┘  │
│                                 │
└─────────────────────────────────┘
```

---

## Layar 5 — Form Penyelesaian Sewa (Denda/Kerusakan)

```
┌─────────────────────────────────┐
│ ← Penyelesaian Sewa             │
│ JS-20261010-0038 · iPhone 15 Pro│
├─────────────────────────────────┤
│                                 │
│ Tanggal Selesai Disepakati      │
│ 20 Oktober 2026                 │
│                                 │
│ Tanggal Kembali Aktual *        │
│ ┌─────────────────────────────┐ │
│ │ [dd/mm/yyyy]                │ │
│ └─────────────────────────────┘ │
│                                 │
│ ─────── KERUSAKAN ────────────  │
│                                 │
│ + Tambah Item Kerusakan         │
│ ┌─────────────────────────────┐ │
│ │ Deskripsi: Layar retak      │ │
│ │ Biaya: Rp [________]        │ │
│ │ Foto: [📷 Unggah]          │ │
│ │                    [Hapus]  │ │
│ └─────────────────────────────┘ │
│                                 │
│ [+ Tambah Kerusakan Lain]       │
│                                 │
│ ─────── RINGKASAN ────────────  │
│                                 │
│ Hari Telat:          2 hari     │
│ Denda Telat:    Rp 150.000      │
│ Potongan Kerusakan: Rp 300.000  │
│ Total Potongan:     Rp 450.000  │
│ Deposit Awal:       Rp 500.000  │
│ ─────────────────────────────   │
│ Sisa Deposit:    **Rp 50.000**  │
│                                 │
│ Catatan Vendor                  │
│ ┌─────────────────────────────┐ │
│ │ ________________________    │ │
│ └─────────────────────────────┘ │
│                                 │
│ ┌─────────────────────────────┐ │
│ │  [ KONFIRMASI PENYELESAIAN ]│ │
│ └─────────────────────────────┘ │
│                                 │
│ [Kirim Pengingat via WhatsApp]  │
└─────────────────────────────────┘
```
