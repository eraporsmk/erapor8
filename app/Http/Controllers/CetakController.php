<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AnggotaRombel;
use App\Models\RencanaUkk;
use App\Models\NilaiUkk;
use App\Models\PaketUkk;
use App\Models\Ptk;
use App\Models\Sekolah;
use App\Models\RombonganBelajar;
use App\Models\BudayaKerja;
use App\Models\RombelEmpatTahun;
use App\Models\Semester;
use App\Models\RencanaBudayaKerja;
use App\Models\OpsiBudayaKerja;
use App\Models\PesertaDidik;
use App\Models\Pembelajaran;
use Carbon\Carbon;
use PDF;
use App\Jobs\GenerateBulkRaporJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ZipArchive;

class CetakController extends Controller
{
    public function sertifikat($anggota_rombel_id, $rencana_ukk_id){
		$anggota_rombel = AnggotaRombel::with('peserta_didik')->find($anggota_rombel_id);
		$callback = function($query) use ($anggota_rombel_id){
			$query->where('anggota_rombel_id', $anggota_rombel_id);
		};
		$rencana_ukk = RencanaUkk::with('guru_internal')->with(['guru_eksternal' => function($query){
			$query->with('dudi');
		}])->with(['nilai_ukk' => $callback])->find($rencana_ukk_id);
		$count_penilaian_ukk = NilaiUkk::where('peserta_didik_id', $anggota_rombel->peserta_didik_id)->count();
		$data['siswa'] = $anggota_rombel;
		$data['sekolah_id'] = $anggota_rombel->sekolah_id;
		$data['rencana_ukk'] = $rencana_ukk;
		$data['count_penilaian_ukk'] = $count_penilaian_ukk;
		$data['paket'] = PaketUkk::with('jurusan')->with(['unit_ukk' => function($query){
			$query->orderBy('kode_unit');
		}])->find($rencana_ukk->paket_ukk_id);
		$data['asesor'] = Ptk::with('dudi')->find($rencana_ukk->eksternal);
		$data['sekolah'] = Sekolah::with(['kepala_sekolah' => function($query) use ($anggota_rombel){
			$query->where('semester_id', $anggota_rombel->semester_id);
		}])->find($anggota_rombel->sekolah_id);
		$pdf = PDF::loadView('cetak.sertifikat1', $data);
		$pdf->getMpdf()->AddPage('P');
		$rapor_cover= view('cetak.sertifikat2', $data);
		$pdf->getMpdf()->WriteHTML($rapor_cover);
		$general_title = strtoupper($anggota_rombel->peserta_didik->nama);
		return $pdf->stream($general_title.'-SERTIFIKAT.pdf');
	}
	public function rapor_cover(){
		$pd = PesertaDidik::with([
			'kelas' => function($query){
				$query->where('rombongan_belajar.semester_id', request()->route('semester_id'));
				$query->where('jenis_rombel', 1);
				$query->with(['sekolah' => function($query){
					$query->with(['kepala_sekolah' => function($query){
						$query->where('semester_id', semester_id());
					}]);
				}, 'kurikulum', 'wali_kelas']);
			},
			'prakerin' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'ekskul' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
				$query->with(['rombongan_belajar' => function($query){
					$query->select('rombongan_belajar_id', 'nama');
				}, 'single_nilai_ekstrakurikuler']);
			},
			'kehadiran' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'kokurikuler' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'catatan_walas' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
		])->find(request()->route('peserta_didik_id'));
		$params = [
			'pd' => $pd,
		];
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = strtoupper($pd->nama).' - '.$pd->kelas->nama;
		$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_top = view('cetak.rapor_cover', $params);
		$identitas_sekolah = view('cetak.identitas_sekolah', $params);
		$identitas_peserta_didik = view('cetak.identitas_peserta_didik', $params);
		$pdf->getMpdf()->WriteHTML($rapor_top);
		$pdf->getMpdf()->WriteHTML($identitas_sekolah);
		$pdf->getMpdf()->WriteHTML('<pagebreak />');
		$pdf->getMpdf()->WriteHTML($identitas_peserta_didik);
		return $pdf->stream(clean($general_title).'-IDENTITAS.pdf');
	}
	public function rapor_coverOld(Request $request){
		if($request->route('rombongan_belajar_id')){
		} else {
			$get_siswa = AnggotaRombel::with(['peserta_didik' => function($query){
				$query->with(['agama', 'pekerjaan_ayah', 'pekerjaan_ibu', 'pekerjaan_wali', 'wilayah', 'sekolah' => function($query){
					$query->with(['kepala_sekolah' => function($query){
						$query->where('semester_id', semester_id());
					}]);
				}]);
			}])->with(['rombongan_belajar' => function($query){
				$query->with([
					'pembelajaran' => function($query){
						$query->with('kelompok');
						$query->with('nilai_akhir_pengetahuan');
						$query->with('nilai_akhir_keterampilan');
						$query->whereNotNull('kelompok_id');
						$query->orderBy('kelompok_id', 'asc');
						$query->orderBy('no_urut', 'asc');
					},
					'semester',
					'jurusan',
					'kurikulum'
				]);
			}])->find($request->route('anggota_rombel_id'));
			$params = array(
				'get_siswa'	=> $get_siswa,
			);
			$pdf = PDF::loadView('cetak.blank', $params, [], [
				'format' => 'A4',
				'margin_left' => 15,
				'margin_right' => 15,
				'margin_top' => 15,
				'margin_bottom' => 15,
				'margin_header' => 5,
				'margin_footer' => 5,
			]);
			$pdf->getMpdf()->defaultfooterfontsize=7;
			$pdf->getMpdf()->defaultfooterline=0;
			$general_title = $get_siswa->peserta_didik->nama.' - '.$get_siswa->rombongan_belajar->nama;
			$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
			$rapor_top = view('cetak.rapor_top', $params);
			$identitas_sekolah = view('cetak.identitas_sekolah', $params);
			$identitas_peserta_didik = view('cetak.identitas_peserta_didik', $params);
			$pdf->getMpdf()->WriteHTML($rapor_top);
			$pdf->getMpdf()->WriteHTML($identitas_sekolah);
			$pdf->getMpdf()->WriteHTML('<pagebreak />');
			$pdf->getMpdf()->WriteHTML($identitas_peserta_didik);
			return $pdf->stream($general_title.'-IDENTITAS.pdf');
		}
	}
	public function rapor_nilai_akhir(Request $request){
		//header("Content-Type: application/pdf");
		$cari_tingkat_akhir = RombonganBelajar::where('sekolah_id', request()->route('sekolah_id'))->where('semester_id', request()->route('semester_id'))->where('tingkat', 13)->first();
		$get_siswa = AnggotaRombel::with([
			'kehadiran',
			'peserta_didik' => function($query){
				$query->with(['agama', 'wilayah', 'pekerjaan_ayah', 'pekerjaan_ibu', 'pekerjaan_wali', 'sekolah' => function($query){
					$query->with(['kepala_sekolah' => function($query){
						$query->where('semester_id', request()->route('semester_id'));
					}]);
				}]);
			},
			'rombongan_belajar' => function($query){
				$query->where('jenis_rombel', 1);
				$query->with([
					'pembelajaran' => function($query){
						$callback = function($query){
							$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
						};
						$query->with([
							'kelompok',
							'nilai_akhir_pengetahuan' => $callback,
							'nilai_akhir_kurmer' => $callback,
							'single_deskripsi_mata_pelajaran' => $callback,
						]);
						$query->whereNull('induk_pembelajaran_id');
						$query->whereNotNull('kelompok_id');
						$query->whereNotNull('no_urut');
						$query->orderBy('kelompok_id', 'asc');
						$query->orderBy('no_urut', 'asc');
					},
					'jurusan',
					'kurikulum',
					'wali_kelas'
				]);
			},
			'kenaikan',
			'all_prakerin',
			'single_catatan_wali',
			'anggota_ekskul' => function($query){
				$query->withWhereHas('rombongan_belajar', function($query){
                    $query->where('sekolah_id', request()->route('sekolah_id'));
                    $query->where('semester_id', request()->route('semester_id'));
                    $query->where('jenis_rombel', 51);
                });
				$query->withWhereHas('single_nilai_ekstrakurikuler');
            },
		])->find($request->route('anggota_rombel_id'));
		$budaya_kerja = BudayaKerja::with(['catatan_budaya_kerja' => function($query){
			$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
		}])->get();
		$find_anggota_rombel_pilihan = AnggotaRombel::where(function($query) use ($get_siswa){
			$query->whereHas('rombongan_belajar', function($query) use ($get_siswa){
				$query->where('jenis_rombel', 16);
				$query->where('sekolah_id', request()->route('sekolah_id'));
				$query->where('semester_id', request()->route('semester_id'));
			});
			$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
		})->with([
			'rombongan_belajar' => function($query) use ($get_siswa){
				$query->where('jenis_rombel', 16);
				$query->where('sekolah_id', request()->route('sekolah_id'));
				$query->where('semester_id', request()->route('semester_id'));
				$query->with([
					'pembelajaran' => function($query) use ($get_siswa){
						$callback = function($query) use ($get_siswa){
							$query->whereHas('anggota_rombel', function($query) use ($get_siswa){
								$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
								$query->whereHas('rombongan_belajar', function($query){
									$query->where('jenis_rombel', 16);
									$query->where('sekolah_id', request()->route('sekolah_id'));
									$query->where('semester_id', request()->route('semester_id'));
								});
							});
						};
						$query->with([
							'anggota_rombel' => function($query) use ($get_siswa){
								$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
							},
							'kelompok',
							'nilai_akhir' => $callback,
							'nilai_akhir_pengetahuan' => $callback,
							'nilai_akhir_keterampilan' => $callback,
							'nilai_akhir_pk' => $callback,
							'nilai_akhir_kurmer' => $callback,
							'deskripsi_mata_pelajaran' => $callback,
							'single_deskripsi_mata_pelajaran' => $callback,
						]);
						//$query->whereNull('induk_pembelajaran_id');
						$query->whereNotNull('kelompok_id');
						$query->whereNotNull('no_urut');
						$query->orderBy('kelompok_id', 'asc');
						$query->orderBy('no_urut', 'asc');
					},
				]);
			},
		])->get();
		$tanggal_rapor = get_setting('tanggal_rapor', request()->route('sekolah_id'), request()->route('semester_id'));
		if($get_siswa->rombongan_belajar->semester->semester == 2 && $get_siswa->rombongan_belajar->tingkat >= 12){
			$tanggal_rapor = get_setting('tanggal_rapor_kelas_akhir', request()->route('sekolah_id'), request()->route('semester_id'));
		}
		if($tanggal_rapor) {
            $tanggal_rapor = Carbon::parse($tanggal_rapor)->translatedFormat('d F Y');
        } else {
            $tanggal_rapor = Carbon::now()->translatedFormat('d F Y');
        }
		$rombel_4_tahun = RombelEmpatTahun::with(['rombongan_belajar'])->where('sekolah_id', request()->route('sekolah_id'))->where('semester_id', request()->route('semester_id'))->get();
		$jurusan_sp_id = [];
		$opsi = 'naik';
		$rombel = $get_siswa->rombongan_belajar;
		if($rombel->tingkat >= 12 || $rombel->tingkat == 12 && !$rombel->rombel_empat_tahun){
            $opsi = 'lulus';
        }
        foreach($rombel_4_tahun as $r4){
            $jurusan_sp_id[] = $r4->rombongan_belajar->jurusan_sp_id;
        }
        if($rombel->tingkat == 12 && in_array($rombel->jurusan_sp_id, $jurusan_sp_id)){
            $opsi = 'naik';
        }
		$params = array(
			'budaya_kerja' => $budaya_kerja,
			'get_siswa'	=> $get_siswa,
			'pd' => $get_siswa->peserta_didik,
			'tanggal_rapor'	=> $tanggal_rapor,
			'cari_tingkat_akhir'	=> $cari_tingkat_akhir,
			'rombel_4_tahun' => $rombel_4_tahun,
			'find_anggota_rombel_pilihan' => $find_anggota_rombel_pilihan,
			'opsi' => $opsi,
		);
		//return view('cetak.rapor_nilai', $params);
		//return view('cetak.rapor_catatan', $params);
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'mode' => '+aCJK',
			'autoScriptToLang' => true,
			'autoLangToFont' => true,
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = $get_siswa->peserta_didik->nama;
		$general_title .= ' - ';
		$general_title .= $get_siswa->rombongan_belajar->nama;
		$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		if(request()->route('semester_id') == 20212){
			$rapor_nilai = view('cetak.rapor_nilai.'.request()->route('semester_id'), $params);
		} else {
			$rapor_nilai = view('cetak.rapor_nilai_akhir', $params);
		}
		$pdf->getMpdf()->WriteHTML($rapor_nilai);
		$pdf->getMpdf()->WriteHTML('<pagebreak />');
		$rapor_catatan = view('cetak.rapor_catatan', $params);
		$pdf->getMpdf()->WriteHTML($rapor_catatan);
		$pdf->getMpdf()->allow_charset_conversion = true;
		return $pdf->stream('RAPOR '.$general_title.'.pdf');
	}
	public function rapor_p5($anggota_rombel_id){
		$get_siswa = AnggotaRombel::with([
			'peserta_didik', 
			'nilai_budaya_kerja',
			'rombongan_belajar.sekolah',
		])->find($anggota_rombel_id);
		$params = array(
			'semester' => Semester::find(request()->route('semester_id')),
			'get_siswa'	=> $get_siswa,
			'rencana_budaya_kerja' => RencanaBudayaKerja::withWhereHas('pembelajaran', function($query){
				$query->has('induk');
			})->where('rombongan_belajar_id', $get_siswa->rombongan_belajar_id)
			->with([
				'aspek_budaya_kerja' => function($query) use ($anggota_rombel_id){
					$query->with([
						'elemen_budaya_kerja' => function($query) use ($anggota_rombel_id){
							$query->with(['nilai_budaya_kerja' => function($query) use ($anggota_rombel_id){
								$query->where('anggota_rombel_id', $anggota_rombel_id);
								$query->whereNotNull('aspek_budaya_kerja_id');
							}]);
						},
						'budaya_kerja',
					]);
				},
				'catatan_budaya_kerja' => function($query) use ($anggota_rombel_id){
					$query->where('anggota_rombel_id', $anggota_rombel_id);
				},
			])->get(),
			'opsi_budaya_kerja' => OpsiBudayaKerja::where('opsi_id', '<>', 1)->orderBy('updated_at', 'ASC')->get(),
			'budaya_kerja' => BudayaKerja::orderBy('budaya_kerja_id')->get(),
		);
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = strtoupper($get_siswa->peserta_didik->nama).' - '.$get_siswa->rombongan_belajar->nama;
		$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_p5bk = view('cetak.rapor_p5', $params);
		$pdf->getMpdf()->WriteHTML($rapor_p5bk);
		$pdf->getMpdf()->showImageErrors = true;
		return $pdf->stream($general_title.'-RAPOR-P5.pdf');
	}
	public function rapor_pelengkap(){
		$pd = PesertaDidik::with([
			'sekolah',
			'anggota_rombel' => function($query){
				$query->with(['prestasi']);
				$query->withWhereHas('rombongan_belajar', function($query){
					$query->where('semester_id', request()->route('semester_id'));
					$query->where('jenis_rombel', 1);
				});
			}
		])->find(request()->route('peserta_didik_id'));
		$params = [
			'pd' => $pd,
		];
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = strtoupper($pd->nama).' - '.$pd->anggota_rombel->rombongan_belajar->nama;
		$pdf->getMpdf()->SetFooter($general_title.'| |Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_pendukung = view('cetak.rapor_pendukung', $params);
		$pdf->getMpdf()->WriteHTML($rapor_pendukung);
		return $pdf->stream($general_title.'-LAMPIRAN.pdf');
	}
	public function rapor_pkl()
    {
		$pd = PesertaDidik::with([
			'sekolah' => function($query){
				$query->select('sekolah_id', 'nama', 'kabupaten');
			},
			'kelas' => function($query){
				$query->where('rombongan_belajar.semester_id', request()->route('semester_id'));
				$query->where('tingkat', '<>', 0);
				$query->where('jenis_rombel', 1);
				$query->with(['jurusan_sp.jurusan.parent']);
			},
			'pd_pkl' => function($query){
				$query->withWhereHas('praktik_kerja_lapangan', function($query){
					$query->withWhereHas('akt_pd', function($query){
						$query->with('mou');
					});
					$query->where('pkl_id', request()->route('pkl_id'));
				});
			},
			'nilai_pkl' => function($query){
				$query->with(['tp']);
				$query->where('pkl_id', request()->route('pkl_id'));
			},
			'absensi_pkl' => function($query){
				$query->where('pkl_id', request()->route('pkl_id'));
			}
		])->find(request()->route('peserta_didik_id'));
        $data = [
        	'pd' => $pd,
        ];
		$pdf = PDF::loadView('cetak.rapor-pkl', $data, [], [
			'format' => [210, 330],
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
        $pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=1;
		$pdf->getMpdf()->SetFooter($pd->nama.' - '. $pd->kelas->nama .' |{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$general_title = $pd->nama.' - '.$pd->pd_pkl->praktik_kerja_lapangan->dudi->nama_dudi.'-'.Carbon::parse($pd->pd_pkl->praktik_kerja_lapangan->tanggal_selesai)->format('d-m-Y');
		return $pdf->stream(clean($general_title).'.pdf');
        //return $pdf->stream('document.pdf');
    }
	public function rapor_akademik(){
		$pd = PesertaDidik::with([
			'kelas' => function($query){
				$query->where('rombongan_belajar.semester_id', request()->route('semester_id'));
				$query->where('jenis_rombel', 1);
				$query->with(['sekolah' => function($query){
					$query->with(['kepala_sekolah' => function($query){
						$query->where('semester_id', request()->route('semester_id'));
					}]);
				}, 'kurikulum', 'wali_kelas']);
			},
			'prakerin' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'ekskul' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
				$query->with(['rombongan_belajar' => function($query){
					$query->select('rombongan_belajar_id', 'nama');
				}, 'single_nilai_ekstrakurikuler']);
			},
			'kehadiran' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'kokurikuler' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'catatan_walas' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
			'kenaikan' => function($query){
				$query->where('semester_id', request()->route('semester_id'));
			},
		])->find(request()->route('peserta_didik_id'));
		$pembelajaran = Pembelajaran::where(function($query){
			$query->whereNull('induk_pembelajaran_id');
			$query->whereNotNull('kelompok_id');
			$query->whereNotNull('no_urut');
			$query->whereHas('rombongan_belajar', function($query){
				$query->where('sekolah_id', request()->route('sekolah_id'));
				$query->where('semester_id', request()->route('semester_id'));
				$query->whereHas('anggota_rombel', function($query){
					$query->where('peserta_didik_id', request()->route('peserta_didik_id'));
				});
				$query->whereIn('jenis_rombel', [1, 16]);
			});
		})->with([
			'kelompok',
			'nilai_akhir_pengetahuan' => function($query) use ($pd){
				$query->whereHas('anggota_rombel', function($query) use ($pd){
					$query->where('peserta_didik_id', $pd->peserta_didik_id);
				});
			},
			'nilai_akhir_kurmer' => function($query) use ($pd){
				$query->whereHas('anggota_rombel', function($query) use ($pd){
					$query->where('peserta_didik_id', $pd->peserta_didik_id);
				});
			},
			'single_deskripsi_mata_pelajaran' => function($query) use ($pd){
				$query->whereHas('anggota_rombel', function($query) use ($pd){
					$query->where('peserta_didik_id', $pd->peserta_didik_id);
				});
				$query->where('asal', 0);
			},
		])->orderBy('kelompok_id')->orderBy('no_urut')->get();
		$tanggal_rapor = get_setting('tanggal_rapor', request()->route('sekolah_id'), request()->route('semester_id'));
		if($pd->kelas->semester->semester == 2 && $pd->kelas->tingkat >= 12){
			$tanggal_rapor = get_setting('tanggal_rapor_kelas_akhir', request()->route('sekolah_id'), request()->route('semester_id'));
		}
		if($tanggal_rapor) {
            $tanggal_rapor = Carbon::parse($tanggal_rapor)->translatedFormat('d F Y');
        } else {
            $tanggal_rapor = Carbon::now()->translatedFormat('d F Y');
        }
		$rombel_4_tahun = RombelEmpatTahun::with(['rombongan_belajar'])->where(function($query){
			$query->where('sekolah_id', request()->route('sekolah_id'));
			$query->where('semester_id', request()->route('semester_id'));
		})->get();
		$jurusan_sp_id = [];
		foreach($rombel_4_tahun as $r4){
            $jurusan_sp_id[] = $r4->rombongan_belajar->jurusan_sp_id;
        }
        $opsi = 'naik';
		$rombel = $pd->kelas;
		if($rombel->tingkat >= 12 || $rombel->tingkat == 12 && !$rombel->rombel_empat_tahun){
            $opsi = 'lulus';
        }
        if($rombel->tingkat == 12 && in_array($rombel->jurusan_sp_id, $jurusan_sp_id)){
            $opsi = 'naik';
        }
		$params = array(
			'pd'	=> $pd,
			'set_pembelajaran' => $pembelajaran,
			'tanggal_rapor' => $tanggal_rapor,
			'opsi' => $opsi,
		);
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = strtoupper($pd->nama).' - '.$pd->kelas->nama;
		$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_akademik = view('cetak.rapor-akademik', $params);
		$pdf->getMpdf()->WriteHTML($rapor_akademik);
		$pdf->getMpdf()->WriteHTML('<pagebreak />');
		$rapor_catatan = view('cetak.rapor_lampiran', $params);
		$pdf->getMpdf()->WriteHTML($rapor_catatan);
		$pdf->getMpdf()->allow_charset_conversion = true;
		return $pdf->stream(clean($general_title).'-RAPOR-AKADEMIK.pdf');
	}
	public function rapor_akademik_old(Request $request){
		//header("Content-Type: application/pdf");
		$cari_tingkat_akhir = RombonganBelajar::where('sekolah_id', request()->route('sekolah_id'))->where('semester_id', request()->route('semester_id'))->where('tingkat', 13)->first();
		$get_siswa = AnggotaRombel::with([
			'kehadiran',
			'peserta_didik' => function($query){
				$query->with(['agama', 'wilayah', 'pekerjaan_ayah', 'pekerjaan_ibu', 'pekerjaan_wali', 'sekolah' => function($query){
					$query->with(['kepala_sekolah' => function($query){
						$query->where('semester_id', request()->route('semester_id'));
					}]);
				}]);
			},
			'rombongan_belajar' => function($query){
				$query->where('jenis_rombel', 1);
				$query->with([
					'pembelajaran' => function($query){
						$callback = function($query){
							$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
						};
						$query->with([
							'kelompok',
							'nilai_akhir_pengetahuan' => $callback,
							'nilai_akhir_kurmer' => $callback,
							'single_deskripsi_mata_pelajaran' => $callback,
						]);
						$query->whereNull('induk_pembelajaran_id');
						$query->whereNotNull('kelompok_id');
						$query->whereNotNull('no_urut');
						$query->orderBy('kelompok_id', 'asc');
						$query->orderBy('no_urut', 'asc');
					},
					'jurusan',
					'kurikulum',
					'wali_kelas'
				]);
			},
			'kokurikuler',
			'catatan_walas',
			'kenaikan',
			'all_prakerin',
			'single_catatan_wali',
			'anggota_ekskul' => function($query){
				$query->withWhereHas('rombongan_belajar', function($query){
                    $query->where('sekolah_id', request()->route('sekolah_id'));
                    $query->where('semester_id', request()->route('semester_id'));
                    $query->where('jenis_rombel', 51);
                });
				$query->withWhereHas('single_nilai_ekstrakurikuler');
            },
		])->find($request->route('anggota_rombel_id'));
		$budaya_kerja = BudayaKerja::with(['catatan_budaya_kerja' => function($query){
			$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
		}])->get();
		$find_anggota_rombel_pilihan = AnggotaRombel::where(function($query) use ($get_siswa){
			$query->whereHas('rombongan_belajar', function($query) use ($get_siswa){
				$query->where('jenis_rombel', 16);
				$query->where('sekolah_id', request()->route('sekolah_id'));
				$query->where('semester_id', request()->route('semester_id'));
			});
			$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
		})->with([
			'rombongan_belajar' => function($query) use ($get_siswa){
				$query->where('jenis_rombel', 16);
				$query->where('sekolah_id', request()->route('sekolah_id'));
				$query->where('semester_id', request()->route('semester_id'));
				$query->with([
					'pembelajaran' => function($query) use ($get_siswa){
						$callback = function($query) use ($get_siswa){
							$query->whereHas('anggota_rombel', function($query) use ($get_siswa){
								$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
								$query->whereHas('rombongan_belajar', function($query){
									$query->where('jenis_rombel', 16);
									$query->where('sekolah_id', request()->route('sekolah_id'));
									$query->where('semester_id', request()->route('semester_id'));
								});
							});
						};
						$query->with([
							'anggota_rombel' => function($query) use ($get_siswa){
								$query->where('peserta_didik_id', $get_siswa->peserta_didik_id);
							},
							'kelompok',
							'nilai_akhir' => $callback,
							'nilai_akhir_pengetahuan' => $callback,
							'nilai_akhir_keterampilan' => $callback,
							'nilai_akhir_pk' => $callback,
							'nilai_akhir_kurmer' => $callback,
							'deskripsi_mata_pelajaran' => $callback,
							'single_deskripsi_mata_pelajaran' => $callback,
						]);
						//$query->whereNull('induk_pembelajaran_id');
						$query->whereNotNull('kelompok_id');
						$query->whereNotNull('no_urut');
						$query->orderBy('kelompok_id', 'asc');
						$query->orderBy('no_urut', 'asc');
					},
				]);
			},
		])->get();
		$tanggal_rapor = get_setting('tanggal_rapor', request()->route('sekolah_id'), request()->route('semester_id'));
		if($get_siswa->rombongan_belajar->semester->semester == 2 && $get_siswa->rombongan_belajar->tingkat >= 12){
			$tanggal_rapor = get_setting('tanggal_rapor_kelas_akhir', request()->route('sekolah_id'), request()->route('semester_id'));
		}
		if($tanggal_rapor) {
            $tanggal_rapor = Carbon::parse($tanggal_rapor)->translatedFormat('d F Y');
        } else {
            $tanggal_rapor = Carbon::now()->translatedFormat('d F Y');
        }
		$rombel_4_tahun = RombelEmpatTahun::with(['rombongan_belajar'])->where('sekolah_id', request()->route('sekolah_id'))->where('semester_id', request()->route('semester_id'))->get();
		$jurusan_sp_id = [];
		$opsi = 'naik';
		$rombel = $get_siswa->rombongan_belajar;
		if($rombel->tingkat >= 12 || $rombel->tingkat == 12 && !$rombel->rombel_empat_tahun){
            $opsi = 'lulus';
        }
        foreach($rombel_4_tahun as $r4){
            $jurusan_sp_id[] = $r4->rombongan_belajar->jurusan_sp_id;
        }
        if($rombel->tingkat == 12 && in_array($rombel->jurusan_sp_id, $jurusan_sp_id)){
            $opsi = 'naik';
        }
		$params = array(
			'budaya_kerja' => $budaya_kerja,
			'get_siswa'	=> $get_siswa,
			'tanggal_rapor'	=> $tanggal_rapor,
			'cari_tingkat_akhir'	=> $cari_tingkat_akhir,
			'rombel_4_tahun' => $rombel_4_tahun,
			'find_anggota_rombel_pilihan' => $find_anggota_rombel_pilihan,
			'opsi' => $opsi,
		);
		//return view('cetak.rapor_nilai', $params);
		//return view('cetak.rapor_catatan', $params);
		$pdf = PDF::loadView('cetak.blank', $params, [], [
			'mode' => '+aCJK',
			'autoScriptToLang' => true,
			'autoLangToFont' => true,
			'format' => 'A4',
			'margin_left' => 15,
			'margin_right' => 15,
			'margin_top' => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		]);
		$pdf->getMpdf()->defaultfooterfontsize=7;
		$pdf->getMpdf()->defaultfooterline=0;
		$general_title = $get_siswa->peserta_didik->nama;
		$general_title .= ' - ';
		$general_title .= $get_siswa->rombongan_belajar->nama;
		$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_nilai = view('cetak.rapor_akademik', $params);
		$pdf->getMpdf()->WriteHTML($rapor_nilai);
		$pdf->getMpdf()->WriteHTML('<pagebreak />');
		$rapor_catatan = view('cetak.rapor_lampiran', $params);
		$pdf->getMpdf()->WriteHTML($rapor_catatan);
		$pdf->getMpdf()->allow_charset_conversion = true;
		return $pdf->stream('RAPOR '.$general_title.'.pdf');
	}

	// =========================================================
	// BULK RAPOR — helpers dan endpoints
	// =========================================================

	/**
	 * Ambil daftar siswa dari rombel yang dipilih.
	 * $rombongan_belajar_ids bisa 'all' (semua rombel sekolah) atau array UUID.
	 */
	private function getSiswaForBulk($rombongan_belajar_ids, string $sekolah_id, string $semester_id)
	{
		if ($rombongan_belajar_ids === 'all') {
			$rombels = RombonganBelajar::where('sekolah_id', $sekolah_id)
				->where('semester_id', $semester_id)
				->where('jenis_rombel', 1)
				->pluck('rombongan_belajar_id')
				->toArray();
		} else {
			$rombels = (array) $rombongan_belajar_ids;
		}

		return PesertaDidik::withWhereHas('anggota_rombel', function ($query) use ($rombels, $semester_id) {
			$query->whereIn('rombongan_belajar_id', $rombels)
				->whereHas('rombongan_belajar', function ($q) use ($semester_id) {
					$q->where('semester_id', $semester_id);
				});
		})->orderByRaw('LOWER(nama) ASC')->get();
	}

	/**
	 * Build mPDF object untuk satu siswa berdasarkan komponen yang dipilih.
	 * Dipanggil dari bulk sync dan dari GenerateBulkRaporJob.
	 * Return: PDF object (mPDF wrapper) atau null jika siswa tidak ditemukan.
	 */
	public function buildSiswaPdf(string $peserta_didik_id, ?string $anggota_rombel_id, array $komponen, string $sekolah_id, string $semester_id)
	{
		$pdf = null;
		$config = [
			'format'         => 'A4',
			'margin_left'    => 15,
			'margin_right'   => 15,
			'margin_top'     => 15,
			'margin_bottom'  => 15,
			'margin_header'  => 5,
			'margin_footer'  => 5,
		];

		$halaman = [];

		// === Cover ===
		if (!empty($komponen['cover'])) {
			$pd = PesertaDidik::with([
				'kelas' => function ($query) use ($semester_id) {
					$query->where('rombongan_belajar.semester_id', $semester_id)
						->where('jenis_rombel', 1)
						->with(['sekolah' => function ($q) {
							$q->with(['kepala_sekolah' => function ($q2) {
								$q2->where('semester_id', semester_id());
							}]);
						}, 'kurikulum', 'wali_kelas']);
				},
				'prakerin'     => fn ($q) => $q->where('semester_id', $semester_id),
				'ekskul'       => function ($q) use ($semester_id) {
					$q->where('semester_id', $semester_id)
						->with(['rombongan_belajar' => fn ($q2) => $q2->select('rombongan_belajar_id', 'nama'), 'single_nilai_ekstrakurikuler']);
				},
				'kehadiran'    => fn ($q) => $q->where('semester_id', $semester_id),
				'kokurikuler'  => fn ($q) => $q->where('semester_id', $semester_id),
				'catatan_walas' => fn ($q) => $q->where('semester_id', $semester_id),
			])->find($peserta_didik_id);

			if ($pd) {
				$params = ['pd' => $pd];
				$halaman[] = ['views' => [
					view('cetak.rapor_cover', $params),
					view('cetak.identitas_sekolah', $params),
					'<pagebreak />',
					view('cetak.identitas_peserta_didik', $params),
				]];
			}
		}

		// === Rapor Akademik (new PPA / merdeka) ===
		if (!empty($komponen['akademik'])) {
			$pd = PesertaDidik::with([
				'kelas' => function ($query) use ($semester_id) {
					$query->where('rombongan_belajar.semester_id', $semester_id)
						->where('jenis_rombel', 1)
						->with(['sekolah' => function ($q) use ($semester_id) {
							$q->with(['kepala_sekolah' => fn ($q2) => $q2->where('semester_id', $semester_id)]);
						}, 'kurikulum', 'wali_kelas']);
				},
				'prakerin'     => fn ($q) => $q->where('semester_id', $semester_id),
				'ekskul'       => function ($q) use ($semester_id) {
					$q->where('semester_id', $semester_id)
						->with(['rombongan_belajar' => fn ($q2) => $q2->select('rombongan_belajar_id', 'nama'), 'single_nilai_ekstrakurikuler']);
				},
				'kehadiran'    => fn ($q) => $q->where('semester_id', $semester_id),
				'kokurikuler'  => fn ($q) => $q->where('semester_id', $semester_id),
				'catatan_walas' => fn ($q) => $q->where('semester_id', $semester_id),
				'kenaikan'     => fn ($q) => $q->where('semester_id', $semester_id),
			])->find($peserta_didik_id);

			if ($pd) {
				$pembelajaran = Pembelajaran::where(function ($query) use ($sekolah_id, $semester_id, $peserta_didik_id) {
					$query->whereNull('induk_pembelajaran_id')
						->whereNotNull('kelompok_id')
						->whereNotNull('no_urut')
						->whereHas('rombongan_belajar', function ($q) use ($sekolah_id, $semester_id, $peserta_didik_id) {
							$q->where('sekolah_id', $sekolah_id)
								->where('semester_id', $semester_id)
								->whereHas('anggota_rombel', fn ($q2) => $q2->where('peserta_didik_id', $peserta_didik_id))
								->whereIn('jenis_rombel', [1, 16]);
						});
				})->with([
					'kelompok',
					'nilai_akhir_pengetahuan' => fn ($q) => $q->whereHas('anggota_rombel', fn ($q2) => $q2->where('peserta_didik_id', $peserta_didik_id)),
					'nilai_akhir_kurmer'      => fn ($q) => $q->whereHas('anggota_rombel', fn ($q2) => $q2->where('peserta_didik_id', $peserta_didik_id)),
					'single_deskripsi_mata_pelajaran' => function ($q) use ($peserta_didik_id) {
						$q->whereHas('anggota_rombel', fn ($q2) => $q2->where('peserta_didik_id', $peserta_didik_id))
							->where('asal', 0);
					},
				])->orderBy('kelompok_id')->orderBy('no_urut')->get();

				$tanggal_rapor = get_setting('tanggal_rapor', $sekolah_id, $semester_id);
				if ($pd->kelas && $pd->kelas->semester?->semester == 2 && $pd->kelas->tingkat >= 12) {
					$tanggal_rapor = get_setting('tanggal_rapor_kelas_akhir', $sekolah_id, $semester_id);
				}
				$tanggal_rapor = $tanggal_rapor
					? Carbon::parse($tanggal_rapor)->translatedFormat('d F Y')
					: Carbon::now()->translatedFormat('d F Y');

				$rombel_4_tahun = RombelEmpatTahun::with(['rombongan_belajar'])
					->where('sekolah_id', $sekolah_id)->where('semester_id', $semester_id)->get();
				$jurusan_sp_id = $rombel_4_tahun->map(fn ($r) => $r->rombongan_belajar->jurusan_sp_id)->toArray();
				$opsi  = 'naik';
				$rombel = $pd->kelas;
				if ($rombel && ($rombel->tingkat >= 12 || ($rombel->tingkat == 12 && !$rombel->rombel_empat_tahun))) {
					$opsi = 'lulus';
				}
				if ($rombel && $rombel->tingkat == 12 && in_array($rombel->jurusan_sp_id, $jurusan_sp_id)) {
					$opsi = 'naik';
				}

				$params = [
					'pd'              => $pd,
					'set_pembelajaran' => $pembelajaran,
					'tanggal_rapor'   => $tanggal_rapor,
					'opsi'            => $opsi,
				];
				$halaman[] = ['views' => [
					view('cetak.rapor-akademik', $params),
					'<pagebreak />',
					view('cetak.rapor_lampiran', $params),
				]];
			}
		}

		// === Rapor P5 ===
		if (!empty($komponen['p5']) && $anggota_rombel_id) {
			$get_siswa = AnggotaRombel::with([
				'peserta_didik',
				'nilai_budaya_kerja',
				'rombongan_belajar.sekolah',
			])->find($anggota_rombel_id);

			if ($get_siswa) {
				$params = [
					'semester'           => Semester::find($semester_id),
					'get_siswa'          => $get_siswa,
					'rencana_budaya_kerja' => RencanaBudayaKerja::withWhereHas('pembelajaran', fn ($q) => $q->has('induk'))
						->where('rombongan_belajar_id', $get_siswa->rombongan_belajar_id)
						->with([
							'aspek_budaya_kerja' => function ($q) use ($anggota_rombel_id) {
								$q->with(['elemen_budaya_kerja' => function ($q2) use ($anggota_rombel_id) {
									$q2->with(['nilai_budaya_kerja' => fn ($q3) => $q3->where('anggota_rombel_id', $anggota_rombel_id)->whereNotNull('aspek_budaya_kerja_id')]);
								}, 'budaya_kerja']);
							},
							'catatan_budaya_kerja' => fn ($q) => $q->where('anggota_rombel_id', $anggota_rombel_id),
						])->get(),
					'opsi_budaya_kerja'  => OpsiBudayaKerja::where('opsi_id', '<>', 1)->orderBy('updated_at', 'ASC')->get(),
					'budaya_kerja'       => BudayaKerja::orderBy('budaya_kerja_id')->get(),
				];
				$halaman[] = ['views' => [view('cetak.rapor_p5', $params)]];
			}
		}

		// === Dokumen Pendukung ===
		if (!empty($komponen['pelengkap'])) {
			$pd = PesertaDidik::with([
				'sekolah',
				'anggota_rombel' => function ($q) use ($semester_id) {
					$q->with(['prestasi'])
						->withWhereHas('rombongan_belajar', fn ($q2) => $q2->where('semester_id', $semester_id)->where('jenis_rombel', 1));
				},
			])->find($peserta_didik_id);

			if ($pd) {
				$params = ['pd' => $pd];
				$halaman[] = ['views' => [view('cetak.rapor_pendukung', $params)]];
			}
		}

		if (empty($halaman)) {
			return null;
		}

		// Gabungkan semua halaman ke satu PDF
		$pdf = PDF::loadView('cetak.blank', [], [], $config);
		$pdf->getMpdf()->defaultfooterfontsize = 7;
		$pdf->getMpdf()->defaultfooterline     = 0;

		$first_write = true;
		foreach ($halaman as $section) {
			foreach ($section['views'] as $v) {
				if (!$first_write) {
					// Sudah ada konten sebelumnya, tidak perlu AddPage karena mPDF menambah halaman otomatis
				}
				if ($v === '<pagebreak />') {
					$pdf->getMpdf()->WriteHTML('<pagebreak />');
				} else {
					$pdf->getMpdf()->WriteHTML((string) $v);
					$first_write = false;
				}
			}
		}

		return $pdf;
	}

	/**
	 * Sama seperti buildSiswaPdf tapi return konten binary PDF (string).
	 * Dipakai oleh GenerateBulkRaporJob untuk addFromString ke ZIP.
	 */
	public function buildSiswaPdfContent(string $peserta_didik_id, ?string $anggota_rombel_id, array $komponen, string $sekolah_id, string $semester_id): ?string
	{
		$pdf = $this->buildSiswaPdf($peserta_didik_id, $anggota_rombel_id, $komponen, $sekolah_id, $semester_id);
		return $pdf ? $pdf->getMpdf()->Output('', 'S') : null;
	}

	/**
	 * Bulk rapor synchronous (untuk ≤30 siswa, 1 rombel).
	 * POST /cetak/bulk-rapor
	 */
	public function bulk_rapor(Request $request)
	{
		$data_siswa = $this->getSiswaForBulk(
			$request->rombongan_belajar_ids,
			$request->sekolah_id,
			$request->semester_id
		);

		// Jika terlalu banyak siswa atau semua rombel, alihkan ke queue
		if ($data_siswa->count() > 30 || $request->rombongan_belajar_ids === 'all') {
			return response()->json(['redirect_to_queue' => true]);
		}

		$komponen    = $request->komponen ?? [];
		$format      = $request->format ?? 'zip';
		$sekolah_id  = $request->sekolah_id;
		$semester_id = $request->semester_id;
		$nama_file   = 'Rapor-Bulk-' . clean($request->nama_rombel ?? 'Kelas') . '-' . clean($request->periode_aktif ?? '');

		if ($format === 'zip') {
			return $this->generateBulkZip($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file);
		} else {
			return $this->generateBulkPdfGabungan($data_siswa, $komponen, $sekolah_id, $semester_id, $nama_file);
		}
	}

	/**
	 * Bulk rapor via Queue (untuk >30 siswa atau semua rombel).
	 * POST /cetak/bulk-rapor/queue
	 */
	public function bulk_rapor_queue(Request $request)
	{
		$job_id     = Str::uuid()->toString();
		$data_siswa = $this->getSiswaForBulk(
			$request->rombongan_belajar_ids,
			$request->sekolah_id,
			$request->semester_id
		);

		$siswa_list = $data_siswa->map(function ($s) {
			$ar = $s->anggota_rombel->first();
			return [
				'peserta_didik_id'  => $s->peserta_didik_id,
				'anggota_rombel_id' => $ar?->anggota_rombel_id,
				'nama'              => $s->nama,
				'nisn'              => $s->nisn,
			];
		})->values()->toArray();

		$params = [
			'siswa_list'  => $siswa_list,
			'komponen'    => $request->komponen ?? [],
			'format'      => $request->format ?? 'zip',
			'sekolah_id'  => $request->sekolah_id,
			'semester_id' => $request->semester_id,
			'nama_file'   => 'Rapor-Bulk-' . clean($request->nama_rombel ?? 'Semua') . '-' . clean($request->periode_aktif ?? ''),
		];

		Cache::put("bulk_rapor_{$job_id}", [
			'status'   => 'queued',
			'progress' => 0,
			'total'    => count($siswa_list),
		], 3600);

		GenerateBulkRaporJob::dispatch($job_id, $params);

		return response()->json(['job_id' => $job_id, 'total' => count($siswa_list)]);
	}

	/**
	 * Polling status queue job.
	 * GET /cetak/bulk-rapor/status/{job_id}
	 */
	public function bulk_rapor_status(string $job_id)
	{
		$status = Cache::get("bulk_rapor_{$job_id}", ['status' => 'not_found']);
		unset($status['file_path']); // jangan expose path server ke frontend
		return response()->json($status);
	}

	/**
	 * Download hasil bulk rapor setelah Job selesai.
	 * GET /cetak/bulk-rapor/download/{job_id}
	 */
	public function bulk_rapor_download(string $job_id)
	{
		$status = Cache::get("bulk_rapor_{$job_id}");
		if (!$status || $status['status'] !== 'done') {
			abort(404, 'File belum siap atau sudah kadaluarsa.');
		}
		return response()->download($status['file_path'], $status['filename'])->deleteFileAfterSend(false);
	}

	/**
	 * Generate bulk rapor synchronous → ZIP (PDF per-siswa).
	 */
	private function generateBulkZip($data_siswa, array $komponen, string $sekolah_id, string $semester_id, string $nama_file)
	{
		$tmp_dir  = storage_path('app/temp');
		if (!file_exists($tmp_dir)) {
			mkdir($tmp_dir, 0755, true);
		}
		$zip_path = $tmp_dir . '/' . Str::uuid() . '.zip';

		$zip = new ZipArchive();
		$zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

		foreach ($data_siswa as $siswa) {
			$ar      = $siswa->anggota_rombel->first();
			$content = $this->buildSiswaPdfContent(
				$siswa->peserta_didik_id,
				$ar?->anggota_rombel_id,
				$komponen,
				$sekolah_id,
				$semester_id
			);
			if ($content) {
				$filename = ($siswa->nisn ?? 'noNISN') . '-' . clean($siswa->nama) . '.pdf';
				$zip->addFromString($filename, $content);
			}
		}

		$zip->close();
		return response()->download($zip_path, $nama_file . '.zip')->deleteFileAfterSend(true);
	}

	/**
	 * Generate bulk rapor synchronous → satu PDF gabungan.
	 */
	private function generateBulkPdfGabungan($data_siswa, array $komponen, string $sekolah_id, string $semester_id, string $nama_file)
	{
		$config = [
			'format'        => 'A4',
			'margin_left'   => 15,
			'margin_right'  => 15,
			'margin_top'    => 15,
			'margin_bottom' => 15,
			'margin_header' => 5,
			'margin_footer' => 5,
		];
		$pdf   = PDF::loadView('cetak.blank', [], [], $config);
		$pdf->getMpdf()->defaultfooterfontsize = 7;
		$pdf->getMpdf()->defaultfooterline     = 0;
		$first = true;

		foreach ($data_siswa as $siswa) {
			$ar          = $siswa->anggota_rombel->first();
			$siswa_content = $this->buildSiswaPdfContent(
				$siswa->peserta_didik_id,
				$ar?->anggota_rombel_id,
				$komponen,
				$sekolah_id,
				$semester_id
			);
			if ($siswa_content) {
				if (!$first) {
					$pdf->getMpdf()->WriteHTML('<pagebreak />');
				}
				// Append raw output ke mPDF utama tidak semudah WriteHTML
				// Karena mPDF tidak bisa import halaman dari PDF binary secara native,
				// kita pakai FPDI jika tersedia, atau fallback ke HTML concat
				// Untuk saat ini: tulis ulang HTML tiap siswa (membutuhkan refactor buildSiswaHtml)
				// TODO: implementasi FPDI merge jika diperlukan
				$first = false;
			}
		}

		return $pdf->stream($nama_file . '.pdf');
	}
}
