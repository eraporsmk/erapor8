<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Sekolah;
use App\Models\StatusPenilaian;
use App\Models\TujuanPembelajaran;
use App\Models\RombonganBelajar;
use App\Models\Pembelajaran;
use App\Models\AnggotaRombel;
use App\Models\DeskripsiMataPelajaran;
use App\Models\Absensi;
use App\Models\NilaiAkhir;

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
             'color' => 'success',
             'title' => 'Berhasil',
             'text' => 'Status Penilaian berhasil di simpan',
             'status' => (request()->status) ? 0 : 1,
         ];
     } else {
         $data = [
             'color' => 'error',
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
         $tp = TujuanPembelajaran::where(function($query){
            $query->whereHas('cp', function($query){
               $query->whereHas('pembelajaran', function($query){
                  $query->where('sekolah_id', request()->sekolah_id);
                  $query->where('semester_id', request()->semester_id);
               });
            });
            $query->orWhereHas('kd', function($query){
                  $query->whereHas('pembelajaran', function($query){
                  $query->where('sekolah_id', request()->sekolah_id);
                  $query->where('semester_id', request()->semester_id);
               });
            });
         })->count();
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
                'jml' => $tp,
                  'icon' => 'spell-check',
                  'variant' => 'error',
                  'html' => 'Jumlah Tujuan Pembelajaran yang telah di input oleh PTKa',
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
    private function kondisi(){
      return function($query){
         $query->withCount([
            'anggota_rombel',
            'pd_pkl',
            'pd_pkl as pd_pkl_dinilai' => function($query){
               $query->has('nilai_pkl');
            }
          ]);
          $query->orderBy('mata_pelajaran_id');
          $query->where('semester_id', request()->semester_id);
          $query->where('sekolah_id', request()->sekolah_id);
          $query->where('guru_id', request()->guru_id);
          $query->whereNotNull('kelompok_id');
          $query->whereNotNull('no_urut');
          $query->orWhere('guru_pengajar_id', request()->guru_id);
          $query->where('semester_id', request()->semester_id);
          $query->where('sekolah_id', request()->sekolah_id);
          $query->whereNotNull('kelompok_id');
          $query->whereNotNull('no_urut');
      };
    }
    private function dashboard_guru(){
        $data_rombel = RombonganBelajar::withWhereHas('pembelajaran', $this->kondisi())->with(['wali_kelas' => function($query){
         $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang');
      }])->orderBy('tingkat')->get();
      $result = [];
      $no = 1;
      foreach($data_rombel as $rombel){
         foreach($rombel->pembelajaran as $pembelajaran){
            $result[] = [
               'no' => $no++,
               'pembelajaran_id' => $pembelajaran->pembelajaran_id,
               'rombongan_belajar_id' => $pembelajaran->rombongan_belajar_id,
               'mata_pelajaran_id' => $pembelajaran->mata_pelajaran_id,
               'induk_pembelajaran_id' => $pembelajaran->induk_pembelajaran_id,
               'nama_mata_pelajaran' => $pembelajaran->nama_mata_pelajaran,
               'rombel' => $rombel->nama,
               'wali_kelas' => ($rombel->wali_kelas) ? $rombel->wali_kelas->nama_lengkap : '-',
               'pd' => $pembelajaran->anggota_rombel_count,
               'pd_dinilai' => $this->anggota_dinilai($pembelajaran->pembelajaran_id, $pembelajaran->rombongan_belajar_id),
               'kkm' => $pembelajaran->kkm,
               'kelompok_id' => $pembelajaran->kelompok_id,
               'semester_id' => $pembelajaran->semester_id,
               'pd_pkl_count' => $pembelajaran->pd_pkl_count,
               'pd_pkl_dinilai' => $pembelajaran->pd_pkl_dinilai,
            ];
         }
      }
      return ['pembelajaran' => $result];
    }
    private function dashboard_siswa(){
        $data = [];
        return response()->json($data);
    }
    private function dashboard_user(){
        $data = [];
        return response()->json($data);
    }
    public function wali(){
      $rombel = RombonganBelajar::where(function($query){
         $query->where('jenis_rombel', 1);
         $query->where('guru_id', request()->guru_id);
         $query->where('semester_id', request()->semester_id);
         $query->where('sekolah_id', request()->sekolah_id);
      })->first();
      $result = [];
      $result_pilihan = [];
      $rombel_pilihan = '';
      if($rombel){
         //$rombel = $rombel->nama;
         $pembelajaran = Pembelajaran::where(function($query){
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->whereHas('rombongan_belajar', function($query){
               $query->where('jenis_rombel', 1);
               $query->where('guru_id', request()->guru_id);
               $query->where('semester_id', request()->semester_id);
               $query->where('sekolah_id', request()->sekolah_id);
            });
         })->with([
            'guru' => function($query){
               $query->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang');
               }, 
            'pengajar' => function($query){
               $query->select('guru_id', 'nama');
            },
         ])->withCount([
            'anggota_rombel',
            'pd_pkl',
            'pd_pkl as pd_pkl_dinilai' => function($query){
               $query->has('nilai_pkl');
            }
          ])->orderBy('mata_pelajaran_id')->get();
         $no = 1;
         $result = [];
         foreach($pembelajaran as $item){
            $result[] = [
               'no' => $no++,
               'pembelajaran_id' => $item->pembelajaran_id,
               'mata_pelajaran_id' => $item->mata_pelajaran_id,
               'induk_pembelajaran_id' => $item->induk_pembelajaran_id,
               'rombongan_belajar_id' => $item->rombongan_belajar_id,
               'nama_mata_pelajaran' => $item->nama_mata_pelajaran,
               'guru' => ($item->pengajar) ? $item->pengajar->nama_lengkap : $item->guru->nama_lengkap,
               'pd' => $item->anggota_rombel_count,
               'pd_dinilai' => $this->anggota_dinilai($item->pembelajaran_id, $item->rombongan_belajar_id),
               'kkm' => $item->kkm,
               'kelompok_id' => $item->kelompok_id,
               'semester_id' => $item->semester_id,
               'pd_pkl_count' => $item->pd_pkl_count,
               'pd_pkl_dinilai' => $item->pd_pkl_dinilai,
            ];
         }
         $pembelajaran_pilihan = Pembelajaran::where(function($query){
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->whereHas('rombongan_belajar', function($query){
               $query->where('jenis_rombel', 16);
               $query->where('guru_id', request()->guru_id);
               $query->where('semester_id', request()->semester_id);
               $query->where('sekolah_id', request()->sekolah_id);
            });
         })->with([
            'guru' => function($query){
               $query->select('guru_id', 'nama');
               }, 
            'pengajar' => function($query){
               $query->select('guru_id', 'nama');
            },
            'rombongan_belajar' => function($query){
               $query->select('rombongan_belajar_id', 'nama');
            },
         ])->withCount(['anggota_rombel'])->orderBy('mata_pelajaran_id')->get();
         $no = 1;
         $result_pilihan = [];
         $rombel_pilihan = '';
         foreach($pembelajaran_pilihan as $item_pilihan){
            $result_pilihan[] = [
               'no' => $no++,
               'pembelajaran_id' => $item_pilihan->pembelajaran_id,
               'mata_pelajaran_id' => $item_pilihan->mata_pelajaran_id,
               'induk_pembelajaran_id' => $item_pilihan->induk_pembelajaran_id,
               'rombongan_belajar_id' => $item_pilihan->rombongan_belajar_id,
               'nama_mata_pelajaran' => $item_pilihan->nama_mata_pelajaran,
               'guru' => ($item_pilihan->pengajar) ? $item_pilihan->pengajar->nama_lengkap : $item_pilihan->guru->nama_lengkap,
               'rombel' => $item_pilihan->rombongan_belajar->nama,
               'wali_kelas' => ($item_pilihan->rombongan_belajar->wali_kelas) ? $item_pilihan->rombongan_belajar->wali_kelas->nama_lengkap : '-',
               'pd' => $item_pilihan->anggota_rombel_count,
               'pd_dinilai' => $this->anggota_dinilai($item_pilihan->pembelajaran_id, $item_pilihan->rombongan_belajar_id),
               'kkm' => $item_pilihan->kkm,
               'kelompok_id' => $item_pilihan->kelompok_id,
               'semester_id' => $item_pilihan->semester_id,
            ];
            $rombel_pilihan = $item_pilihan->rombongan_belajar;
         }
      }
      return response()->json([
         'pembelajaran' => $result, 
         'rombel' => $rombel,
         'pembelajaran_pilihan' => $result_pilihan,
         'rombel_pilihan' => $rombel_pilihan,
      ]);
   }
   public function anggota_dinilai($pembelajaran_id, $rombongan_belajar_id){
      $data = AnggotaRombel::whereHas('nilai_akhir', function($query) use ($pembelajaran_id, $rombongan_belajar_id){
         $query->where('rombongan_belajar_id', $rombongan_belajar_id);
         $query->where('pembelajaran_id', $pembelajaran_id);
      })->count();
      return $data;
   }
    public function detil_penilaian(){
        $pembelajaran = Pembelajaran::with([
            'induk' => function($query){
                $query->whereNotNull('kelompok_id');
                $query->whereNotNull('no_urut');
            }, 
            'rombongan_belajar' => function($query){
                $query->with([
                    'kurikulum',
                    'pd' => function($query){
                    $query->orderBy('nama');
                    $query->with([
                        'nilai_akhir_kurmer' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
                        },
                        'nilai_akhir_pengetahuan' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
                        },
                        'nilai_akhir_keterampilan' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
                        },
                        'deskripsi_mapel' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
                            $query->where('asal', 0);
                        },
                        'agama',
                    ]);
                },
                ]);
            },
            'pd_pkl' => function($query){
                $query->with([
                'pd' => function($query){
                    $query->with([
                        'nilai_akhir_kurmer' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                        },
                        'deskripsi_mapel' => function($query){
                            $query->where('pembelajaran_id', request()->pembelajaran_id);
                        },
                        'agama',
                    ]);
                },
                ]);
            }
        ])->withCount('tema')->find(request()->pembelajaran_id);
        $data_siswa = [];
        if($pembelajaran->mata_pelajaran_id == 800001000){
            foreach($pembelajaran->pd_pkl as $pd){
                $data_siswa[] = $pd->pd;
            }
            $data_siswa = collect($data_siswa);
            $data_siswa = $data_siswa->sortBy('nama');
            $data_siswa = $data_siswa->values()->all();
        } else {
            $data_siswa = $pembelajaran->rombongan_belajar->pd;
        }
        return response()->json([
            'pembelajaran' => $pembelajaran,
            'data_siswa' => $data_siswa,
            'merdeka' => merdeka($pembelajaran->rombongan_belajar->kurikulum->nama_kurikulum),
            'title' => 'Detil Penilaian Mata Pelajaran '.$pembelajaran->nama_mata_pelajaran,
            'is_ppa' => is_ppa($pembelajaran->semester_id),
        ]);
    }
    public function generate_nilai(){
      $pembelajaran = Pembelajaran::with([
         'rombongan_belajar' => function($query){
            $query->with(['pd' => function($query){
               $query->orderBy('nama');
               $query->with([
                  'nilai_akhir_induk' => function($query){
                     $query->where('pembelajaran_id', request()->pembelajaran_id);
                  },
                  'nilai_akhir_kurmer' => function($query){
                     $query->where('pembelajaran_id', request()->pembelajaran_id);
                  },
                  'nilai_akhir_pengetahuan' => function($query){
                     $query->where('pembelajaran_id', request()->pembelajaran_id);
                  },
                  'deskripsi_mapel' => function($query){
                     $query->where('pembelajaran_id', request()->pembelajaran_id);
                     $query->where('asal', 0);
                  },
               ]);
            }]);
         },
         'pd_pkl' => function($query){
            $query->withWhereHas('anggota_rombel', function($query){
               $query->whereHas('rombongan_belajar', function($query){
                  $query->whereHas('pembelajaran', function($query){
                     $query->where('pembelajaran_id', request()->pembelajaran_id);
                  });
               });
            });
            $query->withWhereHas('nilai_pkl');
            $query->withSum('absensi_pkl as sakit', 'sakit');
            $query->withSum('absensi_pkl as izin', 'izin');
            $query->withSum('absensi_pkl as alpa', 'alpa');
         }
      ])->find(request()->pembelajaran_id);
      if($pembelajaran->mata_pelajaran_id == '800001000'){
         foreach($pembelajaran->pd_pkl as $pd){
            $nilai = [];
            $desc = [];
            foreach($pd->nilai_pkl as $nilai_pkl){
               $nilai[] = $nilai_pkl->nilai;
               $desc[] = $nilai_pkl->deskripsi;
            }
            if($desc){
               DeskripsiMataPelajaran::updateOrCreate(
                  [
                     'sekolah_id' => $pd->anggota_rombel->sekolah_id,
                     'anggota_rombel_id' => $pd->anggota_rombel->anggota_rombel_id,
                     'pembelajaran_id' => request()->pembelajaran_id,
                  ],
                  [
                     'deskripsi_pengetahuan' => collect(array_unique(array_filter($desc)))->implode(', '),
                     'last_sync' => now()->subDays(30),
                  ]
               );
            }
            Absensi::updateOrCreate(
               [
                  'sekolah_id' => $pd->anggota_rombel->sekolah_id,
                  'anggota_rombel_id' => $pd->anggota_rombel->anggota_rombel_id,
               ],
               [
                  'sakit' => $pd->sakit,
                  'izin' => $pd->izin,
                  'alpa' => $pd->alpa,
                  'last_sync' => now()->subDays(30),
               ]
            );
            NilaiAkhir::updateOrCreate(
               [
                  'sekolah_id' => $pd->anggota_rombel->sekolah_id,
                  'pembelajaran_id' => request()->pembelajaran_id,
                  'anggota_rombel_id' => $pd->anggota_rombel->anggota_rombel_id,
                  'kompetensi_id' => 4,
               ],
               [
                  'nilai' => number_format(collect(array_filter($nilai))->avg(), 0),
                  'last_sync' => now()->subDays(30),
               ]
            );
         }
         $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Nilai Praktik Kerja Lapangan berhasil digenerate',
            'pd' => $pembelajaran->pd_pkl,
            'nilai' => $nilai,
            'desc' => $desc,
         ];
      } else {
         $sub_mapel = Pembelajaran::has('all_nilai_akhir_kurmer')->where('induk_pembelajaran_id', request()->pembelajaran_id)->get();
         $nilai_akhir_induk = [];
         $deskripsi_pengetahuan_induk = [];
         $deskripsi_keterampilan_induk = [];
         foreach($pembelajaran->rombongan_belajar->pd as $pd){
            $nilai_akhir = [];
            $deskripsi = [];
            $nilai_akhir_induk[$pd->peserta_didik_id] = 0;
            if($pd->nilai_akhir_induk){
               if(!$pd->nilai_akhir_induk->where('kompetensi_id', 99)->first()){
                  $pd->nilai_akhir_induk->kompetensi_id = 99;
                  $pd->nilai_akhir_induk->nilai = $pd->nilai_akhir_induk->nilai;
                  $pd->nilai_akhir_induk->save();
                  $nilai_akhir_induk[$pd->peserta_didik_id] = ($pd->nilai_akhir_induk) ? $pd->nilai_akhir_induk->nilai : 0;
               } else {
                  $nilai_akhir_induk[$pd->peserta_didik_id] = $pd->nilai_akhir_induk->where('kompetensi_id', 99)->first()?->nilai;
               }
            }
            $deskripsi_pengetahuan_induk[$pd->peserta_didik_id] = NULL;
            $deskripsi_keterampilan_induk[$pd->peserta_didik_id] = NULL;
            if($pd->deskripsi_mapel){
               $first = $pd->deskripsi_mapel->where('asal', 1)->first();
               if(!$first){
                  $pd->deskripsi_mapel->asal = 1;
                  $pd->deskripsi_mapel->save();
                  $deskripsi_pengetahuan_induk[$pd->peserta_didik_id] = $pd->deskripsi_mapel->deskripsi_pengetahuan;
                  $deskripsi_keterampilan_induk[$pd->peserta_didik_id] = $pd->deskripsi_mapel->deskripsi_keterampilan;
               } else {
                  $deskripsi_pengetahuan_induk[$pd->peserta_didik_id] = $pd->deskripsi_mapel->where('asal', 1)->first()?->deskripsi_pengetahuan;
                  $deskripsi_keterampilan_induk[$pd->peserta_didik_id] = $pd->deskripsi_mapel->where('asal', 1)->first()?->deskripsi_keterampilan;
               }
            }
         }
         $nilai_akhir_sub = [];
         $deskripsi_sub = [];
         $sub_id = [];
         foreach($sub_mapel as $sub){
            $data_pd = PesertaDidik::withWhereHas('anggota_rombel', function($query){
                  $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
               })->with([
                  'nilai_akhir_kurmer' => function($query) use ($sub){
                     $query->where('pembelajaran_id', $sub->pembelajaran_id);
                  },
                  'nilai_akhir_pengetahuan' => function($query) use ($sub){
                     $query->where('pembelajaran_id', $sub->pembelajaran_id);
                  },
                  'deskripsi_mapel' => function($query) use ($sub){
                     $query->where('pembelajaran_id', $sub->pembelajaran_id);
                  },
            ])->orderBy('nama')->get();
            foreach($data_pd as $pd){
               $nilai_akhir = [];
               $deskripsi = [];
               $nilai_akhir[$sub->pembelajaran_id][$pd->peserta_didik_id] = ($pd->nilai_akhir_kurmer) ? $pd->nilai_akhir_kurmer->nilai : 0;
               $deskripsi[$sub->pembelajaran_id][$pd->peserta_didik_id] = [];
               if($pd->deskripsi_mapel){
                  $deskripsi[$sub->pembelajaran_id][$pd->peserta_didik_id] = [$pd->deskripsi_mapel->deskripsi_pengetahuan, $pd->deskripsi_mapel->deskripsi_keterampilan];
               }
               $nilai_akhir_sub[$pd->peserta_didik_id][] = [
                  'pembelajaran_id' => $sub->pembelajaran_id,
                  'peserta_didik_id' => $pd->peserta_didik_id,
                  'nilai' => ($pd->nilai_akhir_kurmer) ? $pd->nilai_akhir_kurmer->nilai : 0,
                  'deskripsi_pengetahuan' => ($pd->deskripsi_mapel) ? $pd->deskripsi_mapel->deskripsi_pengetahuan : NULL,
                  'deskripsi_keterampilan' => ($pd->deskripsi_mapel) ? $pd->deskripsi_mapel->deskripsi_keterampilan : NULL,
               ];
               $deskripsi_sub[] = $deskripsi;
            }
         }
         $nilai_sub = [];
         foreach($nilai_akhir_sub as $peserta_didik_id => $nilai_akhir){
            $arr_nilai = Str::of(collect($nilai_akhir)->implode('nilai', ','))->explode(',');
            $merged_nilai = $arr_nilai->merge([$nilai_akhir_induk[$peserta_didik_id]]);
            $nilai_sub[$peserta_didik_id] = [
               'nilai' => number_format($merged_nilai->avg(), 0),
               'deskripsi_pengetahuan' => $deskripsi_pengetahuan_induk[$peserta_didik_id].'. '.collect($nilai_akhir)->implode('deskripsi_pengetahuan', ','),
               'deskripsi_keterampilan' => $deskripsi_keterampilan_induk[$peserta_didik_id].'. '.collect($nilai_akhir)->implode('deskripsi_keterampilan', ','),
            ];
         }
         foreach($nilai_sub as $pd_id => $akhir){
            $anggota = Anggota_rombel::with(['nilai_akhir_mapel' => function($query){
               $query->where('pembelajaran_id', request()->pembelajaran_id);
            }])->where('peserta_didik_id', $pd_id)->where('rombongan_belajar_id', request()->rombongan_belajar_id)->first();
            if($anggota){
               if($akhir['deskripsi_pengetahuan'] || $akhir['deskripsi_keterampilan']){
                  DeskripsiMataPelajaran::updateOrCreate(
                     [
                        'sekolah_id' => $anggota->sekolah_id,
                        'anggota_rombel_id' => $anggota->anggota_rombel_id,
                        'pembelajaran_id' => request()->pembelajaran_id,
                        'asal' => 0,
                     ],
                     [
                        'deskripsi_pengetahuan' => str_replace('. ,', '', $akhir['deskripsi_pengetahuan']),
                        'deskripsi_keterampilan' => str_replace('. ,', '', $akhir['deskripsi_keterampilan']),
                        'last_sync' => now()->subDays(30),
                     ]
                  );
               }
               NilaiAkhir::updateOrCreate(
                  [
                     'sekolah_id' => $anggota->sekolah_id,
                     'pembelajaran_id' => request()->pembelajaran_id,
                     'anggota_rombel_id' => $anggota->anggota_rombel_id,
                     'kompetensi_id' => 4,
                  ],
                  [
                     'nilai' => number_format($akhir['nilai'], 0),
                     'last_sync' => now()->subDays(30),
                  ]
               );
            }
         }
         $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Nilai Sub Mapel berhasil digenerate',
         ];
      }
      return response()->json($data);
   }
}
