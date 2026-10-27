# JagaSewa — QRIS & Real-time Webhook Microservice (Golang)

Microservice berkecepatan tinggi berbasis **Golang** yang bertugas menangani pembuatan QRIS dinamis, penerimaan webhook pembayaran dari bank/gateway (Tripay, Midtrans, Mayar), dan streaming status pembayaran langsung ke browser penyewa via **Server-Sent Events (SSE)**.

---

## 1. Arsitektur Bersama Laravel

```
[ Penyewa / Renter ]
       │  1. Scan QRIS & Bayar via m-Banking/e-Wallet
       ▼
[ Bank / Payment Gateway ]
       │  2. Webhook Callback (POST)
       ▼
[ Golang QRIS Engine (:8080) ] ──── 3. SSE Stream Real-time (<100ms) ────► [ HP Penyewa: Lunas! ]
       │
       │  4. HTTP Callback Sinkronisasi
       ▼
[ Laravel Core Monolith (:8000) ]
   (Update Booking -> Disetujui/Lunas, Notifikasi WA Vendor)
```

---

## 2. Fitur Utama

- **Pembuatan QRIS Dinamis Instan:** Menghasilkan payload string QRIS dan gambar QR Code PNG (Base64) tanpa dependensi pihak ketiga.
- **Penerimaan Webhook Super Cepat:** Verifikasi *cryptographic signature* (HMAC-SHA256) dalam hitungan mikrosekon dengan pencegahan duplikasi (*idempotency protection*).
- **Real-time SSE Event Stream:** Endpoint `GET /api/v1/qris/stream/:reference_code` memungkinkan browser penyewa langsung berganti halaman ke *"Pembayaran Berhasil"* begitu uang masuk di bank.
- **Hemat Memori:** Hanya memakan ~15 MB RAM, sanggup menangani ribuan koneksi konkuren simultan via Goroutines.
- **Simulator Bawaan:** Halaman testing visual di `http://localhost:8080/simulator` untuk simulasi scan dan bayar langsung.

---

## 3. Cara Menjalankan

### Menjalankan Lokal (Development)
```bash
cd services/qris-engine
go run ./cmd/server
```

Server akan aktif di `http://localhost:8080`.

### Menjalankan Unit Test
```bash
cd services/qris-engine
go test -v ./tests/...
```

---

## 4. Daftar Endpoint API

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/health` | Cek status kesehatan, uptime, dan pemakaian memori servis |
| `GET` | `/simulator` | Halaman web simulasi QRIS & live webhook |
| `POST` | `/api/v1/qris/create` | Buat transaksi QRIS dinamis |
| `GET` | `/api/v1/qris/status/:ref` | Cek status transaksi via JSON polling |
| `GET` | `/api/v1/qris/stream/:ref` | Real-time Server-Sent Events (SSE) status stream |
| `POST` | `/api/v1/qris/simulate-pay` | Trigger simulasi bayar (BCA / GoPay / OVO) |
| `POST` | `/api/v1/webhooks/qris` | Endpoint penerima webhook resmi dari Payment Gateway |
