<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\RencanaUkk;
use App\Models\NilaiUkk;
use App\Models\PesertaDidik;

class UkkController extends Controller
{
    public function index(){
        if(request()->isMethod('POST')){
            $data = [];
            if(request()->data == 'rencana'){
                $rencana_ukk = [];
                $get = RencanaUkk::where(function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                    $query->where('internal', request()->guru_id);
                })->withWhereHas('paket_ukk')->get();
                foreach($get as $val){
                    $rencana_ukk[] = [
                        'rencana_ukk_id' => $val->rencana_ukk_id,
                        'nama' => $val->paket_ukk->nama_paket_id,
                    ];
                }
                $data = [
                    'rencana_ukk' => $rencana_ukk,
                    'data_siswa' => (request()->rencana_ukk_id) ? PesertaDidik::withWhereHas('nilai_ukk', function($query){
                        $query->where('rencana_ukk_id', request()->rencana_ukk_id);
                    })->orderByRaw('LOWER(nama) ASC')->get() : [],
                ];
            }
            if(request()->data == 'siswa'){
                $data = [
                    'data_siswa' => PesertaDidik::withWhereHas('nilai_ukk', function($query){
                        $query->where('rencana_ukk_id', request()->rencana_ukk_id);
                    })->orderByRaw('LOWER(nama) ASC')->get(),
                ];
            }
            return response()->json($data);
        } else {
            $data = RencanaUkk::withWhereHas('paket_ukk')->where(function($query){
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
            })->with([
                'guru_internal' => function($query){
                    $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo', 'email');
                },
                'guru_eksternal' => function($query){
                    $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo', 'email');
                },
            ])->withCount('pd')
            ->orderBy(request()->sortby, request()->sortbydesc)
            ->when(request()->q, function($query) {
                $query->whereHas('paket_ukk', function($query){
                    $query->where('nama_paket_id', 'ILIKE', '%' . request()->q . '%');
                    $query->orWhere('nama_paket_en', 'ILIKE', '%' . request()->q . '%');
                });
            })->paginate(request()->per_page);
            return response()->json(['status' => 'success', 'data' => $data]);
        }
    }
    public function save(){
        $insert = 0;
        $text = 'Unknow';
        if(request()->data == 'rencana'){
            $text = 'Perencanaan Penilaian UKK';
            request()->validate(
                [
                    'tingkat' => 'required',
                    'rombongan_belajar_id' => 'required',
                    'penguji_internal' => 'required',
                    'penguji_eksternal' => 'required',
                    'paket_ukk_id' => 'required',
                    'tanggal' => 'required',
                ],
                [
                    'tingkat.required' => 'Tingkat Kelas tidak boleh kosong!',
                    'rombongan_belajar_id.required' => 'Rombongan Belajar tidak boleh kosong!',
                    'penguji_internal.required' => 'Penguji Internal tidak boleh kosong!',
                    'penguji_eksternal.required' => 'Penguji Eksternal tidak boleh kosong!',
                    'paket_ukk_id.required' => 'Paket Kompetensi Kelas tidak boleh kosong!',
                    'tanggal.required' => 'Tanggal Sertifikat tidak boleh kosong!',
                ]
            );
            $rencana_ukk = RencanaUkk::create(
                [
                    'semester_id' => request()->semester_id,
                    'paket_ukk_id' => request()->paket_ukk_id,
                    'sekolah_id' => request()->sekolah_id,
                    'internal' => request()->penguji_internal,
                    'eksternal' => request()->penguji_eksternal,
                    'tanggal_sertifikat' => request()->tanggal,
                    'last_sync' => now(), 
                ],
            );
            $deleted = [];
            $insert = 0;
            foreach(request()->selected as $uuid){
                $insert++;
                $segments = Str::of($uuid)->split('/[\s#]+/');
                $peserta_didik_id = $segments->first();
                $anggota_rombel_id = $segments->last();
                $deleted[] = $anggota_rombel_id;
                if($anggota_rombel_id){
                    NilaiUkk::firstOrCreate(
                        [
                            'rencana_ukk_id'		=> $rencana_ukk->rencana_ukk_id,
                            'anggota_rombel_id'		=> $anggota_rombel_id,
                            'peserta_didik_id'		=> $peserta_didik_id,
                        ],
                        [
                            'sekolah_id' 			=> request()->sekolah_id,
                            'nilai'					=> 0,
                            'last_sync' 			=> now(), 
                        ]
                    );
                }
            }
            if(array_filter($deleted)){
                NilaiUkk::where('rencana_ukk_id', $rencana_ukk->rencana_ukk_id)->whereNotIn('anggota_rombel_id', array_filter($deleted))->delete();
            }
        }
        if(request()->data == 'nilai'){
            $text = 'Nilai UKK';
            foreach(request()->nilai as $uuid => $nilai_ukk){
                $insert++;
                $segments = Str::of($uuid)->split('/[\s#]+/');
                NilaiUkk::updateOrCreate(
                    [
                        'sekolah_id' => request()->sekolah_id,
                        'rencana_ukk_id' => request()->rencana_ukk_id,
                        'anggota_rombel_id' => $segments->last(),
                        'peserta_didik_id' => $segments->first(),
                    ],
                    [
                        'nilai' => $nilai_ukk,
                        'last_sync' => now(),
                    ]
                );
            }
        }
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil disimpan',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal disimpan. Silahkan coba beberapa saat lagi!',
                'request' => request()->all(),
            ];
        }
        return response()->json($data);
    }
    public function hapus(){
        $insert = 0;
        $text = 'Unknow';
        if(request()->data == 'rencana'){
            $text = 'Perencanaan Penilaian UKK';
            $insert = RencanaUkk::where('rencana_ukk_id', request()->rencana_ukk_id)->delete();
        }
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil dihapus',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal dihapus. Silahkan coba beberapa saat lagi!',
                'request' => request()->all(),
            ];
        }
        return response()->json($data);
    }
    public function show(){
        $data = [
            'rencana' => RencanaUkk::with(['paket_ukk', 'guru_internal' => function($query){
                $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo', 'email');
            }, 'guru_eksternal' => function($query){
                $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo', 'email');
            }])->find(request()->rencana_ukk_id),
            'data_siswa' => PesertaDidik::withWhereHas('nilai_ukk', function($query){
                $query->where('rencana_ukk_id', request()->rencana_ukk_id);
            })->orderByRaw('LOWER(nama) ASC')->get(),
        ];
        return response()->json($data);
    }
}
