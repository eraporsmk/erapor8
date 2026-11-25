<?php

namespace App\Exports;

use App\Models\RombonganBelajar;
use App\Services\LegerAkhirService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeggerAkhirBulkExport implements WithMultipleSheets
{
    protected string $sekolahId;
    protected string $semesterId;

    public function __construct(string $sekolahId, string $semesterId)
    {
        $this->sekolahId = $sekolahId;
        $this->semesterId = $semesterId;
    }

    public function sheets(): array
    {
        $sheets = [];
        $service = app(LegerAkhirService::class);

        $rombels = RombonganBelajar::where('sekolah_id', $this->sekolahId)
            ->where('semester_id', $this->semesterId)
            ->where('jenis_rombel', 1)
            ->where('tingkat', 12)
            ->orderBy('nama')
            ->get();

        foreach ($rombels as $rombel) {
            $data = $service->getLeggerAkhir(
                $rombel->rombongan_belajar_id,
                $this->sekolahId,
                $this->semesterId
            );
            $sheets[] = new LeggerAkhirRekapSheet($data, $rombel->nama, false);
        }

        return $sheets;
    }
}
