# legal-drafts.md — JagaSewa

> [!CAUTION]
> **DRAF — Wajib ditinjau dan disetujui oleh ahli hukum yang kompeten sebelum digunakan secara resmi. Dokumen ini tidak merupakan nasihat hukum dan tidak mengklaim kepatuhan terhadap hukum atau regulasi apa pun.**

---

## Bagian 1: Kerangka Syarat Layanan Vendor (Perjanjian Platform)

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.**

Dokumen ini merupakan **kerangka/outline** Syarat Layanan untuk vendor yang mendaftar ke platform JagaSewa. Teks akhir harus disusun dan divalidasi oleh ahli hukum yang kompeten.

---

### 1.1 Definisi

| Istilah | Definisi |
|---|---|
| **Platform** | Aplikasi web JagaSewa yang dapat diakses di [URL Platform] |
| **Vendor** | Pihak yang mendaftarkan diri sebagai pemilik/pengelola barang sewaan melalui Platform |
| **Penyewa** | Pihak yang melakukan pemesanan barang melalui Platform tanpa membuat akun |
| **Booking** | Permintaan sewa yang dibuat oleh Penyewa melalui Platform |
| **KTP** | Kartu Tanda Penduduk sebagaimana dimaksud dalam peraturan perundang-undangan yang berlaku |
| **Data Penyewa** | Informasi pribadi Penyewa yang dikumpulkan dalam proses Booking, termasuk foto KTP dan selfie |
| **Blacklist Vendor** | Daftar penyewa yang diblokir oleh Vendor tertentu, bersifat pribadi dan tidak dibagikan kepada Vendor lain (MVP) |
| **Layanan Langganan** | Paket berlangganan berbayar yang memberikan akses fitur Platform kepada Vendor [Fase 1.5] |

---

### 1.2 Pendaftaran dan Akun Vendor

- Vendor wajib mendaftar menggunakan identitas yang benar dan dapat diverifikasi.
- Vendor bertanggung jawab atas keamanan kredensial akun (username dan password).
- Satu entitas bisnis hanya diperbolehkan memiliki **[ISIAN: jumlah akun]** akun aktif.
- JagaSewa berhak menolak atau menangguhkan pendaftaran tanpa memberikan alasan [perlu tinjauan hukum mengenai batasan hak ini].
- Vendor wajib menginformasikan perubahan data identitas/bisnis kepada JagaSewa dalam **[ISIAN: X hari]** hari kerja.

---

### 1.3 Hak dan Kewajiban JagaSewa

**Hak JagaSewa:**
- Menangguhkan atau menghentikan akun Vendor yang melanggar Syarat Layanan ini.
- Mengubah fitur, tampilan, atau ketersediaan Platform dengan pemberitahuan kepada Vendor.
- Menetapkan batas maksimum jumlah listing barang per akun Vendor [ISIAN].
- Memungut biaya langganan sesuai paket yang dipilih Vendor [Fase 1.5].

**Kewajiban JagaSewa:**
- Menyediakan Platform yang dapat diakses sesuai dengan Service Level Agreement (SLA) [ISIAN: uptime target, misal 99,5%].
- Memberikan pemberitahuan kepada Vendor setidaknya **[ISIAN: X hari]** sebelum perubahan Syarat Layanan yang material.
- Menjaga keamanan data akun Vendor sesuai kebijakan keamanan yang berlaku.
- Memberikan notifikasi Booking kepada Vendor secara tepat waktu.

---

### 1.4 Hak dan Kewajiban Vendor

**Hak Vendor:**
- Mengakses dashboard pengelolaan listing, kalender, dan Blacklist Vendor.
- Menerima notifikasi Booking melalui saluran yang tersedia (WhatsApp, email).
- Menolak atau menyetujui Booking sesuai kebijakan internal Vendor.

**Kewajiban Vendor:**
- **Keakuratan Deskripsi Barang:** Vendor sepenuhnya bertanggung jawab atas keakuratan, kelengkapan, dan kejujuran informasi barang sewaan yang ditampilkan di Platform, termasuk kondisi barang, harga, dan ketersediaan.
- **Penanganan Uang Sewa:** Vendor bertanggung jawab penuh atas seluruh transaksi keuangan dengan Penyewa. Pembayaran uang sewa dilakukan **langsung antara Penyewa dan Vendor di luar Platform**. JagaSewa tidak terlibat dalam transaksi keuangan tersebut.
- **Kerahasiaan Data Penyewa:** Vendor wajib menjaga kerahasiaan Data Penyewa yang diperoleh melalui Platform, menggunakannya hanya untuk tujuan verifikasi identitas Booking, dan tidak membagikannya kepada pihak ketiga tanpa dasar hukum yang sah.
- Mematuhi seluruh peraturan perundang-undangan yang berlaku dalam menjalankan usaha persewaan barang.
- Segera merespons Booking dalam **[ISIAN: X jam]** jam setelah notifikasi diterima.
- Menggunakan fitur Blacklist Vendor hanya untuk tujuan yang sah dan dapat dipertanggungjawabkan.

---

### 1.5 Batasan Layanan

Platform JagaSewa **tidak** berfungsi sebagai:

| Fungsi | Keterangan |
|---|---|
| **Perantara keuangan** | JagaSewa tidak menerima, menyimpan, atau meneruskan pembayaran uang sewa antara Vendor dan Penyewa |
| **Penjamin transaksi** | JagaSewa tidak menjamin terlaksananya transaksi sewa atau kualitas barang yang disewakan |
| **Lembaga penyelesaian sengketa** | JagaSewa tidak memiliki kewenangan memutus sengketa antara Vendor dan Penyewa |
| **Pihak dalam kontrak sewa** | Perjanjian sewa terjadi langsung antara Vendor dan Penyewa |
| **Verifikator identitas** | JagaSewa meneruskan Data Penyewa kepada Vendor namun tidak memverifikasi keaslian KTP atau identitas Penyewa |

---

### 1.6 Blacklist Vendor

- **MVP:** Blacklist bersifat **pribadi per Vendor**. Data blacklist seorang Vendor tidak dapat diakses oleh Vendor lain maupun pihak ketiga.
- Vendor bertanggung jawab penuh atas keakuratan data yang dimasukkan ke dalam Blacklist Vendor mereka.
- **JagaSewa tidak memvalidasi** kebenaran atau dasar hukum dari entri Blacklist yang dibuat Vendor.
- Vendor wajib memastikan bahwa penggunaan fitur Blacklist tidak melanggar hak individu yang didaftarkan, termasuk namun tidak terbatas pada hak atas data pribadi berdasarkan peraturan yang berlaku.
- Penyalahgunaan fitur Blacklist (misal: diskriminasi tidak berdasar) merupakan tanggung jawab Vendor sepenuhnya.
- **Fase 2:** Blacklist bersama (shared blacklist) antar Vendor memerlukan tinjauan hukum tersendiri sebelum dapat diimplementasikan.

---

### 1.7 Privasi dan Perlindungan Data

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.** Bagian ini membutuhkan tinjauan mendalam terhadap UU Perlindungan Data Pribadi (UU PDP) dan peraturan pelaksanaannya.

- Vendor bertindak sebagai **pengendali data** (data controller) atas Data Penyewa yang dikumpulkan melalui Platform dalam rangka Booking.
- JagaSewa bertindak sebagai **pemroses data** (data processor) yang menyediakan infrastruktur penyimpanan sementara.
- Vendor wajib memastikan adanya dasar hukum yang sah untuk setiap pemrosesan Data Penyewa [perlu tinjauan hukum mengenai bentuk persetujuan yang memadai].
- Vendor dilarang menggunakan Data Penyewa untuk tujuan selain verifikasi identitas Booking yang bersangkutan.
- Vendor wajib mengakomodasi hak-hak subjek data sebagaimana diatur dalam peraturan perundang-undangan yang berlaku, termasuk hak akses, koreksi, dan penghapusan data [perlu tinjauan hukum].
- Kewajiban lebih lanjut terkait UU PDP dan regulasi terkait **wajib dikonfirmasi oleh ahli hukum** sebelum Platform diluncurkan.

---

### 1.8 Pembayaran Langganan [Fase 1.5]

> [!NOTE]
> Bagian ini berlaku mulai Fase 1.5 dan belum diimplementasikan pada MVP.

- Vendor wajib membayar biaya langganan sesuai paket yang dipilih, dengan nominal **[ISIAN]** per **[ISIAN: periode]**.
- Pembayaran dilakukan melalui metode yang disediakan oleh JagaSewa [ISIAN: payment gateway].
- Akses ke fitur premium akan dinonaktifkan jika pembayaran tidak diterima dalam **[ISIAN: X hari]** setelah jatuh tempo.
- Kebijakan refund dan pembatalan langganan: [ISIAN — perlu tinjauan hukum].

---

### 1.9 Penghentian Layanan

| Skenario | Prosedur |
|---|---|
| Vendor mengakhiri layanan secara sukarela | Vendor mengajukan penghentian melalui dashboard; data akun dihapus dalam [ISIAN] hari |
| JagaSewa menghentikan akun karena pelanggaran | Penangguhan segera; notifikasi kepada Vendor; hak banding [ISIAN] |
| JagaSewa menghentikan operasi Platform | Pemberitahuan kepada Vendor minimal [ISIAN] hari sebelumnya |
| Force majeure | [perlu ketentuan khusus — tinjauan hukum] |

Setelah penghentian, data yang wajib dipertahankan akan mengikuti kebijakan retensi yang berlaku (lihat `privacy-data-retention.md`).

---

### 1.10 Penyelesaian Sengketa dan Hukum yang Berlaku

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.**

- Syarat Layanan ini tunduk pada **hukum Negara Republik Indonesia**.
- Segala sengketa yang timbul dari atau berkaitan dengan Syarat Layanan ini akan diselesaikan melalui musyawarah mufakat terlebih dahulu dalam jangka waktu **[ISIAN: X hari]** hari.
- Apabila musyawarah tidak menghasilkan kesepakatan, sengketa akan diselesaikan melalui **[ISIAN: arbitrase/pengadilan]** yang berkedudukan di **[ASUMSI: kota tempat JagaSewa berdomisili hukum — perlu dikonfirmasi]**.
- [ASUMSI: Klausul arbitrase memerlukan tinjauan hukum untuk memastikan kesesuaian dengan peraturan yang berlaku]

---

### 1.11 Perubahan Syarat

- JagaSewa berhak mengubah Syarat Layanan ini sewaktu-waktu.
- Perubahan material akan diberitahukan kepada Vendor melalui **[ISIAN: email / notifikasi dashboard]** minimal **[ISIAN: X hari]** sebelum berlaku.
- Penggunaan Platform setelah tanggal berlaku perubahan dianggap sebagai penerimaan Vendor atas syarat yang baru [perlu tinjauan hukum mengenai keabsahan mekanisme ini].

---

## Bagian 2: Kerangka Kebijakan Privasi JagaSewa

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.**

Dokumen ini merupakan **kerangka/outline** Kebijakan Privasi JagaSewa untuk penyewa dan vendor. Teks akhir harus disusun dan divalidasi oleh ahli hukum yang kompeten, khususnya terkait UU PDP dan regulasi turunannya.

---

### 2.1 Data yang Dikumpulkan

#### Data Penyewa

| Kategori | Data | Cara Pengumpulan | Wajib/Opsional |
|---|---|---|---|
| Identitas | Foto KTP | Upload manual saat Booking | Wajib |
| Identitas | Foto selfie dengan KTP | Upload manual saat Booking | Wajib |
| Kontak | Nomor WhatsApp | Input form Booking | Wajib |
| Kontak | Nama lengkap | Input form Booking | Wajib |
| Transaksi | Kode Booking, tanggal, item yang disewa | Sistem otomatis | Otomatis |

#### Data Vendor

| Kategori | Data | Cara Pengumpulan | Wajib/Opsional |
|---|---|---|---|
| Akun | Nama, email, nomor telepon | Registrasi | Wajib |
| Bisnis | Nama usaha, alamat, deskripsi [ISIAN] | Registrasi/profil | Wajib |
| Pembayaran | Data tagihan langganan [Fase 1.5] | Input saat berlangganan | Wajib [Fase 1.5] |

#### Data Teknis

| Kategori | Data | Sumber |
|---|---|---|
| Sesi | Session ID, CSRF token | Server (otomatis) |
| Log | IP address, user agent, timestamp request | Server log |
| [ASUMSI] | Tidak ada cookie analytics/tracking pihak ketiga di MVP | — |

---

### 2.2 Tujuan Penggunaan Data

| Data | Tujuan | Dasar [ASUMSI — perlu konfirmasi hukum] |
|---|---|---|
| Foto KTP + selfie Penyewa | Verifikasi identitas untuk Booking oleh Vendor | Persetujuan (consent) Penyewa |
| Nomor WhatsApp Penyewa | Notifikasi status Booking; link wa.me ke Vendor | Persetujuan (consent) Penyewa |
| Data akun Vendor | Pengelolaan akun, autentikasi, komunikasi layanan | Pelaksanaan kontrak |
| Data teknis | Keamanan sistem, debugging, pencegahan penyalahgunaan | Kepentingan sah (legitimate interest) [ASUMSI] |
| Data tagihan Vendor [Fase 1.5] | Pemrosesan pembayaran langganan | Pelaksanaan kontrak |

---

### 2.3 Dasar Pemrosesan

> [!CAUTION]
> **[ASUMSI — perlu konfirmasi hukum]** Dasar pemrosesan data di bawah ini merupakan asumsi awal dan **wajib dikonfirmasi** oleh ahli hukum berdasarkan UU PDP dan peraturan pelaksanaannya sebelum Platform diluncurkan.

| Dasar | Data yang Dicakup |
|---|---|
| Persetujuan (consent) | Data identitas Penyewa (KTP, selfie, kontak) |
| Pelaksanaan kontrak | Data akun Vendor, data tagihan langganan [Fase 1.5] |
| Kepentingan sah (legitimate interest) | Data teknis/log sistem [ASUMSI] |
| Kewajiban hukum | Data yang diwajibkan disimpan oleh peraturan perundang-undangan [perlu identifikasi lebih lanjut] |

---

### 2.4 Penyimpanan dan Keamanan

- Data disimpan pada server yang berlokasi di **[ISIAN: lokasi data center / cloud provider]**.
- Foto KTP dan selfie Penyewa disimpan dalam kondisi **terenkripsi at-rest** menggunakan algoritma **[ISIAN, misal: AES-256]**.
- Akses ke data Penyewa dibatasi hanya kepada Vendor yang bersangkutan dan administrator sistem yang berwenang.
- Transfer data antara klien dan server menggunakan enkripsi **TLS** (HTTPS).
- Kontrol keamanan teknis merujuk pada dokumen `security.md` JagaSewa.
- JagaSewa akan memberitahukan kepada Penyewa dan/atau otoritas yang berwenang jika terjadi kebocoran data (data breach) sesuai ketentuan yang berlaku [perlu tinjauan hukum mengenai kewajiban dan tenggat waktu pelaporan].

---

### 2.5 Retensi dan Penghapusan

Ringkasan dari kebijakan retensi (detail lihat `privacy-data-retention.md`):

| Data | Retensi | Penghapusan |
|---|---|---|
| Foto KTP + selfie Penyewa | 7 hari setelah Booking selesai (atau 3 hari jika dibatalkan/ditolak) | Otomatis oleh sistem; notifikasi ke Penyewa |
| Data Booking (metadata) | [ISIAN — tergantung kebutuhan audit] | [ISIAN] |
| Data akun Vendor (aktif) | Selama akun aktif | Setelah penghentian akun + [ISIAN] masa tenggang |
| Log sistem | [ISIAN] | Rolling basis [ISIAN] |

---

### 2.6 Hak Subjek Data

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.**

Berdasarkan peraturan yang berlaku [perlu konfirmasi hukum], subjek data berhak atas:

| Hak | Deskripsi | Cara Menggunakan |
|---|---|---|
| **Akses** | Mengetahui data apa yang disimpan oleh JagaSewa | Kirim permintaan ke [ISIAN: email privasi] |
| **Koreksi** | Meminta perbaikan data yang tidak akurat | Kirim permintaan ke [ISIAN: email privasi] dengan bukti |
| **Penghapusan** | Meminta penghapusan data sebelum jadwal retensi | Kirim permintaan ke [ISIAN: email privasi]; tunduk pada kewajiban hukum penyimpanan |
| **Penarikan Persetujuan** | Mencabut persetujuan yang telah diberikan | Kirim permintaan ke [ISIAN: email privasi]; tidak memengaruhi pemrosesan sebelum penarikan |
| **Pembatasan Pemrosesan** | Meminta pembatasan pemrosesan data dalam kondisi tertentu | [perlu tinjauan hukum mengenai syarat dan prosedur] |
| **Portabilitas** | Mendapatkan salinan data dalam format terstruktur [perlu konfirmasi apakah berlaku] | [perlu tinjauan hukum] |

JagaSewa akan merespons permintaan dalam **[ISIAN: X hari kerja]** hari kerja.

---

### 2.7 Berbagi Data dengan Pihak Ketiga

JagaSewa **tidak menjual** data pengguna kepada pihak ketiga mana pun.

| Pihak Ketiga | Data yang Dibagikan | Tujuan | Dasar |
|---|---|---|---|
| **Cloud/VPS Provider** ([ISIAN: nama provider]) | Data yang disimpan di infrastruktur mereka | Hosting Platform | Keperluan operasional; tunduk pada ToS provider |
| **Cloudflare** | Metadata lalu lintas jaringan | CDN, DDoS protection | Keperluan operasional |
| **Payment Gateway** [Fase 1.5] | Data tagihan Vendor | Pemrosesan pembayaran langganan | Pelaksanaan kontrak [Fase 1.5] |
| Pihak lain | — | Tidak dibagikan di MVP | — |

---

### 2.8 Transfer Data Lintas Batas

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.** Transfer data lintas batas memerlukan analisis hukum tersendiri terkait UU PDP dan peraturan pelaksanaannya.

- [ASUMSI] Penggunaan cloud provider dan layanan CDN (Cloudflare) berpotensi mengakibatkan data diproses di luar wilayah Indonesia.
- Kewajiban JagaSewa terkait transfer data lintas batas **wajib dikonfirmasi** oleh ahli hukum sebelum Platform diluncurkan.
- [ISIAN: kebijakan spesifik terkait lokasi penyimpanan data setelah tinjauan hukum]

---

### 2.9 Cookie dan Tracking

> [!NOTE]
> **[ASUMSI]** Implementasi cookie dan tracking di bawah ini merupakan asumsi awal berdasarkan desain MVP. Konfirmasi dengan tim teknis sebelum dipublikasikan.

| Jenis | Digunakan? | Keterangan |
|---|---|---|
| **Session cookie** | ✅ Ya | Diperlukan untuk autentikasi Vendor; bersifat esensial |
| **CSRF token** | ✅ Ya | Diperlukan untuk keamanan formulir; bersifat esensial |
| Cookie analytics/tracking | ❌ Tidak (MVP) | Tidak digunakan di MVP |
| Cookie iklan/retargeting | ❌ Tidak | Tidak digunakan |
| Pixel pelacak pihak ketiga | ❌ Tidak (MVP) | Tidak digunakan di MVP |

Cookie yang digunakan di MVP bersifat **esensial teknis** dan tidak memerlukan persetujuan terpisah [ASUMSI — perlu konfirmasi hukum].

---

### 2.10 Kontak dan Pengaduan

Pertanyaan, permintaan hak subjek data, atau pengaduan terkait privasi dapat disampaikan kepada:

- **Email Privasi:** [ISIAN: privacy@jagasewa.id atau alamat email yang ditentukan]
- **Alamat Surat:** [ISIAN: alamat lengkap JagaSewa]
- **Waktu Respons:** [ISIAN: X hari kerja]

Jika pengguna tidak puas dengan respons JagaSewa, pengguna berhak mengajukan pengaduan kepada otoritas pengawas perlindungan data yang berwenang [perlu tinjauan hukum mengenai otoritas yang berwenang di Indonesia].

---

### 2.11 Perubahan Kebijakan

- JagaSewa dapat memperbarui Kebijakan Privasi ini sewaktu-waktu.
- Perubahan material akan diberitahukan melalui **[ISIAN: notifikasi di Platform / email]** minimal **[ISIAN: X hari]** sebelum berlaku.
- Versi terbaru Kebijakan Privasi selalu tersedia di **[ISIAN: URL halaman privasi]**.

---

## Bagian 3: Teks Persetujuan Penyewa (Consent Copy)

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.** Keabsahan mekanisme persetujuan digital ini perlu dikonfirmasi oleh ahli hukum.

---

### 3a. Versi Ringkas (untuk checkbox di form)

Teks berikut muncul di samping checkbox persetujuan pada halaman upload dokumen Penyewa:

```
Saya setuju bahwa foto KTP dan selfie saya akan digunakan oleh [Nama Vendor] untuk
memverifikasi identitas saya dalam booking ini ([kode booking]). Data ini disimpan
secara aman dan akan dihapus otomatis dalam 7 hari setelah booking selesai
(atau 3 hari jika dibatalkan/ditolak).
Dengan mencentang kotak ini, saya menyatakan telah membaca dan menyetujui
[Kebijakan Privasi JagaSewa].
```

> [!NOTE]
> Teks "[Kebijakan Privasi JagaSewa]" harus berupa tautan yang dapat diklik menuju halaman kebijakan privasi lengkap (Bagian 3b). `[Nama Vendor]` dan `[kode booking]` diisi dinamis oleh sistem.

---

### 3b. Versi Lengkap (di halaman kebijakan privasi yang dapat diklik)

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.**

---

**Informasi Perlindungan Data Pribadi untuk Penyewa JagaSewa**

Sebelum Anda mengunggah dokumen identitas untuk booking ini, harap baca informasi berikut dengan seksama.

**Siapa yang mengumpulkan data Anda?**
Data identitas Anda dikumpulkan oleh **[Nama Vendor]** (selanjutnya disebut "Vendor") melalui platform JagaSewa. Dalam konteks ini, Vendor bertindak sebagai pengendali data (data controller) yang bertanggung jawab atas penggunaan data Anda. JagaSewa menyediakan infrastruktur platform sebagai pemroses data (data processor).

**Data apa yang dikumpulkan?**
Dalam proses verifikasi booking ini, Anda diminta untuk mengunggah:
- Foto Kartu Tanda Penduduk (KTP) Anda; dan
- Foto selfie Anda bersama KTP.

Selain itu, nama lengkap dan nomor WhatsApp Anda yang Anda masukkan pada formulir booking juga dikumpulkan.

**Untuk apa data Anda digunakan?**
Data identitas Anda digunakan **semata-mata** untuk keperluan verifikasi identitas dalam rangka memproses booking Anda ([kode booking]) dengan Vendor. Data ini tidak akan digunakan untuk tujuan lain, tidak akan dijual, dan tidak akan dibagikan kepada pihak ketiga selain yang disebutkan dalam Kebijakan Privasi JagaSewa.

**Bagaimana data Anda disimpan?**
Foto KTP dan selfie Anda disimpan dalam format terenkripsi pada server aman yang dikelola oleh JagaSewa. Akses ke data Anda dibatasi hanya kepada Vendor yang bersangkutan dan administrator sistem yang berwenang. Seluruh transfer data dilindungi dengan enkripsi HTTPS/TLS.

**Berapa lama data Anda disimpan?**
Data identitas Anda (foto KTP dan selfie) akan **dihapus secara otomatis dalam 7 (tujuh) hari** setelah booking Anda dinyatakan selesai (atau 3 hari jika booking dibatalkan/ditolak). Anda akan menerima notifikasi setelah penghapusan berhasil dilakukan. Metadata booking (tanggal, item yang disewa, dll.) dapat disimpan lebih lama sesuai kebijakan retensi JagaSewa.

**Apa hak Anda?**
Anda berhak untuk mengakses, mengoreksi, atau meminta penghapusan data Anda sebelum jadwal penghapusan otomatis. Anda juga berhak menarik persetujuan Anda sewaktu-waktu. Untuk menggunakan hak-hak Anda, silakan hubungi kami melalui email di **[ISIAN: privacy@jagasewa.id]** dengan menyertakan kode booking Anda.

**Apa yang terjadi jika Anda tidak memberikan persetujuan?**
Pemberian data identitas dan persetujuan atas penggunaannya bersifat sukarela. Namun, jika Anda tidak memberikan persetujuan atau tidak mengunggah dokumen yang diminta, **Vendor berhak untuk tidak memproses atau menolak booking Anda** sesuai kebijakan Vendor yang bersangkutan.

Dengan mencentang kotak persetujuan dan mengunggah dokumen Anda, Anda menyatakan bahwa Anda telah membaca, memahami, dan menyetujui penggunaan data Anda sebagaimana dijelaskan di atas.

---

### 3c. Teks Notifikasi Penghapusan (via WA atau email)

Template pesan yang dikirim sistem kepada Penyewa setelah data berhasil dihapus secara otomatis:

**Template WhatsApp (wa.me):**
```
[JagaSewa] Data identitas Anda untuk booking [KODE] telah dihapus secara permanen
dari sistem kami pada [TANGGAL]. Terima kasih telah menggunakan JagaSewa.
```

**Template Email (alternatif):**
```
Subject: [JagaSewa] Konfirmasi Penghapusan Data - Booking [KODE]

Yth. [Nama Penyewa],

Kami ingin menginformasikan bahwa data identitas Anda (foto KTP dan selfie)
yang terkait dengan booking [KODE] telah dihapus secara permanen dari sistem
JagaSewa pada [TANGGAL WAKTU WIB].

Penghapusan ini dilakukan sesuai dengan Kebijakan Privasi JagaSewa sebagai
bagian dari komitmen kami terhadap perlindungan data pribadi Anda.

Jika Anda memiliki pertanyaan, silakan hubungi kami di [ISIAN: privacy@jagasewa.id].

Terima kasih telah menggunakan JagaSewa.

Salam,
Tim JagaSewa
```

> [!NOTE]
> Variabel `[KODE]`, `[TANGGAL]`, `[TANGGAL WAKTU WIB]`, dan `[Nama Penyewa]` diisi dinamis oleh sistem. Pastikan timezone yang digunakan adalah **Asia/Jakarta (WIB)**.

---

## Bagian 4: Template Perjanjian Sewa untuk Vendor

> [!WARNING]
> **DRAF — Wajib ditinjau ahli hukum sebelum digunakan. Tidak ada klaim kepatuhan hukum apa pun.** Ini adalah template yang dapat **disesuaikan** oleh masing-masing Vendor. Vendor bertanggung jawab penuh atas isi akhir perjanjian mereka. JagaSewa tidak bertanggung jawab atas keabsahan atau penegakan perjanjian ini.

---

```
═══════════════════════════════════════════════════════════════
              PERJANJIAN SEWA BARANG
═══════════════════════════════════════════════════════════════

DRAF — TEMPLATE. Vendor wajib menyesuaikan dan mendapatkan
tinjauan hukum sebelum digunakan secara resmi.

───────────────────────────────────────────────────────────────
HEADER PERJANJIAN
───────────────────────────────────────────────────────────────

Nomor Referensi Booking : [KODE_BOOKING]
Tanggal Perjanjian      : [TANGGAL_PERJANJIAN] WIB
Platform                : JagaSewa ([URL Platform])

PIHAK PERTAMA (Vendor / Pemberi Sewa):
  Nama Usaha / Nama     : [NAMA_VENDOR]
  Alamat                : [ALAMAT_VENDOR]
  Nomor Telepon         : [NOMOR_VENDOR]
  Email                 : [EMAIL_VENDOR]

PIHAK KEDUA (Penyewa):
  Nama Lengkap          : [NAMA_PENYEWA]
  Nomor WhatsApp        : [NOMOR_PENYEWA]
  Nomor KTP             : [NOMOR_KTP_PENYEWA] *
  (* Nomor KTP diambil dari verifikasi dokumen oleh Vendor)

───────────────────────────────────────────────────────────────
PASAL 1 — DESKRIPSI BARANG YANG DISEWA
───────────────────────────────────────────────────────────────

Pihak Pertama setuju untuk menyewakan kepada Pihak Kedua
barang dengan deskripsi sebagai berikut:

  Nama Barang           : [NAMA_BARANG]
  Kategori              : [KATEGORI_BARANG]
  Deskripsi             : [DESKRIPSI_DETAIL_BARANG]
  Kondisi Saat Diserah  : [KONDISI_BARANG, misal: Baik/Bekas/dll]
  Nomor Seri/Identitas  : [ISIAN — jika ada]

───────────────────────────────────────────────────────────────
PASAL 2 — PERIODE SEWA
───────────────────────────────────────────────────────────────

  Tanggal Mulai Sewa    : [TANGGAL_MULAI] pukul [JAM_MULAI] WIB
  Tanggal Selesai Sewa  : [TANGGAL_SELESAI] pukul [JAM_SELESAI] WIB
  Jumlah Hari Sewa      : [JUMLAH_HARI] hari

───────────────────────────────────────────────────────────────
PASAL 3 — HARGA SEWA DAN DEPOSIT
───────────────────────────────────────────────────────────────

  Harga Sewa per Hari   : Rp [HARGA_PER_HARI]
  Total Harga Sewa      : Rp [HARGA_TOTAL] ([JUMLAH_HARI] hari)
  Deposit               : Rp [DEPOSIT]
  Total yang Dibayarkan : Rp [TOTAL_BAYAR] (sewa + deposit)

CATATAN PENTING: Pembayaran dilakukan langsung kepada Pihak
Pertama (Vendor) di luar Platform JagaSewa. JagaSewa BUKAN
perantara keuangan dan tidak bertanggung jawab atas transaksi
keuangan dalam perjanjian ini.

  Metode Pembayaran     : [ISIAN oleh Vendor, misal: transfer BCA,
                          tunai, dll]
  Rekening / Info Bayar : [ISIAN oleh Vendor]

───────────────────────────────────────────────────────────────
PASAL 4 — ATURAN PENGGUNAAN BARANG
───────────────────────────────────────────────────────────────

Pihak Kedua setuju untuk mematuhi aturan penggunaan berikut:

  [ISIAN oleh Vendor — contoh:]
  1. Barang hanya boleh digunakan untuk keperluan [ISIAN].
  2. Pihak Kedua dilarang meminjamkan atau menyewakan kembali
     barang kepada pihak lain tanpa izin tertulis Pihak Pertama.
  3. Pihak Kedua wajib menggunakan barang sesuai petunjuk
     penggunaan yang diberikan Pihak Pertama.
  4. [ISIAN — tambahkan aturan spesifik barang jika ada]
  5. [ISIAN]

───────────────────────────────────────────────────────────────
PASAL 5 — KETENTUAN PENGEMBALIAN
───────────────────────────────────────────────────────────────

  Lokasi Pengembalian   : [ISIAN oleh Vendor]
  Waktu Pengembalian    : Selambatnya [TANGGAL_SELESAI] pukul
                          [JAM_SELESAI] WIB
  Prosedur Pengembalian : [ISIAN oleh Vendor — misal: pemeriksaan
                          bersama, tanda terima, dll]

Barang dikembalikan dalam kondisi yang sama seperti saat
diserahkan, dengan mempertimbangkan keausan normal yang wajar.

───────────────────────────────────────────────────────────────
PASAL 6 — DENDA KETERLAMBATAN
───────────────────────────────────────────────────────────────

Apabila Pihak Kedua terlambat mengembalikan barang melewati
waktu yang disepakati:

  Denda Keterlambatan   : Rp [FINE_PER_DAY] per hari
                          (atau proporsional per jam:
                          Rp [FINE_PER_HOUR] per jam [ISIAN])

Keterlambatan dihitung sejak [JAM_SELESAI] WIB pada
[TANGGAL_SELESAI] hingga waktu pengembalian aktual.

Denda wajib dibayarkan kepada Pihak Pertama bersamaan dengan
pengembalian barang atau dalam [ISIAN: X hari] setelahnya.

───────────────────────────────────────────────────────────────
PASAL 7 — KETENTUAN KERUSAKAN
───────────────────────────────────────────────────────────────

Apabila barang mengalami kerusakan yang bukan merupakan
keausan normal selama masa sewa:

  a. Deposit sebesar Rp [DEPOSIT] akan digunakan untuk menutup
     biaya perbaikan atau penggantian.

  b. Apabila biaya perbaikan/penggantian MELEBIHI jumlah deposit,
     Pihak Kedua WAJIB melunasi kekurangannya kepada Pihak
     Pertama dalam [ISIAN: X hari] hari kerja setelah
     penilaian kerusakan dilakukan.

  c. Apabila biaya perbaikan/penggantian LEBIH RENDAH dari
     deposit, selisihnya akan dikembalikan kepada Pihak Kedua
     dalam [ISIAN: X hari] hari kerja.

  d. Penilaian kerusakan dilakukan secara bersama-sama oleh
     kedua pihak pada saat pengembalian barang. Jika tidak
     tercapai kesepakatan, dapat melibatkan pihak ketiga yang
     disepakati [ISIAN oleh Vendor].

  e. Kerusakan akibat force majeure [ISIAN ketentuan khusus].

───────────────────────────────────────────────────────────────
PASAL 8 — PERSETUJUAN DIGITAL
───────────────────────────────────────────────────────────────

[ASUMSI — perlu tinjauan hukum mengenai keabsahan tanda tangan
dan persetujuan digital dalam konteks ini berdasarkan UU ITE
dan peraturan terkait di Indonesia]

Pihak Kedua menyatakan persetujuannya atas seluruh ketentuan
dalam perjanjian ini dengan cara mencentang kotak persetujuan
pada formulir booking di Platform JagaSewa (Booking: [KODE]).

Tindakan tersebut dianggap sebagai bentuk persetujuan digital
yang mengikat secara hukum [ASUMSI — perlu konfirmasi hukum].

  Persetujuan diberikan pada : [TIMESTAMP_CONSENT] WIB
  Alamat IP                  : [IP_ADDRESS_PENYEWA]
  User Agent                 : [USER_AGENT_PENYEWA]

(Data di atas dicatat otomatis oleh sistem untuk keperluan
audit trail [perlu tinjauan hukum mengenai relevansi dan
penyimpanan data ini])

───────────────────────────────────────────────────────────────
PASAL 9 — CATATAN PLATFORM
───────────────────────────────────────────────────────────────

  • Perjanjian ini BUKAN instrumen keuangan.
  • Pembayaran sewa dilakukan di luar Platform JagaSewa,
    langsung antara Pihak Pertama dan Pihak Kedua.
  • JagaSewa tidak menjadi pihak dalam perjanjian ini dan
    tidak bertanggung jawab atas pelaksanaannya.
  • Template ini disediakan sebagai kemudahan. Vendor
    bertanggung jawab penuh atas isi dan keabsahan
    perjanjian akhir mereka.

═══════════════════════════════════════════════════════════════
DRAF TEMPLATE v0.1 — Perlu Tinjauan Hukum Sebelum Digunakan
═══════════════════════════════════════════════════════════════
```

---

> [!CAUTION]
> **DRAF — Wajib ditinjau dan disetujui oleh ahli hukum yang kompeten sebelum digunakan secara resmi. Dokumen ini tidak merupakan nasihat hukum dan tidak mengklaim kepatuhan terhadap hukum atau regulasi apa pun.**

---

*Versi Draf: 0.1 | Tanggal: 2026-10-18 | Status: Perlu Tinjauan Hukum*
