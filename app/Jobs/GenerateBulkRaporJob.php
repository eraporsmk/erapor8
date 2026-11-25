<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\CetakController;
use ZipArchive;

class GenerateBulkRaporJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 menit max

    protected string $job_id;
    protected array  $params;

    public function __construct(string $job_id, array $params)
    {
        $this->job_id  = $job_id;
        $this->params  = $params;
    }

    public function handle(): void
    {
        $params      = $this->params;
        $siswa_list  = $params['siswa_list'];   // array of PesertaDidik data arrays
        $komponen    = $params['komponen'];      // ['cover'=>bool, 'akademik'=>bool, ...]
        $format      = $params['format'];        // 'zip' | 'pdf'
        $sekolah_id  = $params['sekolah_id'];
        $semester_id = $params['semester_id'];
        $nama_file   = $params['nama_file'];
        $total       = count($siswa_list);

        \Log::info("GenerateBulkRaporJob started. job_id={$this->job_id}, total={$total}, first_siswa=" . json_encode($siswa_list[0] ?? []));

        Cache::put("bulk_rapor_{$this->job_id}", [
            'status'   => 'processing',
            'progress' => 0,
            'total'    => $total,
        ], 3600);

        $dir = storage_path("app/temp/bulk_rapor_{$this->job_id}");
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $cetak = new CetakController();

        if ($format === 'zip') {
            $this->generateZip($cetak, $siswa_list, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total);
        } else {
            $this->generatePdfGabungan($cetak, $siswa_list, $komponen, $sekolah_id, $semester_id, $dir, $nama_file, $total);
        }
    }

    protected function generateZip(CetakController $cetak, array $siswa_list, array $komponen, string $sekolah_id, string $semester_id, string $dir, string $nama_file, int $total): void
    {
        $zip_path = "{$dir}/{$nama_file}.zip";
        $zip      = new ZipArchive();
        $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($siswa_list as $index => $siswa) {
            $pdf_content = $cetak->buildSiswaPdfContent(
                $siswa['peserta_didik_id'],
                $siswa['anggota_rombel_id'],
                $komponen,
                $sekolah_id,
                $semester_id
            );

            if ($pdf_content) {
                $filename = ($siswa['nisn'] ?? 'noNISN') . '-' . clean($siswa['nama']) . '.pdf';
                $zip->addFromString($filename, $pdf_content);
            }

            $progress = (int)(($index + 1) / $total * 100);
            Cache::put("bulk_rapor_{$this->job_id}", [
                'status'   => 'processing',
                'progress' => $progress,
                'total'    => $total,
            ], 3600);
        }

        $zip->close();

        Cache::put("bulk_rapor_{$this->job_id}", [
            'status'    => 'done',
            'progress'  => 100,
            'file_path' => $zip_path,
            'filename'  => "{$nama_file}.zip",
        ], 3600);
    }

    protected function generatePdfGabungan(CetakController $cetak, array $siswa_list, array $komponen, string $sekolah_id, string $semester_id, string $dir, string $nama_file, int $total): void
    {
        $config = [
            'format'        => 'A4',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5,
        ];
        $pdf   = \PDF::loadView('cetak.blank', [], [], $config);
        $pdf->getMpdf()->defaultfooterfontsize = 7;
        $pdf->getMpdf()->defaultfooterline     = 0;
        $first    = true;
        $hasData  = false;

        foreach ($siswa_list as $index => $siswa) {
            $halaman = $cetak->buildSiswaHtmlViews(
                $siswa['peserta_didik_id'],
                $siswa['anggota_rombel_id'],
                $komponen,
                $sekolah_id,
                $semester_id
            );

            if (!empty($halaman)) {
                $hasData = true;
                if (!$first) {
                    $pdf->getMpdf()->WriteHTML('<pagebreak />');
                }
                
                $first_write = true;
                foreach ($halaman as $section) {
                    foreach ($section['views'] as $v) {
                        if ($v === '<pagebreak />') {
                            $pdf->getMpdf()->WriteHTML('<pagebreak />');
                        } else {
                            $pdf->getMpdf()->WriteHTML((string) $v);
                            $first_write = false;
                        }
                    }
                }
                $first = false;
            }

            $progress = (int)(($index + 1) / $total * 100);
            Cache::put("bulk_rapor_{$this->job_id}", [
                'status'   => 'processing',
                'progress' => $progress,
                'total'    => $total,
            ], 3600);
        }

        if ($hasData) {
            $pdf_path = "{$dir}/{$nama_file}.pdf";
            $pdf->save($pdf_path);

            Cache::put("bulk_rapor_{$this->job_id}", [
                'status'    => 'done',
                'progress'  => 100,
                'file_path' => $pdf_path,
                'filename'  => "{$nama_file}.pdf",
            ], 3600);
        } else {
            Cache::put("bulk_rapor_{$this->job_id}", [
                'status'  => 'error',
                'message' => 'Tidak ada data siswa yang berhasil diproses.',
            ], 3600);
        }
    }
}
