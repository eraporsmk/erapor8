<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\TeknikPenilaian;
use App\Models\PesertaDidik;
use App\Models\TujuanPembelajaran;
use App\Models\NilaiTp;
use App\Models\NilaiSumatif;
use App\Models\Pembelajaran;
use App\Models\NilaiAkhir;
use App\Models\TpNilai;
use App\Models\DeskripsiMataPelajaran;
use App\Models\NilaiBudayaKerja;
use App\Models\RombonganBelajar;
use App\Models\NilaiEkstrakurikuler;
use App\Imports\NilaiSumatifLingkupMateri;
use App\Imports\NilaiSumatifAkhirSemester;
use App\Imports\NilaiAkhirImport;
use Carbon\Carbon;
use Storage;

class PenilaianController extends Controller
{
    public function get_cp(){
        $get_mapel_agama = filter_agama_siswa(request()->pembelajaran_id, request()->rombongan_belajar_id);
        $metode = TeknikPenilaian::find(request()->teknik_penilaian_id);
        $show_cp = ($metode->nama == 'Sumatif Lingkup Materi') ? TRUE : FALSE;
        $data = [
            'show_cp' => $show_cp,
            'data_siswa' => PesertaDidik::withWhereHas('anggota_rombel', function($query){
                $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
                $query->with([
                    'nilai_sumatif' => function($query){
                        $query->where('pembelajaran_id', request()->pembelajaran_id);
                    },
                    'nilai_tp' => function($query){
                        $query->where('pembelajaran_id', request()->pembelajaran_id);
                    }
                ]);
            })->where(function($query) use ($get_mapel_agama){
                if($get_mapel_agama){
                    $query->where('agama_id', $get_mapel_agama);
                }
            })->orderBy('nama')->get(),
            'data_tp' => $show_cp ? TujuanPembelajaran::where(function($query){
                $query->whereHas('tp_mapel', function($query){
                    $query->where('tp_mapel.pembelajaran_id', request()->pembelajaran_id);
                });
            })->orderBy('created_at')->get() : [],
        ];
        return response()->json($data);
    }
    public function simpan(){
        $insert = 0;
        $text = 'Unknow';
        if(request()->opsi == 'sumatif-lingkup-materi'){
            foreach(request()->tp as $uuid => $nilai){
                $segments = Str::of($uuid)->split('/[\s#]+/');
                $anggota_rombel_id = $segments->first();
                $tp_id = $segments->last();
                if($nilai > -1 && $nilai < 101){
                    $insert++;
                    NilaiTp::updateOrCreate(
                        [
                            'sekolah_id' => request()->sekolah_id,
                            'anggota_rombel_id' => $anggota_rombel_id,
                            'pembelajaran_id' => request()->pembelajaran_id,
                            'tp_id' => $tp_id,
                        ],
                        [
                            'nilai' => number_format($nilai,0),
                            'last_sync' => Carbon::now()->subDays(30),
                        ]
                    );
                } else {
                    if(Str::isUuid($anggota_rombel_id)){
                        NilaiTp::where('anggota_rombel_id', $anggota_rombel_id)->where('pembelajaran_id', request()->pembelajaran_id)->where('tp_id', $tp_id)->delete();
                    }
                }
            }
            $text = 'Nilai Sumatif Lingkup Materi';
        }
        if(request()->opsi == 'sumatif-akhir-semester'){
            $text = 'Nilai Sumatif Akhir Semester';
            foreach(request()->sumatif as $uuid => $nilai){
                $segments = Str::of($uuid)->split('/[\s#]+/');
                $anggota_rombel_id = $segments->first();
                $jenis = $segments->last();
                if($nilai > -1 && $nilai < 101){
                    $insert++;
                    NilaiSumatif::updateOrCreate(
                        [
                            'sekolah_id' => request()->sekolah_id,
                            'anggota_rombel_id' => $anggota_rombel_id,
                            'pembelajaran_id' => request()->pembelajaran_id,
                            'jenis' => $jenis,
                        ],
                        [
                            'nilai' => number_format($nilai,0),
                            'last_sync' => Carbon::now()->subDays(30),
                        ]
                    );
                } else {
                    NilaiSumatif::where('anggota_rombel_id', $anggota_rombel_id)->where('pembelajaran_id', request()->pembelajaran_id)->where('jenis', $jenis)->delete();
                }
            }
        }
        if(request()->opsi == 'nilai-akhir'){
            $text = 'Nilai Akhir';
            $kompetensi_id = (request()->merdeka) ? 4 : 1;
            if(request()->mata_pelajaran_id !='800001000'){
                $sub_mapel = Pembelajaran::where(function($query){
                    $query->where('induk_pembelajaran_id', request()->pembelajaran_id);
                    $query->whereNotNull('kelompok_id');
                    $query->whereNotNull('no_urut');
                })->get();
                if($sub_mapel->count()){
                    $kompetensi_id = 99;
                }
            }
            foreach(request()->akhir as $anggota_rombel_id => $nilai_akhir){
                $insert++;
                if($nilai_akhir >= 0 && $nilai_akhir <= 100){
                    NilaiAkhir::updateOrCreate(
                        [
                            'sekolah_id' => request()->sekolah_id,
                            'anggota_rombel_id' => $anggota_rombel_id,
                            'pembelajaran_id' => request()->pembelajaran_id,
                            'kompetensi_id' => $kompetensi_id,
                        ],
                        [
                            'nilai' => ($nilai_akhir) ? number_format($nilai_akhir,0) : 0,
                            'last_sync' => now(),
                        ]
                    );
                } else {
                    NilaiAkhir::where('anggota_rombel_id', $anggota_rombel_id)->where('pembelajaran_id', request()->pembelajaran_id)->where('kompetensi_id', $kompetensi_id)->delete();
                }
            }
            $first = [];
            $last = [];
            foreach(request()->kompeten as $uuid => $kompeten){
                $segments = Str::of($uuid)->split('/[\s#]+/');
                $anggota_rombel_id = $segments->first();
                $tp_id = $segments->last();
                $first[] = $anggota_rombel_id;
                $last[] = $tp_id;
                $tp = TujuanPembelajaran::find($tp_id);
                if($tp){
                    if(request()->merdeka){
                        $update = [
                            'cp_id' => $tp->cp_id,
                            'last_sync' => now(),
                        ];
                    } else {
                        $update = [
                            'kd_id' => $tp->kd_id,
                            'last_sync' => now(),
                        ];
                    }
                    if($kompeten > -1){
                        TpNilai::updateOrCreate(
                            [
                                'sekolah_id' => request()->sekolah_id,
                                'anggota_rombel_id' => $anggota_rombel_id,
                                'tp_id' => $tp_id,
                                'kompeten' => $kompeten,
                            ],
                            $update
                        );
                    } else {
                        TpNilai::where('anggota_rombel_id', $anggota_rombel_id)->where('tp_id', $tp_id)->delete();
                    }
                }
            }
        }
        if(request()->opsi == 'capaian-kompetensi'){
            $text = 'Capaian Kompetensi';
            foreach(request()->angka as $anggota_rombel_id => $nilai_akhir){
                if(request()->kompeten[$anggota_rombel_id] || request()->inkompeten[$anggota_rombel_id]){
                    $insert++;
                    DeskripsiMataPelajaran::updateOrCreate(
                        [
                            'sekolah_id' => request()->sekolah_id,
                            'anggota_rombel_id' => $anggota_rombel_id,
                            'pembelajaran_id' => request()->pembelajaran_id,
                        ],
                        [
                            'deskripsi_pengetahuan' => request()->kompeten[$anggota_rombel_id],
                            'deskripsi_keterampilan' => request()->inkompeten[$anggota_rombel_id],
                            'last_sync' => now(),
                        ]
                    );
                } else {
                    DeskripsiMataPelajaran::where('anggota_rombel_id', $anggota_rombel_id)->where('pembelajaran_id', request()->pembelajaran_id)->delete();
                }
            }
        }
        if(request()->opsi == 'reset-kompetensi'){
            $text = 'Capaian Kompetensi';
            $insert = DeskripsiMataPelajaran::where('pembelajaran_id', request()->pembelajaran_id)->delete();
        }
        if(request()->opsi == 'nilai-sikap'){
            $text = 'Nilai Sikap';
            request()->validate(
                [
                    'tingkat' => 'required_if:nilai_budaya_kerja_id,null',
                    'rombongan_belajar_id' => 'required_if:nilai_budaya_kerja_id,null',
                    'peserta_didik_id' => 'required_if:nilai_budaya_kerja_id,null',
                    'tingkat' => 'required_if:nilai_budaya_kerja_id,null',
                    'tanggal' => 'required',
                    'budaya_kerja_id' => 'required',
                    'elemen_id' => 'required',
                    'opsi_sikap' => 'required',
                    'uraian_sikap' => 'required',
                ],
                [
                    'tingkat.required_if' => 'Tingkat Kelas tidak boleh kosong!',
                    'rombongan_belajar_id.required_if' => 'Rombongan Belajar tidak boleh kosong!',
                    'peserta_didik_id.required_if' => 'Peserta Didik tidak boleh kosong!',
                    'tanggal.required' => 'Tanggal tidak boleh kosong!',
                    'budaya_kerja_id.required' => 'Dimensi Sikap tidak boleh kosong!',
                    'elemen_id.required' => 'Elemen Sikap tidak boleh kosong!',
                    'opsi_sikap.required' => 'Opsi Sikap tidak boleh kosong!',
                    'uraian_sikap.required' => 'Uraian Sikap tidak boleh kosong!',
                ]
            );
            $find = new NilaiBudayaKerja;
            if(request()->nilai_budaya_kerja_id){
                $find = $find->find(request()->nilai_budaya_kerja_id);
            } else {
                $find->sekolah_id = request()->sekolah_id;
                $find->guru_id = request()->guru_id;
                $find->anggota_rombel_id = request()->anggota_rombel_id;
                $find->last_sync = now();
            }
            $find->tanggal = request()->tanggal;
            $find->budaya_kerja_id = request()->budaya_kerja_id;
            $find->elemen_id = request()->elemen_id;
            $find->opsi_id = request()->opsi_sikap;
            $find->deskripsi = request()->uraian_sikap;
            $insert = $find->save();
        }
        if(request()->opsi == 'nilai-ekskul'){
            $text = 'Nilai Ekstrakurikuler';
            foreach(request()->nilai_ekskul as $anggota_rombel_id => $nilai){
                $insert++;
                if($nilai){
                    NilaiEkstrakurikuler::updateOrCreate(
                        [
                            'anggota_rombel_id' => $anggota_rombel_id,
                            'sekolah_id' => request()->sekolah_id,
                            'ekstrakurikuler_id' => request()->ekstrakurikuler_id,
                        ],
                        [
                            'nilai' => $nilai,
                            'deskripsi_ekskul' => request()->deskripsi_ekskul[$anggota_rombel_id],
                            'last_sync' => now(),
                        ]
                    );
                } else {
                    NilaiEkstrakurikuler::where('anggota_rombel_id', $anggota_rombel_id)->where('ekstrakurikuler_id', request()->ekstrakurikuler_id)->delete();
                }
            }
        }
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil disimpan',
                'request' => request()->all(),
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal disimpan. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
    public function destroy(){
        $deleted = FALSE;
        $text = 'Unknow';
        if(request()->data == 'nilai-sikap'){
            $text = 'Nilai Sikap';
            $deleted = NilaiBudayaKerja::where('nilai_budaya_kerja_id', request()->id)->delete();
        }
        if(request()->data == 'nilai-ekskul'){
            $text = 'Nilai Ekstrakurikuler';
            $deleted = NilaiEkstrakurikuler::where(function($query){
                $query->where('ekstrakurikuler_id', request()->ekstrakurikuler_id);
                if(request()->rombel_id_reguler){
                    $query->whereHas('peserta_didik', function($query){
                        $query->whereHas('anggota_rombel', function($query){
                            $query->where('rombongan_belajar_id', request()->rombel_id_reguler);
                        });
                    });
                }
            })->delete();
        }
        if($deleted){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil dihapus',
                'request' => request()->all(),
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal dihapus. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
    private function wherehas($query){
        if(request()->merdeka){
            $query->whereHas('tp', function($query){
                $query->whereHas('cp', function($query){
                    $query->whereHas('pembelajaran', function($query){
                        $query->where('mata_pelajaran_id', request()->mata_pelajaran_id);
                        $query->where($this->kondisiPembelajaran());
                    });
                });
            });
        } else {
            $query->whereHas('kd', function($query){
                $query->whereHas('pembelajaran', function($query){
                    $query->where('mata_pelajaran_id', request()->mata_pelajaran_id);
                    $query->where($this->kondisiPembelajaran());
                });
            });
        }
    }
    private function kondisiPembelajaran(){
        return function($query){
            $query->where('guru_id', request()->guru_id);
            if(request()->rombongan_belajar_id){
                $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            }
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->where('mata_pelajaran_id', '<>', '800001000');
            //$query->whereNull('induk_pembelajaran_id');
            $query->orWhere('guru_pengajar_id', request()->guru_id);
            if(request()->rombongan_belajar_id){
                $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            }
            $query->whereNotNull('kelompok_id');
            $query->whereNotNull('no_urut');
            $query->where('mata_pelajaran_id', '<>', '800001000');
            //$query->whereNull('induk_pembelajaran_id');
        };
    }
    public function get_nilai_akhir(){
        $get_mapel_agama = filter_agama_siswa(request()->pembelajaran_id, request()->rombongan_belajar_id);
        $pembelajaran = Pembelajaran::find(request()->pembelajaran_id);
        $kompetensi_id = (request()->merdeka) ? 4 : 1;
        if(request()->mata_pelajaran_id !='800001000'){
            $sub_mapel = Pembelajaran::where(function($query){
                $query->where('induk_pembelajaran_id', request()->pembelajaran_id);
                $query->whereNotNull('kelompok_id');
                $query->whereNotNull('no_urut');
            })->get();
            if($sub_mapel->count()){
                $kompetensi_id = 99;
            }
        }
        $get_siswa = PesertaDidik::where(function($query) use ($get_mapel_agama){
            if($get_mapel_agama){
                $query->where('agama_id', $get_mapel_agama);
            }
        })->withWhereHas('anggota_rombel', function($query) use ($kompetensi_id, $pembelajaran){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            $query->with([
                'capaian_kompeten' => function($query){
                    $this->wherehas($query);
                },
                'tp_kompeten' => function($query){
                    $this->wherehas($query);
                },
                'tp_inkompeten' => function($query){
                    $this->wherehas($query);
                },
                'nilai_tp' => function($query){
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'nilai_sumatif_semester' => function($query){
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'nilai_akhir_mapel' => function($query) use ($kompetensi_id){
                    $query->where('kompetensi_id', $kompetensi_id);
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'nilai_akhir_kurmer' => function($query){
                    $query->where('kompetensi_id', 4);
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'nilai_akhir_induk' => function($query){
                    $query->where('kompetensi_id', 99);
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
            ]);
            $query->withAvg(['nilai_tp' => function($query){
                $query->where('pembelajaran_id', request()->pembelajaran_id);
            }], 'nilai');
        })->orderBy('nama')->get();
        $bobot_sumatif_materi = $pembelajaran->bobot_sumatif_materi;
        $bobot_sumatif_akhir = $pembelajaran->bobot_sumatif_akhir;
        $total_bobot = $bobot_sumatif_materi + $bobot_sumatif_akhir;
        $data_siswa = [];
        foreach($get_siswa as $siswa){
            $nilai_sumatif_materi = number_format($siswa->anggota_rombel->nilai_tp_avg_nilai, 2);
            $nilai_sumatif_semester = ($siswa->anggota_rombel->nilai_sumatif_semester) ? number_format($siswa->anggota_rombel->nilai_sumatif_semester->nilai, 2) : 0;
            $nilai_akhir = collect([$nilai_sumatif_materi, $nilai_sumatif_semester]);
            $nilai_asesmen = NULL;
            if($nilai_akhir->avg()){
                $nilai_asesmen = number_format(($bobot_sumatif_materi * $nilai_sumatif_materi / $total_bobot) + ($bobot_sumatif_akhir * $nilai_sumatif_semester / $total_bobot) , 0);
            }
            $nilai_akhir_jadi = NULL;
            if($siswa->anggota_rombel->nilai_akhir_mapel){
                $nilai_akhir_jadi = $siswa->anggota_rombel->nilai_akhir_mapel->nilai;
            } elseif($siswa->anggota_rombel->nilai_akhir_kurmer){
                $nilai_akhir_jadi = $siswa->anggota_rombel->nilai_akhir_kurmer->nilai;
            } elseif($siswa->anggota_rombel->nilai_akhir_induk){
                $nilai_akhir_jadi = $siswa->anggota_rombel->nilai_akhir_induk->nilai;
            }
            $data_siswa[] = [
                'nama' => $siswa->nama,
                'photo' => $siswa->photo,
                'nisn' => $siswa->nisn,
                'anggota_rombel_id' => $siswa->anggota_rombel->anggota_rombel_id,
                'pembelajaran_id' => request()->pembelajaran_id,
                'kompetensi_id' => $kompetensi_id,
                'nilai_akhir' => $nilai_akhir_jadi,
                'nilai_akhir_kurmer' => ($siswa->anggota_rombel->nilai_akhir_kurmer) ? $siswa->anggota_rombel->nilai_akhir_kurmer->nilai : NULL,
                'nilai_akhir_induk' => ($siswa->anggota_rombel->nilai_akhir_induk) ? $siswa->anggota_rombel->nilai_akhir_induk->nilai : NULL,
                'nilai_asesmen' => $nilai_asesmen,
                'tp_kompeten' => $siswa->anggota_rombel->tp_kompeten,
                'tp_inkompeten' => $siswa->anggota_rombel->tp_inkompeten,
                'capaian_kompeten' => $siswa->anggota_rombel->capaian_kompeten,
            ];
        }
        $data = [
            'data_siswa' => $data_siswa,
            'data_tp' => TujuanPembelajaran::where(function($query){
                $query->whereHas('tp_mapel', function($query){
                    $query->where('tp_mapel.pembelajaran_id', request()->pembelajaran_id);
                });
                if(request()->merdeka){
                    $query->whereHas('cp', function($query){
                        $query->where('mata_pelajaran_id', request()->mata_pelajaran_id);
                    });
                } else {
                    $query->whereHas('kd', function($query){
                        $query->where('mata_pelajaran_id', request()->mata_pelajaran_id);
                    });
                }
            })->orderBy('created_at')->get(),
            'pembelajaran' => $pembelajaran,
        ];
        return response()->json($data);
    }
    public function get_capaian_kompetensi(){
        $callback = function($query){
            $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            $query->with([
                'nilai_akhir_mapel' => function($query){
                    if(request()->merdeka){
                        $query->where('kompetensi_id', 4);
                    } else {
                        $query->where('kompetensi_id', 1);
                    }
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'single_deskripsi_mata_pelajaran' => function($query){
                    $query->where('pembelajaran_id', request()->pembelajaran_id);
                },
                'tp_kompeten' => function($query){
                    $this->wherehas($query);
                    $query->withWhereHas('tp');
                },
                'tp_inkompeten' => function($query){
                    $this->wherehas($query);
                    $query->withWhereHas('tp');
                },
            ]);
        };
        $data_siswa = [];
        if(request()->pembelajaran_id){
            $get_mapel_agama = filter_agama_siswa(request()->pembelajaran_id, request()->rombongan_belajar_id);
            $data_siswa = PesertaDidik::where(function($query) use ($get_mapel_agama, $callback){
                $query->whereHas('anggota_rombel', $callback);
                if($get_mapel_agama){
                    $query->where('agama_id', $get_mapel_agama);
                }
            })->with(['anggota_rombel' => $callback])->orderBy('nama')->get();
        }
        $data = [
            'data_siswa' => $data_siswa,
            'show_reset' => DeskripsiMataPelajaran::where('pembelajaran_id', request()->pembelajaran_id)->count(),
        ];
        return response()->json($data);
    }
    public function nilai_sikap(){
        if(request()->route('id')){
            $data = NilaiBudayaKerja::with(['anggota_rombel' => function($query){
                $query->with(['rombongan_belajar', 'peserta_didik']);
            }])->find(request()->route('id'));
            return response()->json($data);
        } else {
            $data = NilaiBudayaKerja::withWhereHas('anggota_rombel', function($query){
                $query->whereHas('peserta_didik');
                $query->whereHas('rombongan_belajar');
                $query->with(['rombongan_belajar', 'peserta_didik']);
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
            })->where(function($query){
                $query->where('guru_id', request()->guru_id);
                $query->whereNotNull('deskripsi');
            })->with(['budaya_kerja', 'elemen_budaya_kerja'])
            ->orderBy(request()->sortby, request()->sortbydesc)
            ->when(request()->q, function($query) {
                $query->where('deskripsi', 'ILIKE', '%' . request()->q . '%');
                $query->orWhereHas('anggota_rombel', function($query){
                    $query->whereHas('peserta_didik', function($query){
                        $query->where('nama', 'ILIKE', '%' . request()->q . '%');
                        $query->orWhere('nisn', 'ILIKE', '%' . request()->q . '%');
                    });
                    $query->orWhereHas('rombongan_belajar', function($query){
                        $query->where('nama', 'ILIKE', '%' . request()->q . '%');
                    });
                });
            })->paginate(request()->per_page);
            return response()->json([
                'status' => 'success',
                'data' => $data,
                'search' => request()->q,
                'kurtilas' => RombonganBelajar::where(function($query){
                    $query->whereHas('kurikulum', function($query){
                        $query->where('nama_kurikulum', 'ILIKE', '%2013%');
                    });
                    $query->where('semester_id', request()->semester_id);
                    $query->where('sekolah_id', request()->sekolah_id);
                })->first(),
            ]);
        }
    }
    public function upload_nilai(){
        request()->validate(
            [
                'template_excel' => 'mimes:xlsx', // 1MB Max
            ],
            [
                'template_excel.mimes' => 'File harus berupa file dengan ekstensi: xlsx.',
            ]
        );
        $list = [];
        $collection = [];
        $file_path = request()->template_excel->store('files', 'public');
        if(request()->opsi == 'sumatif-lingkup-materi'){
            $data_tp = TujuanPembelajaran::where(function($query){
                $query->whereHas('tp_mapel', function($query){
                    $query->where('tp_mapel.pembelajaran_id', request()->pembelajaran_id);
                });
            })->orderBy('created_at')->get();
            $list = [];
            $collection = (new NilaiSumatifLingkupMateri())->toCollection(storage_path('/app/public/'.$file_path));
            
            foreach($collection as $rows){
                foreach ($rows as $row) {
                    $nilai = [];
                    foreach($data_tp as $index => $tp){
                        $nilai[] = [
                            'angka' => $row['tp_'.($index + 1)],
                            'tp' => $row[str_replace('-', '_', $tp->tp_id)],
                        ];
                    }
                    $list[] = [
                        'anggota_rombel_id' => $row['pd_id'],
                        'nilai' => $nilai,
                    ];
                }
            }
        } elseif(request()->opsi == 'sumatif-akhir-semester'){
            $list = [];
            $collection = (new NilaiSumatifAkhirSemester())->toCollection(storage_path('/app/public/'.$file_path));
            
            foreach($collection as $rows){
                foreach ($rows as $row) {
                    $list[] = [
                        'anggota_rombel_id' => $row['pd_id'],
                        'nilai_non_tes' => $row['nilai_non_tes'],
                        'nilai_tes' => $row['nilai_tes'],
                    ];
                }
            }
        } else {
            $collection = (new NilaiAkhirImport(request()->rombongan_belajar_id, request()->pembelajaran_id, request()->sekolah_id, request()->merdeka))->toCollection(storage_path('/app/public/'.$file_path));
            if($collection->count() == 1){
                $this->simpan_nilai_import($collection->first());
                $data = [
                    'color' => 'success',
                    'title' => 'Berhasil!',
                    'text' => 'Nilai Akhir berhasil disimpan',
                ];
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Format Import salah. Silahkan Unduh Template ulang!',
                ];
            }
            Storage::disk('public')->delete($file_path);
            return response()->json($data);
        }
        Storage::disk('public')->delete($file_path);
        
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Nilai Akhir berhasil disimpan',
            'collection' => $collection,
            'data_nilai' => $list,
        ];
        return response()->json($data);
    }
    private function simpan_nilai_import($rows){
        if(is_bool(request()->merdeka)){
            $merdeka = request()->merdeka;
        } else {
            $merdeka = (request()->merdeka == 'true') ? TRUE : FALSE;
        }
        foreach ($rows as $row){
            if($row[0]){
                if(is_numeric($row[4])) {
                    $a = NilaiAkhir::updateOrCreate(
                        [
                            'sekolah_id' => request()->sekolah_id,
                            'anggota_rombel_id' => $row[1],
                            'pembelajaran_id' => request()->pembelajaran_id,
                            'kompetensi_id' => ($merdeka) ? 4 : 1,
                        ],
                        [
                            'nilai' => ($row[4] >= 0 && $row[4] <= 100) ? number_format($row[4], 0) : 0,
                        ]
                    );
                }
                $this->insertTpNilai($row, $merdeka);
            } else {
                $this->insertTpNilai($row, $merdeka);
            }
        }
    }
    private function insertTpNilai($row, $merdeka){
        $tp = TujuanPembelajaran::find($row[5]);
        if ($merdeka) {
            $update = [
                'cp_id' => $tp->cp_id,
                'last_sync' => now()
            ];
        } else {
            $update = [
                'kd_id' => $tp->kd_id,
                'last_sync' => now()
            ];
        }
        if(!is_null($row[6])){
            TpNilai::updateOrCreate(
                [
                    'sekolah_id' => request()->sekolah_id,
                    'anggota_rombel_id' => $row[1],
                    'tp_id' => $row[5],
                    'kompeten' => $row[6],
                ],
                $update
            );
        } else {
            TpNilai::where('anggota_rombel_id', $row[1])->where('tp_id', $row[5])->delete();
        }
    }
}
