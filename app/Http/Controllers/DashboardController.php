<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sekolah;
use App\Models\StatusPenilaian;
use App\Models\TujuanPembelajaran;
use App\Models\RombonganBelajar;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if($user->hasRole(['admin', 'tu'], request()->periode_aktif)){
            $data = $this->dashboard_admin();
        } elseif($user->hasRole('guru', request()->periode_aktif)){
            $data = $this->dashboard_guru();
        } elseif($user->hasRole('siswa', request()->periode_aktif)){
            $data = $this->dashboard_siswa();
        } else {
            $data = $this->dashboard_user();
        }
        return response()->json($data);
    }
    public function status_penilaian(){
      $insert = 0;
      if(request()->has('rombongan_belajar_id')){
         $rombel = RombonganBelajar::find(request()->rombongan_belajar_id);
         $rombel->kunci_nilai = (request()->status) ? 0 : 1;
         $insert = $rombel->save();
      } else {
         $insert = StatusPenilaian::updateOrCreate(
            [
               'sekolah_id' => request()->sekolah_id,
               'semester_id' => request()->semester_id,
            ],
            ['status' => (request()->status) ? 1 : 0]
         );
      }
      if($insert){
         $data = [
             'icon' => 'success',
             'title' => 'Berhasil',
             'text' => 'Status Penilaian berhasil di simpan',
             'status' => (request()->status) ? 0 : 1,
         ];
     } else {
         $data = [
             'icon' => 'error',
             'title' => 'Gagal',
             'text' => 'Status Penilaian gagal disimpan. Silahkan coba beberapa saat lagi!',
             'status' => (request()->status) ? 0 : 1,
         ];
     }
     return response()->json($data);
   }
    private function dashboard_admin(){
        $sekolah = Sekolah::with(['kepala_sekolah' => function($query){
            $query->where('semester_id', request()->semester_id);
        }])->withCount([
            'ptk' => function($query){
                $query->where('is_dapodik', 1);
                $query->whereDoesntHave('ptk_keluar', function($query){
                $query->where('semester_id', request()->semester_id);
            });
            },
            'pd_aktif' => function($query){
                $query->where('semester_id', request()->semester_id);
                $query->whereHas('rombongan_belajar', function($query){
                    $query->where('jenis_rombel', 1);
                });
            },
            'nilai_akhir' => function($query){
                $query->whereHas('pembelajaran', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                });
            },
            'cp' => function($query){
                $query->whereHas('pembelajaran', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                });
            },
            'nilai_projek' => function($query){
                $query->whereHas('anggota_rombel', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                });
                $query->whereNotNull('rencana_budaya_kerja_id');
            }
        ])->find(request()->sekolah_id);
        $status_penilaian = StatusPenilaian::firstOrCreate(
            [
                'sekolah_id' => request()->sekolah_id,
                'semester_id' => request()->semester_id,
            ],
            ['status' => 1]
        );
        $data = [
            'sekolah' => $sekolah,
            'rekap' => [
                [
                'data' => 'PTK',
                'jml' => $sekolah->ptk_count,
                'icon' => 'user-graduate',
                'variant' => 'info',
                'html' => '',
                ],
                [
                'data' => 'Peserta Didik',
                'jml' => $sekolah->pd_aktif_count,
                'icon' => 'children',
                'variant' => 'warning',
                'html' => '',
                ],
                [
                'data' => 'CP',
                'jml' => $sekolah->cp_count,
                'icon' => 'list-check',
                'variant' => 'success',
                'html' => 'Jumlah Mata Pelajaran yang telah di input Deskripsi Capaian Pembelajaran',
                ],
                [
                'data' => 'TP',
                'jml' => TujuanPembelajaran::whereHas('cp', function($query){
                    $query->whereHas('pembelajaran', function($query){
                        $query->where('sekolah_id', request()->sekolah_id);
                        $query->where('semester_id', request()->semester_id);
                    });
                })->orWhereHas('kd', function($query){
                    $query->whereHas('pembelajaran', function($query){
                        $query->where('sekolah_id', request()->sekolah_id);
                        $query->where('semester_id', request()->semester_id);
                    });
                })->count(),
                'icon' => 'spell-check',
                'variant' => 'error',
                'html' => 'Jumlah Tujuan Pembelajaran yang telah di input oleh PTK',
                ],
                [
                'data' => 'Nilai Akhir',
                'jml' => $sekolah->nilai_akhir_count,
                'icon' => 'list-check',
                'variant' => 'primary',
                'html' => 'Jumlah Nilai Akhir yang siap dicetak',
                ],
                [
                'data' => 'Nilai Projek',
                'jml' => $sekolah->nilai_projek_count,
                'icon' => 'list-check',
                'variant' => 'error',
                'html' => 'Jumlah Peserta Didik yang telah dinilai P5',
                ],
            ],
            'app' => [
                'app_name' => config('app.name'),
                'app_version' => get_setting('app_version'),
                'db_version' => get_setting('db_version'),
                'status_penilaian' => ($status_penilaian->status) ? TRUE: FALSE
            ],
            'text_wa' => urlencode('Mohon bantuan terkait e-Rapor SMK'."\n".'NPSN:'.$sekolah->npsn),
            'helpdesk' => [
                [
                'nama' => 'Wahyudin',
                'hp' => '628156441864',
                'instansi' => 'SMKN 1 Tangerang',
                ],
                [
                'nama' => 'Ahmad Aripin',
                'hp' => '6281229997730',
                'instansi' => 'SMK Ariya Metta Tangerang'
                ],
                [
                'nama' => 'Ikhsan Wijaya',
                'hp' => '6282174508706',
                'instansi' => 'SMKN 1 Lubuk Sikaping',
                ],
                [
                'nama' => 'Adhi Prasetya',
                'hp' => '6285643935009',
                'instansi' => 'SMK Muhammadiyah 1 Temon',
                ],
                [
                'nama' => 'Bambang Hermanto',
                'hp' => '6281356723484',
                'instansi' => 'SMK Budi Luhur Sintang',
                ],
                [
                'nama' => 'Djoko Poernomo',
                'hp' => '628119890509',
                'instansi' => 'SMKN 51 Jakarta',
                ],
                [
                'nama' => 'Muhamad Nazmudin',
                'hp' => '6285651414221',
                'instansi' => 'SMK Negeri 1 Cilimus',
                ],
                [
                'nama' => 'Didik Harianto',
                'hp' => '6285258636767',
                'instansi' => 'SMKN 1 Kraksaan'
                ],
                [
                'nama' => 'Iwan Sutisna',
                'hp' => '6285258636767',
                'instansi' => 'SMKN 1 Lemahabang'
                ],
            ],
        ];
        return $data;
    }
    private function dashboard_guru(){
        $data = [];
        return response()->json($data);
    }
    private function dashboard_siswa(){
        $data = [];
        return response()->json($data);
    }
    private function dashboard_user(){
        $data = [];
        return response()->json($data);
    }
}
