<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\RombonganBelajar;
use App\Models\Pembelajaran;
use App\Models\KompetensiDasar;
use App\Models\CapaianPembelajaran;
use App\Models\PesertaDidik;
use App\Models\TujuanPembelajaran;
use App\Exports\TemplateTp;
use App\Exports\TemplateSumatifLingkupMateri;
use App\Exports\TemplateSumatifAkhirSemester;
use App\Exports\TemplateNilaiAkhir;
use Maatwebsite\Excel\Facades\Excel;

class DownloadController extends Controller
{
    public function template_tp(){
        if(request()->route('id')){
			$rombongan_belajar = RombonganBelajar::find(request()->route('rombongan_belajar_id'));
			$pembelajaran = Pembelajaran::find(request()->route('pembelajaran_id'));
			if(Str::isUuid(request()->route('id'))){
				$kd = KompetensiDasar::find(request()->route('id'));
				$nama_file = 'Template TP Mata Pelajaran ' . $pembelajaran->nama_mata_pelajaran . ' Kelas '.$rombongan_belajar->nama;
				$nama_file = clean($nama_file);
				$nama_file = $nama_file . '.xlsx';
				return (new TemplateTp)->query(request()->route('id'), $pembelajaran, $rombongan_belajar)->download($nama_file);
			} else {
				$cp = CapaianPembelajaran::with(['pembelajaran'])->find(request()->route('id'));
				$nama_file = 'Template TP Mata Pelajaran ' . $pembelajaran->nama_mata_pelajaran. ' Kelas '.$rombongan_belajar->nama;
				$nama_file = clean($nama_file);
				$nama_file = $nama_file . '.xlsx';
				return (new TemplateTp)->query(request()->route('id'), $pembelajaran, $rombongan_belajar)->download($nama_file);
			}
		} else {
			echo 'Akses tidak sah!';
		}
    }
	public function template_sumatif_lingkup_materi($pembelajaran_id){
		$pembelajaran = Pembelajaran::with('rombongan_belajar')->find($pembelajaran_id);
		$get_mapel_agama = filter_agama_siswa($pembelajaran->pembelajaran_id, $pembelajaran->rombongan_belajar_id);
		$data_siswa = PesertaDidik::where(function($query) use ($get_mapel_agama){
			if($get_mapel_agama){
				$query->where('agama_id', $get_mapel_agama);
			}
		})->withWhereHas('anggota_rombel', function($query) use ($pembelajaran){
			$query->where('rombongan_belajar_id', $pembelajaran->rombongan_belajar_id);
			$query->with(['nilai_tp' => function($query) use ($pembelajaran){
				$query->where('pembelajaran_id', $pembelajaran->pembelajaran_id);
			}]);
		})->orderBy('nama')->get();
		$data_tp = TujuanPembelajaran::where(function($query) use ($pembelajaran){
			$query->whereHas('tp_mapel', function($query) use ($pembelajaran){
				$query->where('tp_mapel.pembelajaran_id', $pembelajaran->pembelajaran_id);
			});
		})->orderBy('created_at')->get();
		$file = clean('template-nilai-sumatif-lingkup-materi-'.$pembelajaran->nama_mata_pelajaran.'-kelas-'.$pembelajaran->rombongan_belajar->nama);
		$data = [
			'pembelajaran' => $pembelajaran,
			'data_siswa' => $data_siswa,
			'data_tp' => $data_tp,
			'file' => $file,
		];
		return (new TemplateSumatifLingkupMateri($data))->download($file.'.xlsx');
	}
	public function template_sumatif_akhir_semester($pembelajaran_id){
		$pembelajaran = Pembelajaran::with('rombongan_belajar')->find($pembelajaran_id);
		$get_mapel_agama = filter_agama_siswa($pembelajaran->pembelajaran_id, $pembelajaran->rombongan_belajar_id);
        $data_siswa = PesertaDidik::where(function($query) use ($get_mapel_agama){
			if($get_mapel_agama){
				$query->where('agama_id', $get_mapel_agama);
			}
		})->withWhereHas('anggota_rombel', function($query) use ($pembelajaran){
			$query->where('rombongan_belajar_id', $pembelajaran->rombongan_belajar_id);
			$query->with(['nilai_sumatif' => function($query) use ($pembelajaran){
				$query->where('pembelajaran_id', $pembelajaran->pembelajaran_id);
			}]);
		})->orderBy('nama')->get();
		$file = clean('template-nilai-sumatif-akhir-semester-'.$pembelajaran->nama_mata_pelajaran.'-kelas-'.$pembelajaran->rombongan_belajar->nama);
		$data = [
			'pembelajaran' => $pembelajaran,
			'data_siswa' => $data_siswa,
			'file' => $file,
		];
		return (new TemplateSumatifAkhirSemester($data))->download($file.'.xlsx');
	}
	private function wherehas($query, $merdeka){
        if($merdeka){
            $query->whereHas('tp', function($query){
                $query->whereHas('cp', function($query){
                    $query->whereHas('pembelajaran', function($query){
                        $query->where('pembelajaran_id', request()->route('pembelajaran_id'));
                    });
                });
            });
        } else {
            $query->whereHas('kd', function($query){
                $query->whereHas('pembelajaran', function($query){
                    $query->where('pembelajaran_id', request()->route('pembelajaran_id'));
                });
            });
        }
    }
	public function template_nilai_akhir(){
		if(request()->route('pembelajaran_id')){
			$pembelajaran = Pembelajaran::with([
				'rombongan_belajar' => function($query){
					$query->select('rombongan_belajar_id', 'nama', 'kurikulum_id');
					$query->with(['kurikulum' => function($query){
						$query->select('kurikulum_id', 'nama_kurikulum');
					}]);
				},
			])->find(request()->route('pembelajaran_id'));
			$merdeka = merdeka($pembelajaran->rombongan_belajar->kurikulum->nama_kurikulum);
			$kompetensi_id = ($merdeka) ? 4 : 1;
			if($pembelajaran->mata_pelajaran_id !='800001000'){
				$sub_mapel = Pembelajaran::where(function($query) use ($pembelajaran){
					$query->where('induk_pembelajaran_id', $pembelajaran->pembelajaran_id);
					$query->whereNotNull('kelompok_id');
					$query->whereNotNull('no_urut');
				})->get();
				if($sub_mapel->count()){
					$kompetensi_id = 99;
				}
			}
			$get_mapel_agama = filter_agama_siswa($pembelajaran->pembelajaran_id, $pembelajaran->rombongan_belajar_id);
			$data_siswa = PesertaDidik::where(function($query) use ($get_mapel_agama){
				if($get_mapel_agama){
					$query->where('agama_id', $get_mapel_agama);
				}
			})->withWhereHas('anggota_rombel', function($query) use ($merdeka, $kompetensi_id){
				$query->withWhereHas('rombongan_belajar', function($query){
					$query->whereHas('mapel', function($query){
						$query->where('pembelajaran_id', request()->route('pembelajaran_id'));
					});
				});
				$query->with([
					'nilai_akhir_mapel' => function($query) use ($merdeka, $kompetensi_id){
						$query->where('kompetensi_id', $kompetensi_id);
						$query->where('pembelajaran_id', request()->route('pembelajaran_id'));
					},
					'tp_nilai' => function($query) use ($merdeka){
						$this->wherehas($query, $merdeka);
					}
				]);
			})->orderBy('nama')->get();
			$data_tp = TujuanPembelajaran::where(function($query){
				$query->whereHas('tp_mapel', function($query){
					$query->where('tp_mapel.pembelajaran_id', request()->route('pembelajaran_id'));
				});
			})->orderBy('created_at')->get();
			$nama_file = 'Template Nilai Akhir Mata Pelajaran ' . $pembelajaran->nama_mata_pelajaran. ' Kelas '.$pembelajaran->rombongan_belajar->nama;
			$nama_file = clean($nama_file);
			$data = [
				'data_siswa' => $data_siswa, 
				'data_tp' => $data_tp, 
				'pembelajaran' => $pembelajaran,
			];
			$export = new TemplateNilaiAkhir($data);
			return Excel::download($export, $nama_file . '.xlsx');
		} else {
			echo 'Akses tidak sah!';
		}
	}
}
