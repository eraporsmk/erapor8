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
use Carbon\Carbon;
use PDF;

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
	public function rapor_cover(Request $request){
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
			$pdf->getMpdf()->SetFooter($general_title.'|{PAGENO}|Dicetak dari '.config('site.app_name').' v.'.get_setting('app_version'));
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
		$get_siswa = PesertaDidik::whereHas('anggota_rombel', function($query){
			$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
		})->with([
			'sekolah',
			'anggota_rombel' => function($query){
				$query->where('anggota_rombel_id', request()->route('anggota_rombel_id'));
				$query->with(['rombongan_belajar', 'prestasi']);
			}
		])->first();
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
		$general_title = strtoupper($get_siswa->nama).' - '.$get_siswa->anggota_rombel->rombongan_belajar->nama;
		$pdf->getMpdf()->SetFooter($general_title.'| |Dicetak dari '.config('app.name').' v.'.get_setting('app_version'));
		$rapor_pendukung = view('cetak.rapor_pendukung', $params);
		$pdf->getMpdf()->WriteHTML($rapor_pendukung);
		return $pdf->stream($general_title.'-LAMPIRAN.pdf');
	}
}
