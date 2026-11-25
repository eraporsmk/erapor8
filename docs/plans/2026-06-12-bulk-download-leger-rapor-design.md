# Desain: Fitur Unduh Bulk Leger Nilai & Rapor

**Tanggal:** 2026-06-12
**Scope:** Wali Kelas & Waka Kurikulum

---

## Ringkasan

Menambahkan kemampuan unduh bulk untuk dua jenis dokumen:
1. **Leger Nilai** — Waka Kurikulum dapat mengunduh leger semua rombel sekaligus dalam satu file Excel (tiap rombel = satu sheet, nama sheet = nama rombel)
2. **Rapor PDF Bulk** — Wali Kelas (kelasnya sendiri) dan Waka Kurikulum (satu rombel atau semua rombel) dapat mengunduh rapor seluruh siswa dalam format ZIP (PDF per-siswa) atau PDF gabungan, dengan komponen rapor yang dapat dipilih sendiri

---

## Fitur 1: Bulk Leger Nilai (Waka Kurikulum)

### Perubahan UI

**File:** `resources/js/pages/monitoring/unduh-legger.vue`

- Tambahkan tombol **"Unduh Semua Kelas"** di bagian header card (di samping tombol unduh per rombel yang sudah ada)
- Tombol ini tidak memerlukan pilihan rombel — langsung men-trigger download semua

### Backend

**File baru:** `app/Exports/LeggerNilaiBulkExport.php`

Class export implement WithMultipleSheets.
Tiap sheet = satu rombongan belajar.
Nama sheet = nama rombel (misal: "X TKJ 1", "XI RPL 2").
Konten tiap sheet identik dengan LeggerNilaiKurmerExport existing.

**Endpoint baru di** `app/Http/Controllers/DownloadController.php`:

GET /downloads/leger-nilai-bulk/{sekolah_id}/{semester_id}
Ambil semua rombel jenis_rombel=1 milik sekolah pada semester aktif.
Generate satu file Excel dengan multiple sheets.
Nama file: "Leger-Nilai-Semua-Kelas-{nama_semester}.xlsx"

**Route baru di** `routes/web.php`:
Route::get('/downloads/leger-nilai-bulk/{sekolah_id}/{semester_id}', [DownloadController::class, 'unduh_leger_nilai_bulk_semua']);

### Alur Data

User klik "Unduh Semua Kelas"
  -> Frontend: window.open('/downloads/leger-nilai-bulk/{sekolah_id}/{semester_id}')
  -> Backend: ambil semua rombel -> generate Excel multi-sheet
  -> Browser: download file .xlsx

---

## Fitur 2: Bulk Rapor PDF

### Perubahan UI — Wali Kelas

**File:** `resources/js/pages/walas/cetak-rapor.vue`

Tambahkan section baru "Unduh Bulk Rapor" di bawah tabel siswa:
- Checkbox komponen rapor yang dapat dipilih: Cover, Rapor Akademik, Rapor PTS, Rapor P5, Dokumen Pendukung
- Pilihan format output: ZIP (PDF per-siswa) | PDF Gabungan (1 file)
- Tombol "Unduh Rapor Semua Siswa"

### Perubahan UI — Waka Kurikulum

**File:** `resources/js/pages/monitoring/cetak-rapor.vue`

- Tambahkan opsi "Semua Rombel" di dropdown rombel
- Section "Unduh Bulk Rapor" identik dengan yang ada di wali kelas
- Jika dipilih "Semua Rombel", backend akan iterasi semua rombel

### Backend

**Endpoint baru di** `app/Http/Controllers/CetakController.php`:

POST /cetak/bulk-rapor
Body: { rombongan_belajar_ids: [...] | 'all', semester_id, sekolah_id, komponen: { cover, akademik, pts, p5, pelengkap }, format: 'zip' | 'pdf' }

**Alur untuk Format ZIP:**
1. Ambil semua siswa dari rombel yang dipilih
2. Untuk tiap siswa, generate PDF tiap komponen yang dipilih
3. Gabungkan PDF komponen per siswa jadi satu PDF per siswa (via mPDF)
4. Compress semua file PDF ke dalam satu ZIP
5. Stream ZIP ke browser
Nama file ZIP: "Rapor-{nama_rombel}-{semester}.zip"
Nama file PDF per siswa: "{NISN}-{nama_siswa}.pdf"

**Alur untuk Format PDF Gabungan:**
1. Ambil semua siswa dari rombel yang dipilih
2. Untuk tiap siswa, generate semua komponen yang dipilih
3. AddPage() ke mPDF untuk tiap halaman baru
4. Stream satu PDF besar ke browser
Nama file: "Rapor-Semua-Siswa-{nama_rombel}-{semester}.pdf"

**Solusi Timeout:**

Untuk kelas dengan banyak siswa (>30 siswa atau semua rombel dipilih), gunakan Laravel Queue:
1. User klik "Unduh" -> POST /cetak/bulk-rapor/queue
2. Backend dispatch Job: GenerateBulkRaporJob
3. Frontend polling: GET /cetak/bulk-rapor/status/{job_id} tiap 3 detik
4. UI tampilkan progress bar (% siswa yang selesai diproses)
5. Ketika Job selesai -> simpan file ke storage/app/temp/
6. Frontend tampilkan tombol "Klik untuk Download" -> GET /cetak/bulk-rapor/download/{job_id}
7. File temp dihapus setelah 1 jam (scheduled cleanup)

---

## File yang Akan Dimodifikasi/Dibuat

### [MODIFY] Frontend
- resources/js/pages/monitoring/unduh-legger.vue
- resources/js/pages/monitoring/cetak-rapor.vue
- resources/js/pages/walas/cetak-rapor.vue

### [MODIFY] Backend
- app/Http/Controllers/DownloadController.php
- app/Http/Controllers/CetakController.php
- routes/web.php

### [NEW] Backend
- app/Exports/LeggerNilaiBulkExport.php
- app/Jobs/GenerateBulkRaporJob.php

---

## Threshold Synchronous vs Queue

- <= 30 siswa + 1 rombel: proses synchronous (langsung download)
- > 30 siswa atau multiple rombel: otomatis pakai Queue dengan progress bar

## Library yang Digunakan (Tidak Perlu Tambah Dependency)

- maatwebsite/excel: untuk Excel multi-sheet (Leger)
- mPDF (via PDF facade): untuk generate & merge PDF (Rapor)
- ZipArchive (PHP built-in): untuk ZIP file

## Keamanan

- Validate bahwa rombongan_belajar_id milik sekolah_id user
- File temp diberi nama random (UUID) untuk menghindari akses tidak sah
- File temp auto-delete setelah 1 jam

---

## Success Criteria

1. Waka Kurikulum dapat mengunduh leger nilai semua kelas dalam satu file Excel, tiap kelas = satu sheet bernama sesuai nama rombel
2. Wali Kelas dapat mengunduh rapor semua siswa di kelasnya dalam format ZIP atau PDF gabungan
3. Waka Kurikulum dapat mengunduh rapor satu rombel tertentu atau semua rombel sekaligus
4. User dapat memilih komponen rapor yang ingin diunduh (cover, akademik, PTS, P5, pelengkap)
5. Proses dengan banyak siswa (>30 atau semua rombel) menggunakan Queue dengan progress bar dan tidak timeout
