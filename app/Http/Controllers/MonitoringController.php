<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Pembelajaran;
use App\Models\AnggotaRombel;
use App\Models\RombonganBelajar;
use App\Models\RencanaUkk;
use App\Models\PraktikKerjaLapangan;
use App\Models\PesertaDidik;

class MonitoringController extends Controller
{
    public function index(){
        $function = str_replace('-', '_', request()->data);
        $data = $this->{$function}();
        return response()->json($data);
    }
    private function getRombel(){
        return RombonganBelajar::where(function($query){
            $query->whereIn('jenis_rombel', [1, 16]);
            $query->where('sekolah_id', request()->sekolah_id);
            $query->where('semester_id', request()->semester_id);
        })->orderBy('tingkat')->orderBy('nama')->get();
    }
    private function anggota_dinilai($pembelajaran_id, $rombongan_belajar_id){
        $data = AnggotaRombel::whereHas('nilai_akhir', function($query) use ($pembelajaran_id, $rombongan_belajar_id){
            $query->where('rombongan_belajar_id', $rombongan_belajar_id);
            $query->where('pembelajaran_id', $pembelajaran_id);
        })->count();
        return $data;
    }
    private function nilai_akademik(){
        $collection = Pembelajaran::where(function($query){
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
        })
        ->with(['rombongan_belajar'])
        ->withCount([
                'anggota_rombel',
        ])
        ->when(request()->rombongan_belajar_id, function($query){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
        })
        ->when(request()->q, function($query) {
            $query->where('nama_mata_pelajaran', 'ILIKE', '%' . request()->q . '%');
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->orWhereHas('rombongan_belajar', function($query){
                $query->whereIn('jenis_rombel', [1, 16]);
                $query->where('semester_id', request()->semester_id);
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            //$query->whereNull('induk_pembelajaran_id');
            $query->orWhereHas('guru', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            //$query->whereNull('induk_pembelajaran_id');
            $query->orWhereHas('pengajar', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            //$query->whereNull('induk_pembelajaran_id');
        })
        ->paginate(request()->per_page);
        $result = [];
        foreach($collection->sortBy('rombongan_belajar.tingkat')->sortBy('rombongan_belajar.nama') as $item){
            $result[] = [
                'pembelajaran_id' => $item->pembelajaran_id,
                'induk_pembelajaran_id' => $item->induk_pembelajaran_id,
                'rombel' => $item->rombongan_belajar->nama,
                'nama_mata_pelajaran' => $item->nama_mata_pelajaran,
                'guru' => $item->guru_pengajar_id ? $item->pengajar->nama_lengkap : $item->guru->nama_lengkap,
                'pd_count' => $item->anggota_rombel_count,
                'pd_dinilai' => $this->anggota_dinilai($item->pembelajaran_id, $item->rombongan_belajar_id),
                'kkm' => $item->kkm,
                'kelompok_id' => $item->kelompok_id,
                'semester_id' => $item->semester_id,
                'rombongan_belajar_id' => $item->rombongan_belajar_id,
            ];
        }
        $data = [
            'current_page' => request()->page,
            'from' => $collection->firstItem(),
            'data' => $result,
            'per_page' => request()->per_page,
            'to' => $collection->firstItem() + $collection->count() - 1,
            'total' => $collection->total(),
            'rombel' => $this->getRombel(),
        ];
        return $data;
    }
    private function nilai_projek(){
        $data = RombonganBelajar::where(function($query){
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
        })
        ->withWhereHas('projek', function($query){
            $query->with([
                'guru' => function($query){
                    $query->select('guru_id', 'nama', 'email', 'gelar_depan', 'gelar_belakang', 'photo');
                },
                'rencana_projek_count' => function($query){
                    $query->withCount(['aspek_budaya_kerja']);
                },
            ]);
            $query->withCount([
                'tema',
            ]);
        })
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->orderBy('nama', request()->sortbydesc)
        ->when(request()->q, function($query){
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
        })->paginate(request()->per_page);
        return $data;
    }
    private function nilai_ekskul(){
      $data = RombonganBelajar::where(function($query){
         $query->where('semester_id', request()->semester_id);
         $query->where('sekolah_id', request()->sekolah_id);
      })
      ->with(['wali_kelas' => function($query){
         $query->select('guru_id', 'nama', 'email', 'gelar_depan', 'gelar_belakang', 'photo');
      }])
      ->withWhereHas('kelas_ekskul')
      ->withCount([
         'anggota_rombel',
         'anggota_rombel as dinilai' => function($query){
            $query->has('nilai_ekstrakurikuler');
         }
      ])
      ->orderBy(request()->sortby, request()->sortbydesc)
      ->orderBy('nama', request()->sortbydesc)
      ->when(request()->q, function($query){
         $query->where('nama', 'ILIKE', '%' . request()->q . '%');
      })->paginate(request()->per_page);
      return $data;
    }
    private function nilai_ukk(){
        $data = RencanaUkk::where(function($query){
            $query->where('sekolah_id', request()->sekolah_id);
            $query->where('semester_id', request()->semester_id);
        })->withWhereHas('paket_ukk', function($query){
            $query->with(['jurusan' => function($query){
                $query->select('jurusan_id', 'nama_jurusan');
            }]);
        })->with([
                'guru_internal' => function($query){
                    $query->select('guru_id', 'nama', 'email', 'gelar_depan', 'gelar_belakang', 'photo');
                },
                'guru_eksternal' => function($query){
                    $query->select('guru_id', 'nama', 'email', 'gelar_depan', 'gelar_belakang', 'photo');
                },
        ])->withCount('pd')
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
                $query->whereHas('paket_ukk', function($query){
                $query->where('nama_paket_id', 'ILIKE', '%' . request()->q . '%');
                $query->orWhere('nama_paket_en', 'ILIKE', '%' . request()->q . '%');
                });
        })->paginate(request()->per_page);
        return $data;
    }
    private function nilai_pkl(){
        $data = PraktikKerjaLapangan::where(function($query){
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
        })->with([
            'rombongan_belajar',
            'akt_pd.dudi'
        ])->withCount('pd_pkl')->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query){
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->orWhereHas('wali_kelas', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->orWhereHas('jurusan_sp', function($query){
                $query->where('nama_jurusan_sp', 'ILIKE', '%' . request()->q . '%');
            });
            $query->orWhereHas('kurikulum', function($query){
                $query->where('nama_kurikulum', 'ILIKE', '%' . request()->q . '%');
            });
        })->paginate(request()->per_page);
        return $data;
    }
    public function get_data(){
        $getData = 'get_'.request()->data;
        $function = str_replace('-', '_', $getData);
        $data = $this->{$function}();
        return response()->json($data);
    }
    private function get_rombel(){
        $data = RombonganBelajar::where(function($query){
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
            $query->where('tingkat', request()->tingkat);
            $query->where('jenis_rombel', 1);
        })->orderBy('nama')->get();
        return $data;
    }
    private function get_siswa(){
        $data = PesertaDidik::withWhereHas('anggota_rombel', function($query){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
        })->orderByRaw('LOWER(nama) ASC')->get();
        //if(request()->aksi == 'cetak-rapor'){
        $rombel = RombonganBelajar::find(request()->rombongan_belajar_id);
        $merdeka = Str::of($rombel->kurikulum->nama_kurikulum)->contains('Merdeka');
        //}
        return [
            'data_siswa' => $data,
            'merdeka' => $merdeka,
            'rapor_pts' => config('erapor.rapor_pts'),
            'is_ppa' => ($rombel) ? is_ppa($rombel->semester_id) : false,
            'is_new_ppa' => ($rombel) ? is_new_ppa($rombel->semester_id) : false,
        ];
    }
    private function get_legger(){
        $rombel = RombonganBelajar::find(request()->rombongan_belajar_id);
        $data_siswa = PesertaDidik::withWhereHas('anggota_rombel', function($query){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            $query->with(['absensi']);
        })->with(['anggota_pilihan' => function($query) use ($rombel){
            $query->where('semester_id', request()->semester_id);
            $query->whereHas('rombongan_belajar', function($query) use ($rombel){
                $query->where('jurusan_id', $rombel->jurusan_id);
            });
        }])->orderByRaw('LOWER(nama) ASC')->get();
        $pembelajaran = Pembelajaran::withWhereHas('rombongan_belajar', function($query) use ($rombel){
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
            $query->where('guru_id', $rombel->guru_id);
        })->with([
            'all_nilai_akhir_pengetahuan',
            'all_nilai_akhir_keterampilan',
            'all_nilai_akhir_pk',
            'all_nilai_akhir_kurmer',
        ])->where(function($query){
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->whereNull('induk_pembelajaran_id');
        })->orderBy('kelompok_id', 'asc')->orderBy('no_urut', 'asc')->get();
        $data = [
            'merdeka' => ($rombel) ? merdeka($rombel->kurikulum->nama_kurikulum) : FALSE,
            'rombel' => $rombel,
            'data_siswa' => $data_siswa,
            'pembelajaran' => $pembelajaran,
            'is_ppa' => is_ppa($rombel->semester_id),
        ];
        return $data;
    }
}
