# Bulk Download Leger & Rapor Implementation Plan

> **For Antigravity:** REQUIRED SUB-SKILL: Load executing-plans to implement this plan task-by-task.

**Goal:** Tambahkan fitur unduh bulk leger nilai (semua rombel → 1 Excel multi-sheet) untuk Waka Kurikulum, dan unduh bulk rapor (ZIP atau PDF gabungan, komponen dipilih) untuk Wali Kelas & Waka Kurikulum.

**Architecture:**
- Bulk leger: Laravel Excel `WithMultipleSheets` — satu sheet per rombel, nama sheet = nama rombel
- Bulk rapor kecil (≤30 siswa, 1 rombel): synchronous — generate PDF per siswa → ZIP/merge → stream
- Bulk rapor besar (>30 siswa atau semua rombel): Laravel Queue → polling status → download file temp

**Tech Stack:** Laravel 10, Vue 3 + Vuetify, maatwebsite/excel, mPDF (sudah ada), PHP ZipArchive (built-in), Laravel Queue

---

## Konteks Kode

### Struktur route yang ada (routes/web.php)
- Download routes: `Route::group(['prefix' => 'downloads'], ...)`
- Cetak routes: `Route::group(['prefix' => 'cetak'], ...)`

### Export class yang ada
- `app/Exports/LeggerNilaiKurmerExport.php` — implements `FromView, ShouldAutoSize`, method `query(array $data)` dan `view(): View`
- View: `resources/views/laporan/legger_nilai_kurmer.blade.php`

### Cetak route yang tersedia untuk rapor:
- `/cetak/rapor-cover/{peserta_didik_id}/{sekolah_id}/{semester_id}`
- `/cetak/rapor-akademik/{peserta_didik_id}/{sekolah_id}/{semester_id}`
- `/cetak/rapor-semester/{peserta_didik_id}/{sekolah_id}/{semester_id}`
- `/cetak/rapor-nilai-akhir/{anggota_rombel_id}/{sekolah_id}/{semester_id}`
- `/cetak/rapor-p5/{anggota_rombel_id}/{semester_id}`
- `/cetak/rapor-pelengkap/{peserta_didik_id}/{sekolah_id}/{semester_id}`
- `/cetak/rapor-tengah-semester/{peserta_didik_id}/{semester_id}` (dari walas/cetak-rapor.vue — pastikan ada)

### Monitoring controller
- Endpoint: `POST /monitoring/get-data` (MonitoringController)
- Data rombel dengan field: `rombongan_belajar_id`, `nama`

---

## Task 1: Export Class Bulk Leger (LeggerNilaiBulkExport)

**Files:**
- Create: `app/Exports/LeggerNilaiBulkExport.php`

### Step 1: Buat class LeggerNilaiBulkExport

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\RombonganBelajar;
use App\Models\Semester;

class LeggerNilaiBulkExport implements WithMultipleSheets
{
    protected $sekolah_id;
    protected $semester_id;

    public function __construct($sekolah_id, $semester_id)
    {
        $this->sekolah_id = $sekolah_id;
        $this->semester_id = $semester_id;
    }

    public function sheets(): array
    {
        $sheets = [];
        // Ambil semua rombel jenis_rombel=1 (kelas reguler) milik sekolah pada semester aktif
        $rombels = RombonganBelajar::with(['kurikulum'])
            ->where('sekolah_id', $this->sekolah_id)
            ->where('semester_id', $this->semester_id)
            ->where('jenis_rombel', 1)
            ->orderBy('nama')
            ->get();

        foreach ($rombels as $rombel) {
            $merdeka = merdeka($rombel->kurikulum->nama_kurikulum);
            $sheets[] = new LeggerNilaiSheetExport([
                'rombongan_belajar'    => $rombel,
                'rombongan_belajar_id' => $rombel->rombongan_belajar_id,
                'merdeka'              => $merdeka,
                'sekolah_id'           => $this->sekolah_id,
                'semester_id'          => $this->semester_id,
                'sheet_name'           => $rombel->nama, // nama sheet = nama rombel
            ]);
        }

        return $sheets;
    }
}
```

**Simpan ke:** `app/Exports/LeggerNilaiBulkExport.php`

### Step 2: Buat class LeggerNilaiSheetExport (satu sheet per rombel)

```php
<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Models\PesertaDidik;
use App\Models\Pembelajaran;
use App\Models\RombonganBelajar;
use App\Models\Semester;

class LeggerNilaiSheetExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $rombongan_belajar;
    protected $rombongan_belajar_id;
    protected $merdeka;
    protected $sekolah_id;
    protected $semester_id;
    protected $sheet_name;

    public function __construct(array $data)
    {
        $this->rombongan_belajar    = $data['rombongan_belajar'];
        $this->rombongan_belajar_id = $data['rombongan_belajar_id'];
        $this->merdeka              = $data['merdeka'];
        $this->sekolah_id           = $data['sekolah_id'];
        $this->semester_id          = $data['semester_id'];
        $this->sheet_name           = $data['sheet_name'];
    }

    public function title(): string
    {
        // Excel sheet name max 31 chars, hapus karakter invalid
        return substr(preg_replace('/[\/\\\\?\*\[\]:]/', '', $this->sheet_name), 0, 31);
    }

    public function view(): View
    {
        // Logika sama persis dengan LeggerNilaiKurmerExport::view()
        $data_siswa = PesertaDidik::whereHas('anggota_rombel', function($query){
            $query->where('rombongan_belajar_id', $this->rombongan_belajar_id);
        })->with([
            'anggota_rombel' => function($query){
                $query->where('rombongan_belajar_id', $this->rombongan_belajar_id);
                $query->with(['absensi']);
            },
            'anggota_pilihan' => function($query){
                $query->where('semester_id', $this->semester_id);
            }
        ])->orderByRaw('LOWER(nama) ASC')->get();

        $all_pembelajaran = Pembelajaran::with(['rombongan_belajar'])
            ->where(function($query){
                $query->whereHas('rombongan_belajar', function($query){
                    $query->where('sekolah_id', $this->sekolah_id);
                    $query->where('semester_id', $this->semester_id);
                    $query->where('guru_id', $this->rombongan_belajar->guru_id);
                });
                $query->whereNotNull('kelompok_id');
                $query->whereNotNull('no_urut');
                $query->whereNull('induk_pembelajaran_id');
            })
            ->orderBy('kelompok_id', 'asc')
            ->orderBy('no_urut', 'asc')
            ->get();

        $semester = Semester::find($this->semester_id);

        return view('laporan.legger_nilai_kurmer', [
            'data_siswa'        => $data_siswa,
            'all_pembelajaran'  => $all_pembelajaran,
            'rombongan_belajar' => RombonganBelajar::with(['sekolah'])->find($this->rombongan_belajar_id),
            'merdeka'           => $this->merdeka,
            'tahun_ajaran'      => $semester->nama,
        ]);
    }
}
```

**Simpan ke:** `app/Exports/LeggerNilaiSheetExport.php`

### Step 3: Commit

```bash
git add app/Exports/LeggerNilaiBulkExport.php app/Exports/LeggerNilaiSheetExport.php
git commit -m "feat: add LeggerNilaiBulkExport and LeggerNilaiSheetExport classes"
```

---

## Task 2: Backend Route & Controller — Bulk Leger

**Files:**
- Modify: `app/Http/Controllers/DownloadController.php` (tambah method di akhir class sebelum `}`)
- Modify: `routes/web.php` (tambah route di dalam group downloads)

### Step 1: Tambah method ke DownloadController.php

Tambahkan import di bagian atas (setelah use yang ada):
```php
use App\Exports\LeggerNilaiBulkExport;
use Maatwebsite\Excel\Facades\Excel;  // sudah ada, pastikan tidak duplikat
use App\Models\Semester; // sudah ada
```

Tambahkan method baru sebelum penutup `}` class:
```php
public function unduh_leger_nilai_bulk_semua($sekolah_id, $semester_id){
    $semester = Semester::find($semester_id);
    $nama_file = 'Leger-Nilai-Semua-Kelas-' . clean($semester->nama) . '.xlsx';
    return Excel::download(new LeggerNilaiBulkExport($sekolah_id, $semester_id), $nama_file);
}
```

### Step 2: Tambah route ke routes/web.php

Di dalam `Route::group(['prefix' => 'downloads'], ...)` setelah baris route `leger-nilai-kurmer`:
```php
Route::get('/leger-nilai-bulk/{sekolah_id}/{semester_id}', [DownloadController::class, 'unduh_leger_nilai_bulk_semua'])->name('unduh-leger-nilai-bulk-semua');
```

### Step 3: Commit

```bash
git add app/Http/Controllers/DownloadController.php routes/web.php
git commit -m "feat: add bulk leger download route and controller method"
```

---

## Task 3: Frontend — Bulk Leger UI (Waka Kurikulum)

**Files:**
- Modify: `resources/js/pages/monitoring/unduh-legger.vue`

### Step 1: Tambah tombol dan function ke unduh-legger.vue

Di dalam `<script setup>`, tambahkan function baru setelah `unduhLegger()`:
```js
const unduhSemuaLegger = () => {
  const url = `/downloads/leger-nilai-bulk/${form.value.sekolah_id}/${form.value.semester_id}`
  window.open(url, '_blank').focus();
}
```

Di dalam `<template>`, di dalam `<VCardItem>` (setelah VCardTitle), tambahkan tombol:
```html
<template #append>
  <VBtn prepend-icon="tabler-file-type-xls" color="success" @click="unduhSemuaLegger">
    Unduh Semua Kelas
  </VBtn>
</template>
```

### Step 2: Commit

```bash
git add resources/js/pages/monitoring/unduh-legger.vue
git commit -m "feat: add download all classes button to monitoring leger page"
```

---

## Task 4: Backend — Bulk Rapor Controller

**Files:**
- Create: `app/Jobs/GenerateBulkRaporJob.php`
- Modify: `app/Http/Controllers/CetakController.php`
- Modify: `routes/web.php`

### Step 1: Buat GenerateBulkRaporJob.php

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\PesertaDidik;
use App\Models\AnggotaRombel;
use ZipArchive;
use PDF;

class GenerateBulkRaporJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 menit max
    protected $job_id;
    protected $params;

    public function __construct($job_id, $params)
    {
        $this->job_id = $job_id;
        $this->params = $params;
    }

    public function handle()
    {
        $params = $this->params;
        $siswa_ids = $params['siswa_ids'];      // array of ['peserta_didik_id', 'anggota_rombel_id', 'nama', 'nisn']
        $komponen  = $params['komponen'];        // ['cover'=>true, 'akademik'=>true, ...]
        $format    = $params['format'];          // 'zip' | 'pdf'
        $sekolah_id   = $params['sekolah_id'];
        $semester_id  = $params['semester_id'];
        $nama_file    = $params['nama_file'];
        $total = count($siswa_ids);

        // Update progress: mulai
        Cache::put("bulk_rapor_{$this->job_id}", ['status' => 'processing', 'progress' => 0, 'total' => $total], 3600);

        $dir = storage_path("app/temp/bulk_rapor_{$this->job_id}");
        if (!file_exists($dir)) mkdir($dir, 0755, true);

        if ($format === 'zip') {
            $this->generateZip($siswa_ids, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total);
        } else {
            $this->generatePdfGabungan($siswa_ids, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total);
        }
    }

    protected function generateZip($siswa_ids, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total)
    {
        $zip_path = $dir . '/' . $nama_file . '.zip';
        $zip = new ZipArchive();
        $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($siswa_ids as $index => $siswa) {
            $pdf = PDF::loadView('cetak.rapor_cover', $this->getCoverData($siswa, $sekolah_id, $semester_id));
            // Merge komponen yang dipilih ke satu PDF per siswa
            // (implementasi detail tergantung mPDF API)
            $pdf_path = $dir . '/' . $siswa['nisn'] . '-' . clean($siswa['nama']) . '.pdf';
            $pdf->save($pdf_path);
            $zip->addFile($pdf_path, $siswa['nisn'] . '-' . clean($siswa['nama']) . '.pdf');

            $progress = (int)(($index + 1) / $total * 100);
            Cache::put("bulk_rapor_{$this->job_id}", ['status' => 'processing', 'progress' => $progress, 'total' => $total], 3600);
        }

        $zip->close();
        Cache::put("bulk_rapor_{$this->job_id}", [
            'status'    => 'done',
            'progress'  => 100,
            'file_path' => $zip_path,
            'filename'  => $nama_file . '.zip',
        ], 3600);
    }

    protected function generatePdfGabungan($siswa_ids, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total)
    {
        $pdf = null;
        foreach ($siswa_ids as $index => $siswa) {
            if ($pdf === null) {
                $pdf = PDF::loadView('cetak.rapor_cover', $this->getCoverData($siswa, $sekolah_id, $semester_id));
            } else {
                $pdf->getMpdf()->AddPage('P');
                $rapor_page = view('cetak.rapor_cover', $this->getCoverData($siswa, $sekolah_id, $semester_id));
                $pdf->getMpdf()->WriteHTML($rapor_page);
            }

            $progress = (int)(($index + 1) / $total * 100);
            Cache::put("bulk_rapor_{$this->job_id}", ['status' => 'processing', 'progress' => $progress, 'total' => $total], 3600);
        }

        $pdf_path = $dir . '/' . $nama_file . '.pdf';
        $pdf->save($pdf_path);

        Cache::put("bulk_rapor_{$this->job_id}", [
            'status'    => 'done',
            'progress'  => 100,
            'file_path' => $pdf_path,
            'filename'  => $nama_file . '.pdf',
        ], 3600);
    }

    protected function getCoverData($siswa, $sekolah_id, $semester_id) { /* delegasi ke CetakController */ return []; }
}
```

> **Catatan implementasi:** method `generateZip` dan `generatePdfGabungan` perlu menggunakan logika generate PDF yang sama dengan CetakController. Refactor logic ke private methods yang dapat dipanggil dari Job. Untuk setiap komponen rapor (cover, akademik, pts, p5, pelengkap), panggil view mPDF yang sama dengan yang ada di CetakController.

**Simpan ke:** `app/Jobs/GenerateBulkRaporJob.php`

### Step 2: Tambah methods ke CetakController.php

Tambahkan use statements baru (di atas class):
```php
use App\Jobs\GenerateBulkRaporJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
```

Tambahkan methods baru sebelum penutup `}` class:

```php
/**
 * Ambil daftar siswa untuk rombel yang dipilih.
 * Dipakai oleh bulk_rapor (sync) dan bulk_rapor_queue (async).
 */
private function getSiswaForBulk($rombongan_belajar_ids, $sekolah_id, $semester_id)
{
    // Jika 'all', ambil semua rombel sekolah semester ini
    if ($rombongan_belajar_ids === 'all') {
        $rombels = \App\Models\RombonganBelajar::where('sekolah_id', $sekolah_id)
            ->where('semester_id', $semester_id)
            ->where('jenis_rombel', 1)
            ->pluck('rombongan_belajar_id')
            ->toArray();
    } else {
        $rombels = (array) $rombongan_belajar_ids;
    }

    return PesertaDidik::withWhereHas('anggota_rombel', function($query) use ($rombels) {
        $query->whereIn('rombongan_belajar_id', $rombels)
              ->with(['rombongan_belajar']);
    })->orderByRaw('LOWER(nama) ASC')->get();
}

/**
 * Bulk rapor synchronous (untuk ≤30 siswa, 1 rombel).
 * POST /cetak/bulk-rapor
 */
public function bulk_rapor(Request $request)
{
    $data_siswa = $this->getSiswaForBulk(
        $request->rombongan_belajar_ids,
        $request->sekolah_id,
        $request->semester_id
    );

    // Threshold: jika terlalu banyak, alihkan ke queue
    if ($data_siswa->count() > 30 || $request->rombongan_belajar_ids === 'all') {
        return response()->json(['redirect_to_queue' => true]);
    }

    $komponen  = $request->komponen;
    $format    = $request->format; // 'zip' | 'pdf'
    $sekolah_id   = $request->sekolah_id;
    $semester_id  = $request->semester_id;
    $nama_file    = 'Rapor-Bulk-' . clean($request->nama_rombel ?? 'Semua') . '-' . clean($request->periode_aktif ?? '');

    if ($format === 'zip') {
        return $this->generateBulkZip($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file);
    } else {
        return $this->generateBulkPdfGabungan($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file);
    }
}

/**
 * Bulk rapor via Queue (untuk >30 siswa atau semua rombel).
 * POST /cetak/bulk-rapor/queue
 */
public function bulk_rapor_queue(Request $request)
{
    $job_id = Str::uuid()->toString();
    $data_siswa = $this->getSiswaForBulk(
        $request->rombongan_belajar_ids,
        $request->sekolah_id,
        $request->semester_id
    );

    $siswa_ids = $data_siswa->map(fn($s) => [
        'peserta_didik_id' => $s->peserta_didik_id,
        'anggota_rombel_id' => $s->anggota_rombel->first()?->anggota_rombel_id,
        'nama' => $s->nama,
        'nisn' => $s->nisn,
    ])->values()->toArray();

    $params = [
        'siswa_ids'   => $siswa_ids,
        'komponen'    => $request->komponen,
        'format'      => $request->format,
        'sekolah_id'  => $request->sekolah_id,
        'semester_id' => $request->semester_id,
        'nama_file'   => 'Rapor-Bulk-' . clean($request->nama_rombel ?? 'Semua') . '-' . clean($request->periode_aktif ?? ''),
    ];

    Cache::put("bulk_rapor_{$job_id}", ['status' => 'queued', 'progress' => 0, 'total' => count($siswa_ids)], 3600);
    GenerateBulkRaporJob::dispatch($job_id, $params);

    return response()->json(['job_id' => $job_id, 'total' => count($siswa_ids)]);
}

/**
 * Polling status job.
 * GET /cetak/bulk-rapor/status/{job_id}
 */
public function bulk_rapor_status($job_id)
{
    $status = Cache::get("bulk_rapor_{$job_id}", ['status' => 'not_found']);
    // Jangan expose file_path ke frontend
    unset($status['file_path']);
    return response()->json($status);
}

/**
 * Download hasil bulk rapor setelah Job selesai.
 * GET /cetak/bulk-rapor/download/{job_id}
 */
public function bulk_rapor_download($job_id)
{
    $status = Cache::get("bulk_rapor_{$job_id}");
    if (!$status || $status['status'] !== 'done') {
        abort(404, 'File belum siap atau sudah kadaluarsa.');
    }
    return response()->download($status['file_path'], $status['filename'])->deleteFileAfterSend(false);
}

private function generateBulkZip($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file)
{
    $zip_path = storage_path('app/temp/' . Str::uuid() . '.zip');
    if (!file_exists(dirname($zip_path))) mkdir(dirname($zip_path), 0755, true);

    $zip = new ZipArchive();
    $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($data_siswa as $siswa) {
        $pdf = $this->buildSiswaPdf($siswa, $komponen, $sekolah_id, $semester_id);
        $filename = ($siswa->nisn ?? 'noNISN') . '-' . clean($siswa->nama) . '.pdf';
        $zip->addFromString($filename, $pdf->output());
    }

    $zip->close();
    return response()->download($zip_path, $nama_file . '.zip')->deleteFileAfterSend(true);
}

private function generateBulkPdfGabungan($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file)
{
    $first = true;
    $pdf = null;
    foreach ($data_siswa as $siswa) {
        if ($first) {
            $pdf = $this->buildSiswaPdf($siswa, $komponen, $sekolah_id, $semester_id);
            $first = false;
        } else {
            // Append halaman ke PDF yang sama via mPDF
            $siswa_pdf = $this->buildSiswaPdf($siswa, $komponen, $sekolah_id, $semester_id);
            $pdf->getMpdf()->importpages($siswa_pdf->getMpdf(), 1, $siswa_pdf->getMpdf()->page);
        }
    }
    return $pdf->stream($nama_file . '.pdf');
}

/**
 * Build PDF untuk satu siswa berdasarkan komponen yang dipilih.
 * Menggabungkan cover + rapor akademik + pts + p5 + pelengkap jadi 1 PDF.
 */
private function buildSiswaPdf($siswa, $komponen, $sekolah_id, $semester_id)
{
    // Ambil data yang diperlukan untuk view
    $anggota_rombel_id = $siswa->anggota_rombel->first()?->anggota_rombel_id;
    $peserta_didik_id  = $siswa->peserta_didik_id;

    // Mulai dari view pertama yang diaktifkan
    // Gunakan mPDF addPage untuk menggabungkan halaman
    // Implementasi detail: ikuti pola yang sama dengan CetakController::rapor_cover(), dll.
    // PENTING: Load data siswa lengkap sesuai yang dibutuhkan tiap view (ikuti existing controller methods)

    // Return: objek PDF (mPDF wrapper dari Barryvdh/laravel-dompdf atau mPDF)
    // ...implementasi detail mengikuti logika CetakController yang sudah ada
}
```

> **Catatan penting untuk implementor:** Method `buildSiswaPdf` harus mengadaptasi logika dari method-method seperti `rapor_cover()`, `rapor_semester()`, `rapor_akademik()`, dll. di CetakController. Load data siswa yang sama, gunakan view Blade yang sama, gabungkan dengan `mPDF->AddPage()`.

### Step 3: Tambah routes ke routes/web.php

Di dalam `Route::group(['prefix' => 'cetak'], ...)`:
```php
// Bulk rapor routes
Route::post('/bulk-rapor', [CetakController::class, 'bulk_rapor'])->name('bulk-rapor');
Route::post('/bulk-rapor/queue', [CetakController::class, 'bulk_rapor_queue'])->name('bulk-rapor-queue');
Route::get('/bulk-rapor/status/{job_id}', [CetakController::class, 'bulk_rapor_status'])->name('bulk-rapor-status');
Route::get('/bulk-rapor/download/{job_id}', [CetakController::class, 'bulk_rapor_download'])->name('bulk-rapor-download');
```

### Step 4: Commit

```bash
git add app/Jobs/GenerateBulkRaporJob.php app/Http/Controllers/CetakController.php routes/web.php
git commit -m "feat: add bulk rapor controller methods and queue job"
```

---

## Task 5: Frontend — Bulk Rapor UI (Wali Kelas)

**Files:**
- Modify: `resources/js/pages/walas/cetak-rapor.vue`

### Step 1: Tambah state dan logic ke walas/cetak-rapor.vue

Di dalam `<script setup>`, tambahkan setelah `arrayData`:

```js
// State untuk bulk rapor
const bulkForm = ref({
  komponen: {
    cover: true,
    akademik: true,
    pts: false,
    p5: false,
    pelengkap: true,
  },
  format: 'zip', // 'zip' | 'pdf'
})
const bulkStatus = ref({
  loading: false,
  job_id: null,
  progress: 0,
  total: 0,
  status: null, // null | 'queued' | 'processing' | 'done' | 'error'
  download_url: null,
})
let pollingInterval = null

const unduhBulkRapor = async () => {
  if (!arrayData.value.siswa.length) return
  bulkStatus.value.loading = true
  bulkStatus.value.status = null

  const payload = {
    rombongan_belajar_ids: [defaultForm.value.rombongan_belajar_id],
    sekolah_id: defaultForm.value.sekolah_id,
    semester_id: defaultForm.value.semester_id,
    periode_aktif: defaultForm.value.periode_aktif,
    komponen: bulkForm.value.komponen,
    format: bulkForm.value.format,
  }

  // Coba sync dulu; jika server bilang redirect_to_queue, pakai queue
  try {
    const response = await $api('/cetak/bulk-rapor', {
      method: 'POST',
      body: payload,
      responseType: 'blob',
    })
    if (response.redirect_to_queue) {
      await startQueueJob(payload)
    } else {
      // Download blob langsung
      const url = URL.createObjectURL(response)
      const a = document.createElement('a')
      a.href = url
      a.download = 'Rapor-Bulk.zip'
      a.click()
      URL.revokeObjectURL(url)
      bulkStatus.value.loading = false
    }
  } catch (e) {
    console.error(e)
    bulkStatus.value.loading = false
    bulkStatus.value.status = 'error'
  }
}

const startQueueJob = async (payload) => {
  const response = await $api('/cetak/bulk-rapor/queue', {
    method: 'POST',
    body: payload,
  })
  bulkStatus.value.job_id = response.job_id
  bulkStatus.value.total = response.total
  bulkStatus.value.status = 'queued'

  // Polling tiap 3 detik
  pollingInterval = setInterval(async () => {
    const statusResp = await $api(`/cetak/bulk-rapor/status/${bulkStatus.value.job_id}`)
    bulkStatus.value.progress = statusResp.progress ?? 0
    bulkStatus.value.status = statusResp.status

    if (statusResp.status === 'done') {
      clearInterval(pollingInterval)
      bulkStatus.value.download_url = `/cetak/bulk-rapor/download/${bulkStatus.value.job_id}`
      bulkStatus.value.loading = false
    }
    if (statusResp.status === 'error') {
      clearInterval(pollingInterval)
      bulkStatus.value.loading = false
    }
  }, 3000)
}

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
})
```

### Step 2: Tambah template bulk rapor

Di dalam `<template>`, di bawah `</VCard>` yang sudah ada, tambahkan card baru:

```html
<!-- Bulk Rapor Card -->
<VCard class="mt-6" v-if="arrayData.siswa.length">
  <VCardItem class="pb-4">
    <VCardTitle>Unduh Rapor Semua Siswa</VCardTitle>
  </VCardItem>
  <VDivider />
  <VCardText>
    <VRow>
      <!-- Pilihan Komponen -->
      <VCol cols="12">
        <p class="text-body-2 font-weight-medium mb-2">Pilih Komponen Rapor:</p>
        <VRow>
          <VCol cols="6" md="4">
            <VCheckbox v-model="bulkForm.komponen.cover" label="Cover (Halaman Depan)" />
          </VCol>
          <VCol cols="6" md="4">
            <VCheckbox v-model="bulkForm.komponen.akademik" label="Rapor Akademik" />
          </VCol>
          <VCol cols="6" md="4" v-if="defaultForm.rapor_pts">
            <VCheckbox v-model="bulkForm.komponen.pts" label="Rapor Tengah Semester" />
          </VCol>
          <VCol cols="6" md="4" v-if="defaultForm.merdeka && !defaultForm.is_new_ppa">
            <VCheckbox v-model="bulkForm.komponen.p5" label="Rapor P5" />
          </VCol>
          <VCol cols="6" md="4">
            <VCheckbox v-model="bulkForm.komponen.pelengkap" label="Dokumen Pendukung" />
          </VCol>
        </VRow>
      </VCol>
      <!-- Pilihan Format -->
      <VCol cols="12">
        <p class="text-body-2 font-weight-medium mb-2">Format Output:</p>
        <VRadioGroup v-model="bulkForm.format" inline>
          <VRadio label="ZIP (PDF per-siswa)" value="zip" />
          <VRadio label="PDF Gabungan (1 file)" value="pdf" />
        </VRadioGroup>
      </VCol>
      <!-- Tombol Download -->
      <VCol cols="12">
        <VBtn
          prepend-icon="tabler-download"
          color="primary"
          :loading="bulkStatus.loading && bulkStatus.status !== 'done'"
          @click="unduhBulkRapor"
        >
          Unduh Rapor Semua Siswa
        </VBtn>
      </VCol>
      <!-- Progress Bar (muncul saat queue processing) -->
      <VCol cols="12" v-if="bulkStatus.status === 'processing' || bulkStatus.status === 'queued'">
        <p class="text-body-2 mb-1">Memproses {{ bulkStatus.progress }}% dari {{ bulkStatus.total }} siswa...</p>
        <VProgressLinear :model-value="bulkStatus.progress" color="primary" height="8" rounded />
      </VCol>
      <!-- Tombol download saat selesai -->
      <VCol cols="12" v-if="bulkStatus.status === 'done' && bulkStatus.download_url">
        <VAlert type="success" variant="tonal">
          File rapor siap!
          <VBtn :href="bulkStatus.download_url" color="success" class="ml-4" prepend-icon="tabler-download">
            Klik untuk Download
          </VBtn>
        </VAlert>
      </VCol>
      <VCol cols="12" v-if="bulkStatus.status === 'error'">
        <VAlert type="error" variant="tonal">Terjadi kesalahan saat memproses rapor. Coba lagi.</VAlert>
      </VCol>
    </VRow>
  </VCardText>
</VCard>
```

### Step 3: Commit

```bash
git add resources/js/pages/walas/cetak-rapor.vue
git commit -m "feat: add bulk rapor UI to walas cetak-rapor page"
```

---

## Task 6: Frontend — Bulk Rapor UI (Waka Kurikulum)

**Files:**
- Modify: `resources/js/pages/monitoring/cetak-rapor.vue`

### Step 1: Tambah opsi "Semua Rombel" di dropdown

Di `<script setup>`, ubah `changeRombel` dan tambahkan logic:

```js
// Tambahkan computed/ref untuk opsi "Semua Rombel"
const rombelOptions = computed(() => {
  return [
    { rombongan_belajar_id: 'all', nama: '== Semua Rombel ==' },
    ...arrayData.value.rombel
  ]
})

// Ubah payload changeRombel agar 'all' valid
const changeRombel = async (val) => {
  arrayData.value.siswa = []
  if (val && val !== 'all') {
    loading.value.body = true
    await getData({ data: 'siswa' }).then(() => {
      loading.value.body = false
    })
  }
  // Jika 'all', tidak perlu load preview siswa (langsung download saat klik)
}
```

### Step 2: Tambah state dan logic bulk (sama seperti Task 5)

Copy-paste seluruh logic `bulkForm`, `bulkStatus`, `unduhBulkRapor`, `startQueueJob`, `onUnmounted` dari Task 5, dengan penyesuaian:

```js
// Penyesuaian: rombongan_belajar_ids bisa 'all' atau array
const unduhBulkRapor = async () => {
  // ...
  const payload = {
    rombongan_belajar_ids: form.value.rombongan_belajar_id === 'all' ? 'all' : [form.value.rombongan_belajar_id],
    nama_rombel: form.value.rombongan_belajar_id === 'all' ? 'Semua-Kelas' : (arrayData.value.rombel.find(r => r.rombongan_belajar_id === form.value.rombongan_belajar_id)?.nama ?? ''),
    sekolah_id: form.value.sekolah_id,
    semester_id: form.value.semester_id,
    periode_aktif: $semester.nama,
    komponen: bulkForm.value.komponen,
    format: bulkForm.value.format,
  }
  // ...sisa sama dengan Task 5
}
```

### Step 3: Update template

Di template dropdown rombel, gunakan `rombelOptions` computed:
```html
<AppSelect v-model="form.rombongan_belajar_id" ...
  :items="rombelOptions"
  item-value="rombongan_belajar_id"
  item-title="nama" ... />
```

Tambahkan card Bulk Rapor yang sama seperti Task 5 di bawah tabel siswa.

> **Catatan:** Card bulk rapor untuk monitoring harus tetap tampil bahkan ketika `form.rombongan_belajar_id === 'all'` (tidak ada preview tabel siswa). Tambahkan kondisi: `v-if="form.rombongan_belajar_id"` alih-alih `v-if="arrayData.siswa.length"`.

### Step 4: Commit

```bash
git add resources/js/pages/monitoring/cetak-rapor.vue
git commit -m "feat: add bulk rapor UI to monitoring cetak-rapor page"
```

---

## Task 7: Cleanup & Scheduled Delete Temp Files

**Files:**
- Modify: `app/Console/Kernel.php` (atau `routes/console.php` jika Laravel 10+)

### Step 1: Tambah scheduled command untuk hapus temp files

Di `app/Console/Kernel.php` dalam method `schedule()`:
```php
// Hapus file bulk rapor temp yang lebih dari 2 jam
$schedule->call(function () {
    $dir = storage_path('app/temp');
    if (!is_dir($dir)) return;
    foreach (glob($dir . '/bulk_rapor_*') as $path) {
        if (is_dir($path)) {
            $files = glob($path . '/*');
            foreach ($files as $file) {
                if (filemtime($file) < time() - 7200) unlink($file);
            }
            if (count(glob($path . '/*')) === 0) rmdir($path);
        }
    }
})->everyTwoHours()->name('cleanup-bulk-rapor-temp');
```

### Step 2: Commit

```bash
git add app/Console/Kernel.php
git commit -m "feat: add scheduled cleanup for bulk rapor temp files"
```

---

## Verifikasi Manual

1. **Bulk Leger (Waka Kurikulum):**
   - Login sebagai Waka Kurikulum → Monitoring → Unduh Leger
   - Klik "Unduh Semua Kelas" → file Excel harus terdownload
   - Buka file → pastikan ada sheet per rombel, nama sheet = nama kelas

2. **Bulk Rapor (Wali Kelas) — Synchronous:**
   - Login sebagai Wali Kelas → Cetak Rapor
   - Pilih komponen rapor yang diinginkan
   - Pilih format ZIP → klik "Unduh Rapor Semua Siswa"
   - Verifikasi file ZIP berisi PDF per siswa
   - Ulangi dengan format PDF Gabungan

3. **Bulk Rapor (Waka Kurikulum) — Queue:**
   - Login sebagai Waka Kurikulum → Monitoring → Cetak Rapor
   - Pilih "Semua Rombel" di dropdown
   - Klik "Unduh Rapor Semua Siswa"
   - Verifikasi progress bar muncul dan update
   - Setelah selesai, tombol "Klik untuk Download" muncul
   - Verifikasi file dapat didownload
