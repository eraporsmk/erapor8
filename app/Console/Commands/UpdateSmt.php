<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TahunAjaran;
use App\Models\Semester;

class UpdateSmt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-smt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = now()->year;
        $ajaran = $this->generateTahunAjaran($year,$year);
        foreach($ajaran as $a){
            TahunAjaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                ],
                [
                    'nama' => $a['nama'],
                    'periode_aktif' => $a['periode_aktif'],
                    'tanggal_mulai' => $a['tanggal_mulai'],
                    'tanggal_selesai' => $a['tanggal_selesai'],
                    'last_sync' => now(),
                ]
            );
            foreach($a['semester'] as $semester){
                Semester::updateOrCreate(
                    [
                        'semester_id' => $semester['semester_id'],
                    ],
                    [
                        'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                        'nama' => $semester['nama'],
                        'semester' => $semester['semester'],
                        'periode_aktif' => $semester['periode_aktif'],
                        'tanggal_mulai' => $semester['tanggal_mulai'],
                        'tanggal_selesai' => $semester['tanggal_selesai'],
                        'last_sync' => now(),
                    ]
                );
            }
        }
        Semester::where('tahun_ajaran_id', '<>', $year)->update(['periode_aktif' => 0]);
        TahunAjaran::where('tahun_ajaran_id', $year - 3)->update(['periode_aktif' => 0]);
    }
    private function generateTahunAjaran(int $startYear = 2020, ?int $endYear = null): array
    {
        // Jika tahun akhir tidak diisi, otomatis buat sampai 1 tahun ke depan dari tahun berjalan
        $endYear = $endYear ?? ((int) date('Y') + 1);
        
        $today = date('Y-m-d');
        $ajaran = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $nextYear = $year + 1;
            $namaTahun = "{$year}/{$nextYear}";

            // Batas Tanggal Ganjil
            $tglMulaiGanjil   = "{$year}-04-01";
            $tglSelesaiGanjil = "{$year}-12-31";

            // Batas Tanggal Genap
            $tglMulaiGenap   = "{$nextYear}-01-01";
            $tglSelesaiGenap = "{$nextYear}-06-30";

            // Status Aktif Semester
            $isGanjilAktif = ($today >= $tglMulaiGanjil && $today <= $tglSelesaiGanjil) ? 1 : 0;
            $isGenapAktif  = ($today >= $tglMulaiGenap && $today <= $tglSelesaiGenap) ? 1 : 0;

            $semesters = [
                [
                    'semester_id'     => (int) "{$year}1",
                    'nama'            => "{$namaTahun} Ganjil",
                    'semester'        => 1,
                    'tanggal_mulai'   => $tglMulaiGanjil,
                    'tanggal_selesai' => $tglSelesaiGanjil,
                    'periode_aktif'   => $isGanjilAktif,
                ],
                [
                    'semester_id'     => (int) "{$year}2",
                    'nama'            => "{$namaTahun} Genap",
                    'semester'        => 2,
                    'tanggal_mulai'   => $tglMulaiGenap,
                    'tanggal_selesai' => $tglSelesaiGenap,
                    'periode_aktif'   => $isGenapAktif,
                ],
            ];

            // Tahun ajaran aktif jika salah satu semesternya aktif
            $tahunAktif = ($isGanjilAktif || $isGenapAktif) ? 1 : 0;

            $ajaran[] = [
                'tahun_ajaran_id' => $year,
                'nama'            => $namaTahun,
                'periode_aktif'   => $tahunAktif,
                'tanggal_mulai'   => $tglMulaiGanjil,  // 01 April (Tahun Ganjil)
                'tanggal_selesai' => $tglSelesaiGenap,  // 30 Juni (Tahun Genap)
                'semester'        => $semesters,
            ];
        }

        return $ajaran;
    }
}
