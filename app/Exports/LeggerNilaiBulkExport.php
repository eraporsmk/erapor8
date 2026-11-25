<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Models\RombonganBelajar;

class LeggerNilaiBulkExport implements WithMultipleSheets
{
    protected $sekolah_id;
    protected $semester_id;

    public function __construct($sekolah_id, $semester_id)
    {
        $this->sekolah_id  = $sekolah_id;
        $this->semester_id = $semester_id;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Ambil semua rombel kelas reguler (jenis_rombel=1) milik sekolah pada semester ini
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
                'sheet_name'           => $rombel->nama,
            ]);
        }

        return $sheets;
    }
}
