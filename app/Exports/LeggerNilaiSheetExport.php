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
        // Excel sheet name max 31 chars, hapus karakter invalid: / \ ? * [ ] :
        return substr(preg_replace('/[\/\\\\\?\*\[\]:]/', '', $this->sheet_name), 0, 31);
    }

    public function view(): View
    {
        $data_siswa = PesertaDidik::whereHas('anggota_rombel', function ($query) {
            $query->where('rombongan_belajar_id', $this->rombongan_belajar_id);
        })->with([
            'anggota_rombel' => function ($query) {
                $query->where('rombongan_belajar_id', $this->rombongan_belajar_id);
                $query->with(['absensi']);
            },
            'anggota_pilihan' => function ($query) {
                $query->where('semester_id', $this->semester_id);
            },
        ])->orderByRaw('LOWER(nama) ASC')->get();

        $all_pembelajaran = Pembelajaran::with(['rombongan_belajar'])
            ->where(function ($query) {
                $query->whereHas('rombongan_belajar', function ($query) {
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
            'data_siswa'       => $data_siswa,
            'all_pembelajaran' => $all_pembelajaran,
            'rombongan_belajar' => RombonganBelajar::with(['sekolah'])->find($this->rombongan_belajar_id),
            'merdeka'          => $this->merdeka,
            'tahun_ajaran'     => $semester->nama,
        ]);
    }
}
