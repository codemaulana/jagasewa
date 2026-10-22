# Definisi Metrik Finansial dan Operasional Vendor JagaSewa

Dokumen ini mendefinisikan standar perhitungan metrik pendapatan dan operasional toko agar konsisten antara halaman **Dashboard** dan **Laporan & Analitik**.

---

## 1. Metrik Pendapatan / Omzet Sewa (Gross Rental Revenue)

### Definisi
Total nominal biaya sewa (`total_price`) dari seluruh pesanan booking yang telah sah dan terkonfirmasi dalam periode yang dipilih.

### Status Booking yang Dihitung (`VALID_REVENUE_STATUSES`)
Pendapatan diakui saat pesanan mencapai salah satu status berikut:
1. `approved`: Pesanan telah disetujui oleh vendor (dan DP telah diterima).
2. `active`: Unit barang telah diambil atau diserahkan kepada penyewa.
3. `returned`: Unit barang telah dikembalikan penyewa (menunggu cek fisik / pelunasan).
4. `closed`: Seluruh transaksi dan pengembalian deposit telah selesai secara tuntas.

> [!NOTE]
> Status yang **TIDAK** dihitung dalam pendapatan:
> - `pending`: Permohonan sewa belum disetujui / belum membayar DP.
> - `rejected`: Permohonan sewa ditolak oleh vendor.
> - `cancelled_*`: Pesanan dibatalkan (timeout kedaluwarsa, pembatalan oleh penyewa, atau pembatalan oleh vendor).

### Basis Tanggal
Perhitungan didasarkan pada **tanggal mulai sewa (`start_date`)** yang jatuh pada rentang periode yang ditentukan (bukan tanggal dibuatnya pesanan `created_at`).

### Penyebab Ketidaksinkronan Sebelumnya (Temuan Audit)
- **Sebelumnya pada Dashboard (`RentalStatsOverviewWidget`):**
  Query hanya menghitung status `[active, returned, closed]` dan **mengecualikan** status `approved`. Menghasilkan angka **Rp 960.000**.
- **Sebelumnya pada Laporan (`Reports.php`):**
  Query menghitung status `[approved, active, returned, closed]`. Menghasilkan angka **Rp 1.470.000**.
- **Akar Masalah:** Pesanan dengan status `approved` senilai **Rp 510.000** belum dimasukkan ke dalam metrik Dashboard.
- **Solusi Terpadu:** Seluruh kalkulasi pendapatan kini didelegasikan ke satu service terpusat: `App\Services\VendorMetricsService`.

---

## 2. Metrik Tugas Hari Ini (Today's Operational Tasks)

Dihitung berdasarkan tanggal kalender lokal `Asia/Jakarta`:
- **Pengambilan Hari Ini:** Pesanan dengan `start_date = TODAY` dan status `pending` atau `approved`.
- **Pengembalian Hari Ini:** Pesanan dengan `end_date = TODAY` dan status `active`.
- **Terlambat:** Pesanan dengan `end_date < TODAY` dan status masih `active`.

---

## 3. Metrik Durasi & Utilisasi Barang

- **Total Hari Periode:** Dihitung inklusif antara tanggal awal dan tanggal akhir sebagai **bilangan bulat murni** menggunakan `FormatHelper::calculatePeriodDays($start, $end)`.
- **Hari Tersewa (`rented_days`):** Akumulasi integer hari dari pesanan sah (`total_days`).
- **Hari Menganggur (`idle_days`):** $\max(0, \text{Total Hari Periode} - \text{Hari Tersewa})$.
- **Tingkat Utilisasi:** $\min\left(100, \text{round}\left(\frac{\text{Hari Tersewa}}{\text{Total Hari Periode}} \times 100, 1\right)\right)\%$.
