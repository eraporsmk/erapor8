<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\RombonganBelajar;
use App\Models\Pembelajaran;
use App\Models\PesertaDidik;
use App\Models\AnggotaRombel;
use App\Models\Kelompok;
use App\Models\Ptk;
use App\Models\AnggotaAktPd;

class RombelController extends Controller
{
    public function index(){
        $data = RombonganBelajar::where($this->kondisi())->with([
            'wali_kelas' => function($query){
                $query->select('guru_id', 'nama', 'email', 'gelar_depan', 'gelar_belakang', 'photo');
            },
            'jurusan_sp' => function($query){
                $query->select('jurusan_sp_id', 'nama_jurusan_sp');
            },
            'kurikulum' => function($query){
                $query->select('kurikulum_id', 'nama_kurikulum');
            },
        ])
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->orderBy('nama')
        ->when(request()->q, function($query){
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->where($this->kondisi());
            $query->orWhereHas('wali_kelas', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->where($this->kondisi());
            $query->orWhereHas('jurusan_sp', function($query){
                $query->where('nama_jurusan_sp', 'ILIKE', '%' . request()->q . '%');
            });
            $query->where($this->kondisi());
            $query->orWhereHas('kurikulum', function($query){
                $query->where('nama_kurikulum', 'ILIKE', '%' . request()->q . '%');
            });
            $query->where($this->kondisi());
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    private function kondisi(){
        return function($query){
            $query->where('jenis_rombel', request()->jenis_rombel);
            $query->where('semester_id', request()->semester_id);
            $query->where('sekolah_id', request()->sekolah_id);
        };
    }
    public function pembelajaran(){
        $rombel = RombonganBelajar::find(request()->rombongan_belajar_id);
        $merdeka = Str::of($rombel->kurikulum->nama_kurikulum)->contains('Merdeka');
        if($merdeka){
            $kurikulum = 2022;
        } else {
            $kurikulum = 2017;
        }
        $data = Pembelajaran::with(['guru', 'pengajar'])->where(function($query){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
        })->orderBy('mata_pelajaran_id')->get();
        return response()->json([
            'data' => $data,
            'guru' => Ptk::where(function($query){
                $query->where('sekolah_id', request()->sekolah_id);
                $query->whereDoesntHave('ptk_keluar', function($query){
                    $query->where('semester_id', request()->semester_id);
                });
                $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang', 'photo');
            })->orderBy('nama')->get(),
            'kelompok' => Kelompok::where(function($query) use ($kurikulum){
                $query->where('kurikulum', $kurikulum);
                if($kurikulum != 2022){
                    $query->orWhere('kurikulum', 0);
                }
            })->orderBy('kelompok_id')->get(),
            'rombel' => $rombel,
        ]);
    }
    public function simpan_pembelajaran(){
        foreach(request()->all() as $item){
            Pembelajaran::where('pembelajaran_id', $item['pembelajaran_id'])->update([
                'nama_mata_pelajaran' => $item['nama_mata_pelajaran'],
                'guru_pengajar_id' => $item['guru_pengajar_id'],
                'kelompok_id' => $item['kelompok_id'],
                'no_urut' => $item['no_urut']
            ]);
        }
        $data = [
            'request' => request()->all(),
        ];
        return response()->json($data);
    }
    public function hapus_pembelajaran(){
        Pembelajaran::where('pembelajaran_id', request()->pembelajaran_id)->update([
            'guru_pengajar_id' => NULL,
            'kelompok_id' => NULL,
            'no_urut' => NULL
        ]);
    }
    public function anggota_rombel(){
        return response()->json([
            'data' => PesertaDidik::with(['agama'])->withWhereHas('anggota_rombel', function($query){
                $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            })->orderBy('nama')->get(),
            'rombel' => RombonganBelajar::find(request()->rombongan_belajar_id),
        ]);
    }
    public function hapus_anggota_rombel(){
        $data = [
            'color' => 'error',
            'title' => 'Gagal!',
            'text' => 'Permintaan tidak ditemukan!',
        ];
        if(request()->data == 'rombel'){
            $find = AnggotaRombel::find(request()->anggota_rombel_id);
            if($find){
                if($find->delete()){
                    $data = [
                        'color' => 'success',
                        'title' => 'Berhasil',
                        'text' => 'Anggota Rombel berhasil dikeluarkan',
                    ];
                } else {
                    $data = [
                        'color' => 'error',
                        'title' => 'Gagal!',
                        'text' => 'Anggota Rombel gagal dikeluarkan. Silahkan coba beberapa saat lagi!',
                    ];
                }
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Anggota Rombel tidak ditemukan. Silahkan muat ulang aplikasi!',
                ];
            }
        }
        if(request()->data == 'prakerin'){
            $find = AnggotaAktPd::find(request()->anggota_rombel_id);
            if($find){
                if($find->delete()){
                    $data = [
                        'color' => 'success',
                        'title' => 'Berhasil!',
                        'text' => 'Data Peserta Prakerin berhasil di hapus',
                    ];
                } else {
                    $data = [
                        'color' => 'error',
                        'title' => 'Gagal!',
                        'text' => 'Data Peserta Prakerin tidak ditemukan. Silahkan coba beberapa saat lagi!',
                    ];
                }
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Data Peserta Prakerin di hapus. Silahkan coba beberapa saat lagi!',
                ];
            }
        }
        return response()->json($data);
    }
}
