<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeggerAkhirRincianSheet implements FromView, ShouldAutoSize, WithTitle
{
    protected array $data;
    protected string $sheetName;

    public function __construct(array $data, string $sheetName = 'Rincian Per Semester')
    {
        $this->data = $data;
        $this->sheetName = $sheetName;
    }

    public function title(): string
    {
        return substr(preg_replace('/[\/\\\\\?\*\[\]:]/', '', $this->sheetName), 0, 31);
    }

    public function view(): View
    {
        return view('laporan.legger_akhir_rincian', [
            'rombel' => $this->data['rombel'],
            'tahun_ajaran' => $this->data['tahun_ajaran'],
            'semesters' => $this->data['semesters'],
            'max_semester_count' => $this->data['max_semester_count'],
            'mapel_list' => $this->data['mapel_list'],
            'siswa_list' => $this->data['siswa_list'],
        ]);
    }
}
