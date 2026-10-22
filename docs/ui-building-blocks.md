# Building Blocks UI & Komponen Reusable — JagaSewa

Dokumen ini adalah panduan standar bagi developer dan AI coding agent dalam membangun antarmuka dashboard vendor JagaSewa menggunakan Laravel + Filament v5 + Livewire.

Semua komponen dirancang agar ramah bagi pemilik rental UMKM Indonesia yang non-teknis, konsisten terhadap design tokens tema terang semantik, dan bebas dari konfigurasi manual yang berulang.

---

## 1. Komponen Input Formulir (Forms)

### `RupiahInput`
Wrapper resmi untuk input mata uang Rupiah berbasis `Filament\Forms\Components\TextInput`.
Otomatis menyematkan prefix `"Rp"`, formatting mask ribuan dengan pemisah titik (`.`), mode numeric/tel pada mobile keypad, dan konversi otomatis menjadi integer bersih saat disimpan ke model.

- **Class:** `App\Filament\Support\Components\RupiahInput`
- **Helper:** `RupiahInput::make(string $name)`

#### Contoh Penggunaan:
```php
use App\Filament\Support\Components\RupiahInput;

RupiahInput::make('price_per_day')
    ->label('Harga Sewa per Hari')
    ->required(),

RupiahInput::make('deposit_amount')
    ->label('Nilai Deposit / Uang Jaminan')
    ->default(0),
```

#### Perilaku Otomatis:
1. `prefix('Rp')`
2. `numeric()` & `inputMode('numeric')`
3. `extraInputAttributes(['class' => 'tabular-nums text-right'])`
4. Dehydrate state diubah otomatis menjadi integer Rupiah murni (mencegah float / string berpemisah ke DB).

---

## 2. Komponen Kolom Tabel (Tables)

### `MoneyColumn`
Wrapper resmi untuk menampilkan nilai nominal uang Rupiah pada tabel Filament.

- **Class:** `App\Filament\Support\Tables\MoneyColumn`
- **Helper:** `MoneyColumn::make(string $name)`

#### Contoh Penggunaan:
```php
use App\Filament\Support\Tables\MoneyColumn;

MoneyColumn::make('price_per_day')
    ->label('Harga/Hari')
    ->sortable(),

MoneyColumn::make('total_price')
    ->label('Total Sewa'),
```

#### Karakteristik:
- Otomatis memformat angka menjadi format Rupiah standar (`Rp 150.000`) via `FormatHelper::rupiah($state)`.
- Otomatis rata kanan (`alignEnd()`) untuk keterbacaan baris angka moneter.
- Otomatis mengaktifkan sans `tabular-nums` agar lebar angka sejajar rapi vertikal.

---

### `DateColumn`
Wrapper resmi untuk menampilkan tanggal dalam format lokal Indonesia yang konsisten.

- **Class:** `App\Filament\Support\Tables\DateColumn`
- **Helper:** `DateColumn::make(string $name)`

#### Contoh Penggunaan:
```php
use App\Filament\Support\Tables\DateColumn;

DateColumn::make('start_date')
    ->label('Mulai Sewa')
    ->sortable(),

// Opsi dengan jam dan menit
DateColumn::make('created_at')
    ->label('Waktu Dibuat')
    ->withTime(),
```

#### Karakteristik:
- Otomatis menggunakan `FormatHelper::date($state, $withTime)` dengan timezone `Asia/Jakarta`.
- Format default: `d M Y` (misal: `22 Okt 2026`).
- Menghindari format raw ISO atau format Inggris (`Oct 22, 2026`).

---

### `StatusColumn`
Wrapper resmi untuk kolom status yang otomatis terhubung dengan Enum berbasis `HasLabel`, `HasColor`, dan `HasIcon`.

- **Class:** `App\Filament\Support\Tables\StatusColumn`
- **Helper:** `StatusColumn::make(string $name)`

#### Contoh Penggunaan:
```php
use App\Enums\BookingStatus;
use App\Filament\Support\Tables\StatusColumn;

StatusColumn::make('status')
    ->label('Status Pesanan')
    ->enumClass(BookingStatus::class),
```

#### Karakteristik:
- Otomatis mengaktifkan `badge()` Filament.
- Otomatis membaca label bahasa Indonesia via `->getLabel()`.
- Otomatis mewarnai badge via `->getColor()` (misal: `warning`, `info`, `success`, `danger`, `gray`).
- Otomatis menyematkan ikon Heroicon via `->getIcon()`.

---

## 3. Status Enums Terpusat (`App\Enums`)

Semua status di JagaSewa wajib mengimplementasikan interface Filament:
- `Filament\Support\Contracts\HasLabel`
- `Filament\Support\Contracts\HasColor`
- `Filament\Support\Contracts\HasIcon`

### Daftar Enum:
1. **`App\Enums\BookingStatus`**
   - `PENDING` ("Menunggu Konfirmasi", color: `warning`, icon: `heroicon-m-clock`)
   - `APPROVED` ("Dikonfirmasi", color: `info`, icon: `heroicon-m-check-circle`)
   - `ACTIVE` ("Sedang Disewa", color: `primary`, icon: `heroicon-m-arrow-path`)
   - `RETURNED` ("Dikembalikan", color: `purple`, icon: `heroicon-m-archive-box-arrow-down`)
   - `CLOSED` ("Selesai", color: `success`, icon: `heroicon-m-check-badge`)
   - `REJECTED` ("Ditolak", color: `danger`, icon: `heroicon-m-x-circle`)
   - `CANCELLED_TIMEOUT` ("Batal (Kedaluwarsa)", color: `gray`, icon: `heroicon-m-no-symbol`)
   - `CANCELLED_RENTER` ("Batal oleh Penyewa", color: `gray`, icon: `heroicon-m-user-minus`)
   - `CANCELLED_VENDOR` ("Batal oleh Vendor", color: `gray`, icon: `heroicon-m-x-mark`)

2. **`App\Enums\ItemStatus`**
   - `AVAILABLE` ("Tersedia", color: `success`, icon: `heroicon-m-check-circle`)
   - `RENTED` ("Sedang Disewa", color: `info`, icon: `heroicon-m-arrow-path`)
   - `MAINTENANCE` ("Perbaikan", color: `danger`, icon: `heroicon-m-wrench-screwdriver`)
   - `LAUNDRY` ("Laundry / Cuci", color: `warning`, icon: `heroicon-m-sparkles`)
   - `RETIRED` ("Dinonaktifkan", color: `gray`, icon: `heroicon-m-archive-box-x-mark`)

3. **`App\Enums\ItemCondition`**
   - `EXCELLENT` ("Sangat Baik", color: `success`)
   - `GOOD` ("Baik", color: `info`)
   - `FAIR` ("Cukup", color: `warning`)
   - `NEEDS_REPAIR` ("Perlu Perbaikan", color: `danger`)

4. **`App\Enums\VendorPlan`**
   - `FREE` ("Gratis", color: `gray`)
   - `PRO` ("Pro", color: `primary`)
   - `ENTERPRISE` ("Enterprise", color: `success`)

---

## 4. Helper Pemformatan Terpusat (`FormatHelper`)

Semua pemformatan angka, uang, dan tanggal di luar tabel/form (seperti di Blade template, Infolist, atau Notifikasi) WAJIB memanggil `App\Helpers\FormatHelper` atau shortcut helper global:

```php
use App\Helpers\FormatHelper;

// Format Rupiah
FormatHelper::rupiah(150000); // "Rp 150.000"
rupiah(150000);              // via global helper

// Format Hari (mencegah bug float)
FormatHelper::days(3);       // "3 hari"
days(3);                     // via global helper

// Kalkulasi Periode Hari Bersih (startOfDay to startOfDay)
FormatHelper::calculatePeriodDays($start, $end); // int 30 (bukan 29.999999988)

// Format Tanggal Indonesia
FormatHelper::date($carbonInstance); // "22 Okt 2026"
```

---

## 5. Konfigurasi Global Tabel Filament

Dikonfigurasi secara terpusat di `AppServiceProvider::boot()` via `Table::configureUsing()`:
- **Paginasi Default:** 10 baris per halaman, opsi pilihan: `[10, 25, 50]`.
- **Column Manager:** Aktif otomatis di semua tabel (`columnManager()`).
- **Responsif Mobile:** Otomatis tumpuk kartu pada layar ponsel (`stackedOnMobile()`).
- **Empty State:** Heading default "Belum ada data" dan deskripsi ramah berbahasa Indonesia.

---

## 6. Aturan Tipografi & Warna

1. **Angka:** Selalu gunakan font sans dengan utility `tabular-nums`. DILARANG menggunakan `font-mono` pada angka harga/metrik dashboard kecuali kode referensi pesanan/SKU.
2. **Tombol Utama:** Satu tombol utama per halaman (`bg-primary` / solid charcoal `#1B1B19` dengan teks putih).
3. **Aksen:** Lime `#C6F432` HANYA untuk lencana kecil, highlight tag, atau penanda visual ringkas, tidak boleh untuk background tombol aksi utama yang berisiko tidak terbaca.
4. **Tanpa `!important`:** Semua styling harus mematuhi tema Filament dan Vite cascading. Dilarang keras menambahkan `!important` hacks.
