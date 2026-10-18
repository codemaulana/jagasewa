# Authorization Matrix — JagaSewa

> Dokumen ini mendefinisikan siapa boleh melakukan apa terhadap sumber daya apa di platform JagaSewa.
> Semua kontrol berlabel **MVP**, **Fase 1.5**, atau **Fase 2**.

---

## 1. Peran (Roles)

| ID Peran | Nama | Deskripsi | Label |
|---|---|---|---|
| `anon` | **Penyewa Anonim** | Tidak login. Mengakses form booking publik, mengupload KTP/selfie, menggunakan `reference_code` + hash WA untuk melihat status. | MVP |
| `vendor_owner` | **Vendor Owner** | Login. Akses penuh ke seluruh data milik vendor-nya sendiri. Mengelola item, booking, blacklist, staf, dan billing. | MVP |
| `vendor_staff` | **Vendor Staff** | Login. Akses terbatas: lihat booking, proses pengembalian. Tidak dapat mengelola billing, pengaturan blacklist, atau mengundang staf. [ASUMSI: peran ini mungkin disederhanakan menjadi hanya Owner di MVP] | MVP [ASUMSI] |
| `admin` | **Admin Platform** | Tim internal. Read lintas vendor, manajemen vendor, aktivasi billing manual. Bypass filter `vendor_id` via middleware khusus. | MVP |

> [!IMPORTANT]
> Peran **Vendor Staff** adalah [ASUMSI] dan mungkin tidak diimplementasikan di MVP. Jika disederhanakan, semua kolom `Vendor Staff` default ke kolom `Vendor Owner`. Konfirmasi dengan product owner sebelum implementasi.

---

## 2. Matriks Otorisasi

**Legenda:**

| Simbol | Arti |
|---|---|
| ✅ | Diizinkan |
| ❌ | Ditolak |
| ⚠️ | Sebagian — baca kondisi di kolom keterangan |
| 🔒 | Hanya milik sendiri — `vendor_id` harus cocok dengan sesi login |

### 2.1 Items (Katalog Barang Sewa)

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Item | View public listing | ✅ | ✅ | ✅ | ✅ | Endpoint publik, hanya item `is_active = true` |
| Item | View detail publik | ✅ | ✅ | ✅ | ✅ | Endpoint publik |
| Item | Create | ❌ | 🔒 | ❌ | ✅ | Owner hanya bisa buat item untuk vendor-nya sendiri |
| Item | Edit | ❌ | 🔒 | ❌ | ✅ | Staff tidak bisa edit item [ASUMSI] |
| Item | Delete (soft delete) | ❌ | 🔒 | ❌ | ✅ | Tidak boleh hapus jika ada booking aktif |
| Item | Toggle active/inactive | ❌ | 🔒 | ⚠️ | ✅ | Staff: [ASUMSI] boleh toggle aktif, tidak boleh hapus |

### 2.2 Bookings

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Booking | Create (form publik) | ✅ | ❌ | ❌ | ❌ | Dibuat anonim, diidentifikasi oleh `reference_code` |
| Booking | View list | ❌ | 🔒 | 🔒 | ✅ | Vendor hanya lihat booking milik vendor-nya |
| Booking | View detail (penyewa) | ⚠️ | ❌ | ❌ | ✅ | Anonim: hanya via `reference_code` + verifikasi hash WA, **bukan sequential ID** |
| Booking | View detail (vendor) | ❌ | 🔒 | 🔒 | ✅ | Divalidasi `vendor_id` |
| Booking | Approve | ❌ | 🔒 | ⚠️ | ✅ | Staff: [ASUMSI] boleh approve |
| Booking | Reject | ❌ | 🔒 | ⚠️ | ✅ | Staff: [ASUMSI] boleh reject |
| Booking | Activate (barang diserahkan) | ❌ | 🔒 | ✅ | ✅ | Staff boleh proses serah terima |
| Booking | Return (barang dikembalikan) | ❌ | 🔒 | ✅ | ✅ | Staff boleh proses pengembalian |
| Booking | Close (selesai/settle) | ❌ | 🔒 | ❌ | ✅ | Hanya Owner yang finalkan penutupan [ASUMSI] |

### 2.3 Dokumen Identitas (KTP / Selfie)

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Identity Doc | Upload | ✅ | ❌ | ❌ | ❌ | Anonim upload saat booking; disimpan di private storage |
| Identity Doc | View / Generate Signed URL | ❌ | 🔒 | ⚠️ | ✅ | Owner: lihat dokumen booking milik vendor-nya. Staff: [ASUMSI] boleh lihat untuk proses serah terima. URL ditandatangani, expiry pendek |
| Identity Doc | Delete | ❌ | ❌ | ❌ | ✅ | Hanya Admin yang boleh hapus (untuk keperluan privasi/UU PDP, proses manual) |

> [!CAUTION]
> Signed URL dokumen identitas **wajib** pendek masa berlakunya (≤ 15 menit) dan dicatat di audit log setiap kali di-generate. Jangan pernah expose path storage asli ke browser.

### 2.4 Blacklist Entries

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Blacklist | Create | ❌ | 🔒 | ❌ | ✅ | Hanya Owner yang bisa tambahkan entri ke blacklist vendor-nya [ASUMSI] |
| Blacklist | View (private, HMAC) | ❌ | 🔒 | ❌ | ✅ | Endpoint HMAC-protected; Staff tidak bisa lihat [ASUMSI] |
| Blacklist | Delete | ❌ | 🔒 | ❌ | ✅ | Hanya Owner atau Admin |

### 2.5 Settlements & Damage Items

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Settlement | Create | ❌ | 🔒 | ⚠️ | ✅ | Staff: [ASUMSI] boleh buat catatan kerusakan, tidak boleh finalisasi settlement |
| Settlement | View | ❌ | 🔒 | 🔒 | ✅ | Hanya dalam konteks booking milik vendor sendiri |
| Damage Item | Create | ❌ | 🔒 | ⚠️ | ✅ | Staff boleh dokumentasikan item rusak [ASUMSI] |
| Damage Item | View | ❌ | 🔒 | 🔒 | ✅ | Dalam konteks booking milik vendor sendiri |

### 2.6 Vendor Profile

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Vendor Profile | View (publik) | ✅ | ✅ | ✅ | ✅ | Informasi dasar vendor yang ditampilkan ke penyewa |
| Vendor Profile | View (own, lengkap) | ❌ | 🔒 | 🔒 | ✅ | Termasuk data sensitif seperti saldo deposit |
| Vendor Profile | Edit own | ❌ | 🔒 | ❌ | ✅ | Hanya Owner yang edit profil vendor [ASUMSI] |
| Vendor Profile | View any (admin) | ❌ | ❌ | ❌ | ✅ | Admin bisa akses semua vendor |

### 2.7 Vendor Users (Manajemen Staf)

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Vendor User | Invite staf baru | ❌ | 🔒 | ❌ | ✅ | Hanya Owner yang bisa undang staf |
| Vendor User | Edit peran staf | ❌ | 🔒 | ❌ | ✅ | Hanya Owner |
| Vendor User | Deactivate staf | ❌ | 🔒 | ❌ | ✅ | Hanya Owner atau Admin |

### 2.8 Audit Logs

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Audit Log | View (vendor sendiri) | ❌ | 🔒 | ❌ | ✅ | Owner bisa lihat log aktivitas vendor-nya [ASUMSI] |
| Audit Log | View (semua vendor) | ❌ | ❌ | ❌ | ✅ | Hanya Admin Platform |

### 2.9 Admin: Manajemen Vendor

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Vendor (admin) | List semua vendor | ❌ | ❌ | ❌ | ✅ | Hanya via route admin |
| Vendor (admin) | Activate vendor | ❌ | ❌ | ❌ | ✅ | |
| Vendor (admin) | Deactivate/suspend vendor | ❌ | ❌ | ❌ | ✅ | |

### 2.10 Billing & Subscriptions

| Resource | Aksi | Penyewa Anonim | Vendor Owner | Vendor Staff | Admin | Keterangan |
|---|---|:---:|:---:|:---:|:---:|---|
| Billing | View invoice / status langganan | ❌ | 🔒 | ❌ | ✅ | Staff tidak boleh lihat billing [ASUMSI] | **Fase 1.5** |
| Billing | Initiate payment (vendor) | ❌ | 🔒 | ❌ | ❌ | Hanya Owner yang bisa inisiasi pembayaran | **Fase 1.5** |
| Billing | Admin activate subscription manual | ❌ | ❌ | ❌ | ✅ | Untuk kasus pembayaran manual/transfer | **Fase 1.5** |

---

## 3. Aturan Kepemilikan (Ownership Rules)

> [!IMPORTANT]
> Pelanggaran aturan kepemilikan ini adalah kategori **P0 — kritis**. Setiap query resource yang tidak dilindungi `vendor_id` dianggap celah IDOR.

### 3.1 Prinsip Dasar

| Aturan | Detail |
|---|---|
| **Wajib filter `vendor_id`** | Setiap query resource HARUS menyertakan `WHERE vendor_id = auth()->user()->vendor_id`. Tidak ada pengecualian kecuali Admin. |
| **404 bukan 403** | Jika resource tidak ditemukan atau `vendor_id` tidak cocok → kembalikan **HTTP 404**. Jangan gunakan 403 karena dapat membantu enumerasi keberadaan resource. |
| **Tidak ada sequential ID di URL** | Semua public ID menggunakan **ULID/UUID**. Penyewa anonim mengakses booking via `reference_code` + verifikasi hash WA, bukan ID numerik. |
| **Admin bypass via middleware** | Admin tidak di-filter `vendor_id`, tetapi harus melewati middleware `EnsureIsAdmin` pada route group terpisah (`/admin/...`). Admin tidak boleh menggunakan route vendor biasa. |
| **Penyewa anonim dibatasi endpoint publik** | Tidak ada akses ke route yang memerlukan autentikasi. Identifikasi booking via `reference_code` saja. |

### 3.2 Alur Validasi Kepemilikan

```
Request masuk
    │
    ├─ Route publik (/api/public/...) → lewati auth, validasi reference_code + WA hash
    │
    ├─ Route vendor (/vendor/...) → middleware auth → ambil vendor_id dari sesi
    │       │
    │       ├─ Query resource + WHERE vendor_id = auth_vendor_id
    │       └─ Resource tidak ditemukan → 404 (bukan 403)
    │
    └─ Route admin (/admin/...) → middleware EnsureIsAdmin → bypass filter vendor_id
```

---

## 4. Kebijakan Laravel (Policy Mapping)

Semua akses resource harus melewati **Laravel Policy** — bukan hanya middleware. Policy dipanggil via `$this->authorize()` di controller atau `@can` di view.

| Resource | Laravel Policy Class | Key Method(s) |
|---|---|---|
| Item | `App\Policies\ItemPolicy` | `viewAny`, `view`, `create`, `update`, `delete`, `toggleActive` |
| Booking | `App\Policies\BookingPolicy` | `viewAny`, `view`, `approve`, `reject`, `activate`, `return`, `close` |
| Identity Document | `App\Policies\IdentityDocumentPolicy` | `view`, `generateSignedUrl`, `delete` |
| Blacklist Entry | `App\Policies\BlacklistEntryPolicy` | `viewAny`, `view`, `create`, `delete` |
| Settlement | `App\Policies\SettlementPolicy` | `create`, `view` |
| Damage Item | `App\Policies\DamageItemPolicy` | `create`, `view` |
| Vendor Profile | `App\Policies\VendorPolicy` | `viewOwn`, `update`, `viewAny` (admin) |
| Vendor User | `App\Policies\VendorUserPolicy` | `invite`, `update`, `deactivate` |
| Audit Log | `App\Policies\AuditLogPolicy` | `viewOwn`, `viewAll` |
| Billing / Subscription | `App\Policies\BillingPolicy` | `view`, `initiatePayment`, `adminActivate` |

> [!NOTE]
> Untuk endpoint publik anonim (booking create, identity upload), tidak menggunakan Policy karena tidak ada `Auth::user()`. Validasi input dilakukan via **Form Request** + rate limiter.
> Ikuti dokumentasi resmi Laravel tentang Policy authorization gates dan `before()` hook untuk Admin.

---

## 5. Daftar Tes IDOR Wajib

Semua tes ini merujuk ke kontrol **SEC-08**, **SEC-09**, **SEC-10** dari `security.md`.

> [!WARNING]
> Tes IDOR wajib dijalankan sebelum setiap release ke production. Kegagalan pada tes P0 **memblokir deploy**.

| Test ID | Skenario | Request | Ekspektasi | Prioritas | Ref Kontrol |
|---|---|---|---|:---:|---|
| IDOR-01 | Vendor A mengakses item milik Vendor B | `GET /vendor/items/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |
| IDOR-02 | Vendor A mengakses booking milik Vendor B | `GET /vendor/bookings/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |
| IDOR-03 | Vendor A men-generate signed URL dokumen identitas milik Vendor B | `POST /vendor/identity-documents/{ulid_vendor_b}/signed-url` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08, SEC-09 |
| IDOR-04 | Vendor A mengakses entri blacklist milik Vendor B | `GET /vendor/blacklist/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |
| IDOR-05 | Vendor A mengakses settlement milik Vendor B | `GET /vendor/settlements/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |
| IDOR-06 | Vendor A mengakses audit log milik Vendor B | `GET /vendor/audit-logs?vendor_id={id_vendor_b}` dengan sesi Vendor A | HTTP 403 atau log yang dikembalikan hanya milik Vendor A | P0 | SEC-08, SEC-10 |
| IDOR-07 | Vendor Staff mengakses halaman billing | `GET /vendor/billing` dengan sesi Vendor Staff | HTTP 403 | P1 | SEC-09 |
| IDOR-08 | Penyewa anonim mengakses route dashboard vendor | `GET /vendor/dashboard` tanpa sesi login | Redirect ke halaman login (HTTP 302) atau HTTP 401 | P0 | SEC-08 |
| IDOR-09 | Admin mengakses resource via route vendor (bukan route admin) | `GET /vendor/bookings` dengan sesi Admin (bukan route `/admin/...`) | HTTP 403 — Admin harus gunakan route `/admin/...` | P1 | SEC-10 |
| IDOR-10 | Penyewa anonim enumerasi booking dengan sequential ID numerik | `GET /api/public/bookings/12345` (ID numerik) | HTTP 404 — endpoint publik hanya terima `reference_code`, bukan ID | P0 | SEC-09 |
| IDOR-11 | Vendor A menghapus item milik Vendor B | `DELETE /vendor/items/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |
| IDOR-12 | Vendor Staff membuat entri blacklist | `POST /vendor/blacklist` dengan sesi Vendor Staff | HTTP 403 | P1 | SEC-09 |
| IDOR-13 | Penyewa anonim menebak `reference_code` booking penyewa lain | `GET /api/public/bookings/{reference_code_orang_lain}` | HTTP 200 hanya jika hash WA cocok; tanpa WA hash → HTTP 403 | P0 | SEC-08, SEC-09 |
| IDOR-14 | Vendor A mengedit profil Vendor B via ULID | `PUT /vendor/profile/{ulid_vendor_b}` dengan sesi Vendor A | HTTP 404 | P0 | SEC-08 |

> [!NOTE]
> IDOR-13 ditambahkan melampaui minimum 12 karena reference_code tanpa verifikasi WA adalah attack vector nyata pada sistem tanpa login penyewa.

---

*Dokumen ini adalah living document. Perbarui setiap kali resource baru ditambahkan atau peran berubah.*
*Versi: 1.0 — MVP*
