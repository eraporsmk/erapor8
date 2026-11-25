<?php

namespace App\Exports;

use App\Services\LegerAkhirService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeggerAkhirExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(string $rombonganBelajarId, string $sekolahId, string $semesterId)
    {
        $this->data = app(LegerAkhirService::class)->getLeggerAkhir(
            $rombonganBelajarId,
            $sekolahId,
            $semesterId
        );
    }

    public function sheets(): array
    {
        return [
            new LeggerAkhirRekapSheet($this->data, 'Rekap Nilai Akhir', true),
            new LeggerAkhirRincianSheet($this->data, 'Rincian Per Semester'),
        ];
    }
}
