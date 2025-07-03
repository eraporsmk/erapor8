<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MataPelajaran;
use App\Models\Ekstrakurikuler;
use App\Models\Dudi;
use App\Models\PesertaDidik;

class ReferensiController extends Controller
{
    public function index(){
        $data = MataPelajaran::whereHas('pembelajaran', function($query){
            $query->where('sekolah_id', request()->sekolah_id);
            $query->where('semester_id', request()->semester_id);
        })->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->orWhere('mata_pelajaran_id', 'ILIKE', '%' . request()->q . '%');
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function ekstrakurikuler(){
        $data = Ekstrakurikuler::where(function($query){
            $query->whereHas('rombongan_belajar', function($query){
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
            });
        })->with([
            'guru' => function($query){
                $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang');
            },
            'rombongan_belajar' => function($query){
                $query->select('rombongan_belajar_id');
                $query->withCount('anggota_rombel');
            }
        ])
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama_ekskul', 'ILIKE', '%' . request()->q . '%');
            $query->orWhere('nama_ketua', 'ILIKE', '%' . request()->q . '%');
            $query->orWhereHas('guru', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function dudi(){
        $data = Dudi::where(function($query){
            $query->where('sekolah_id', request()->sekolah_id);
        })->withCount(['akt_pd'])->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->orWhere('nama_bidang_usaha', 'ILIKE', '%' . request()->q . '%');
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function detil_dudi(){
        $data = Dudi::with(['mou' => function($query){
            $query->with(['akt_pd' => function($query){
                $query->with([
                    'bimbing_pd' => function($query){
                        $query->with(['guru' => function($query){
                            $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo', 'email');
                        }]);
                        $query->orderBy('urutan_pembimbing');
                    }
                ]);
                $query->withCount(['anggota_akt_pd' => function($query){
                    $query->whereHas('anggota_rombel', function($query){
                        $query->where('semester_id', request()->semester_id);
                    });
                }]);
            }]);
        }])->find(request()->dudi_id);
        return response()->json($data);
    }
    public function anggota_prakerin(){
        $data = PesertaDidik::whereHas('anggota_akt_pd', function($query){
            $query->where('akt_pd_id', request()->akt_pd_id);
            $query->whereHas('anggota_rombel', function($query){
                $query->where('semester_id', request()->semester_id);
            });
        })->with([
            'anggota_akt_pd' => function($query){
                $query->where('akt_pd_id', request()->akt_pd_id);
                $query->whereHas('anggota_rombel', function($query){
                    $query->where('semester_id', request()->semester_id);
                });
            },
            'agama',
            'kelas' => function($query){
                $query->where('jenis_rombel', 1);
                $query->where('rombongan_belajar.semester_id', request()->semester_id);
            }
        ])->get();
        return response()->json($data);
    }
}
