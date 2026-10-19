# Sub agent JagaSewa

Empat sub agent, satu fungsi masing-masing:

| Agent | Tugas | Menulis kode? |
|---|---|---|
| `product-planner` | prd, user stories, business rules, ERD, tasks, AGENTS.md | Tidak |
| `security-architect` | security, otorisasi, billing, privasi, arsitektur, tes, ops | Tidak |
| `ui-ux-designer` | design system, lalu penerapan UI per halaman | Hanya lapisan UI |
| `app-builder` | membangun aplikasi per tugas T-xx | Ya |

## Lokasi file

**Claude Code** (format terverifikasi di dokumentasi resmi):
- Per proyek: `.claude/agents/<nama>.md`
- Semua proyek: `~/.claude/agents/<nama>.md`
- Jika agen tidak terdeteksi, mulai ulang Claude Code, lalu cek lewat `/agents`.

**Antigravity CLI** (format terverifikasi di dokumentasi resmi):
- Per proyek: `.agents/agents/<nama>/agent.md`
- Semua proyek: `~/.gemini/config/agents/<nama>/agent.md`
- Pilih agen lewat perintah `/agents`.
- Catatan: untuk Antigravity IDE/aplikasi desktop, aku belum memverifikasi apakah file ini
  dimuat dengan cara yang sama. Cek dokumentasi Antigravity.

Isi (badan prompt) kedua format sama. Perbedaannya hanya lokasi dan kolom frontmatter
(`tools` dan `model` hanya ada di versi Claude Code).

## Urutan pemakaian

1. `product-planner` -> baca dokumen -> jawab pertanyaan terbuka -> koreksi asumsi.
2. `security-architect` -> baca `security.md`, `authorization-matrix.md`, `billing.md`.
3. `ui-ux-designer` Langkah 1 -> setujui design system -> lanjut Langkah 2, 3, 4.
4. `app-builder` -> satu tugas T-xx per panggilan.

## Contoh perintah

- "Gunakan subagent product-planner untuk menyusun semua dokumen perencanaan JagaSewa."
- "Gunakan subagent security-architect untuk menyusun dokumen keamanan, billing, dan operasional."
- "Gunakan subagent ui-ux-designer untuk Langkah 1: audit dan buat design system."
- "Gunakan subagent app-builder untuk mengerjakan tugas T-01."

## Hal yang perlu diingat

- Sub agent tidak bisa memanggil sub agent lain. Urutannya kamu atau agen utama yang mengatur.
- Setiap panggilan dimulai dengan konteks kosong, jadi `docs/` dan `AGENTS.md` adalah ingatannya.
  Perbarui dokumen lebih dulu bila aturan berubah, baru kodenya.
- Agen perencana, keamanan, dan desain berhenti dan melapor di tiap titik persetujuan.
  Pertanyaan terbuka mereka disampaikan lewat laporan, bukan dijawab sendiri.
- Baca `business-rules.md` dan `security.md` sebelum mulai membangun. Kesalahan di dua file itu
  paling mahal.
- Dokumen hukum (`legal-drafts.md`, privasi, blacklist bersama) hanya kerangka. Konsultasikan ke
  ahli hukum sebelum menerima vendor sungguhan.
