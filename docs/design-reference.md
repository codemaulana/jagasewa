# design-reference.md: referensi gaya untuk JagaSewa

Sumber: SATU screenshot desktop dari landing page SaaS "pencari kerja" (karya orang lain).
Dipakai sebagai referensi GAYA, bukan untuk disalin. Semua nilai angka bertanda [PERKIRAAN]
dibaca dari gambar; verifikasi dengan color picker dan alat kontras sebelum dikunci sebagai token.
Yang tidak terlihat di referensi: tampilan mobile/tablet, state hover/fokus/error, dashboard.
Itu harus diturunkan dari aturan di docs/design-system.md.

## 1. Karakter visual
Minimal, netral hangat, tipografi besar dan rapat, satu aksen lime, kartu bergaya bento,
banyak ruang kosong, permukaan putih dengan border tipis dan bayangan sangat lembut.

## 2. Palet [PERKIRAAN]
| Peran | Nilai | Catatan |
|---|---|---|
| Latar halaman | #F2F2F0 | abu hangat sangat terang |
| Permukaan (kartu) | #FFFFFF | border 1px #E5E5E3 |
| Teks utama | #0A0A0A | hampir hitam |
| Teks sekunder | #5C5C5C | di referensi terlihat lebih terang (sekitar #A0A0A0) dan GAGAL kontras AA; pakai yang lebih gelap |
| Primer (tombol utama) | #111111 | tombol hitam, teks putih |
| Aksen | #86F04C | lime; HANYA sebagai latar dengan teks gelap, tidak pernah teks lime di atas putih |
| Glow | #D9F5C8 | gradasi hijau sangat tipis di dasar section, opsional |
| Kartu gelap | #111111 | untuk satu kartu yang disorot (mis. paket unggulan) |
Aturan kontras: teks di atas lime wajib gelap; teks sekunder minimal 4.5:1 terhadap latarnya.

## 3. Tipografi
- Keluarga: grotesk netral dan rapat, mirip Inter Tight atau Geist [PERKIRAAN, cek deskripsi
  shot]. Alternatif gratis: Inter Tight, Geist. Di-host sendiri.
- Judul hero: sangat besar, bobot sekitar 600, line-height sekitar 1.0, letter-spacing sekitar
  -0.04em. Di JagaSewa pakai skala responsif (mis. clamp(2.25rem, 6vw, 5rem)), bukan ukuran tetap.
- Pola khas: paragraf dua nada, bagian kunci gelap dan sisanya abu. Boleh dipakai, tapi abu harus
  lolos kontras.
- Teks isi: 14-16px. Teks di referensi sering 10-12px, jangan ditiru; minimum 12px untuk keterangan.

## 4. Bentuk dan permukaan
- Radius: pembungkus besar sekitar 24px, kartu 12-16px, tombol utama berbentuk pil (penuh),
  tombol kecil di dalam kartu sekitar 6px. Pilih satu skala dan simpan sebagai token.
- Border 1px halus, bayangan sangat tipis dan menyebar. Tanpa gradasi tebal.
- Navigasi: pil putih melayang di tengah atas; satu item disorot (latar gelap, teks lime).

## 5. Pola layout dan komponen
- Hero terpusat: judul besar, satu kalimat penjelas, satu tombol aksen.
- Section dengan judul terpusat lalu grid bento (kartu berbeda ukuran, tiap kartu memuat
  cuplikan UI mini).
- Dua kartu harga berdampingan, salah satu versi gelap sebagai sorotan.
- Banner promosi berlatar gradasi lime lembut.
- FAQ berupa daftar akordeon dengan ikon plus.
- Elemen dekoratif (daftar teks memudar di sisi hero, ikon 3D melayang, chip mengambang):
  HANYA untuk landing page dan opsional.

## 6. Pemetaan ke JagaSewa
| Elemen referensi | Dipakai? | Catatan |
|---|---|---|
| Palet netral hangat + tombol hitam | Ya | Dasar seluruh UI, termasuk dashboard |
| Tipografi besar rapat | Ya, untuk landing | Di dashboard pakai skala ringkas (14px) |
| Aksen lime | Terbatas | Landing dan satu CTA utama halaman publik. Jangan dipakai sebagai warna status sukses agar tidak rancu |
| Kartu bento | Ya | Landing vendor dan kartu barang di halaman booking |
| Nav pil melayang | Ya, untuk landing | Dashboard tetap memakai sidebar |
| Kartu harga | Nanti | Saat fase billing 1.5 |
| FAQ akordeon | Ya | Landing dan halaman booking (privasi KTP, aturan sewa) |
| Dekorasi 3D, chip melayang | Tidak | Berat, dan aset aslinya milik orang lain |
| Teks 10-12px dan abu terlalu terang | Tidak | Melanggar WCAG AA dan sulit dibaca di HP |

## 7. Penerapan per halaman
- Landing vendor (akuisisi): paling dekat dengan referensi. Hero + 3 kartu bento (kalender buffer,
  verifikasi identitas, denda dan deposit) + FAQ + daftar tunggu.
- Halaman booking publik: ambil nuansa netral, kartu putih, tombol hitam, satu aksen. Prioritas
  kepercayaan dan keterbacaan di HP, bukan dekorasi.
- Dashboard vendor: netral dulu, tombol primer hitam, warna hanya untuk status (maks 5 grup
  semantik). Tanpa emoji. Tanpa elemen dekoratif.

## 8. Jangan disalin
Ilustrasi, ikon 3D, logo, nama merek, foto orang, teks asli, dan komposisi layar secara
persis. Ambil sistemnya (warna, tipografi, bentuk, pola), buat konten dan aset sendiri.
