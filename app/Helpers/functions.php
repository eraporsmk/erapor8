<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Pembelajaran;
use App\Models\Agama;
use App\Models\MstWilayah;
use App\Models\Ptk;
use App\Models\MataPelajaran;
use App\Models\JurusanSp;
use App\Models\Jurusan;
use App\Models\RombonganBelajar;
use App\Models\Kurikulum;
use App\Models\PesertaDidik;
use App\Models\AnggotaRombel;
use App\Models\PdKeluar;
use App\Models\Ekstrakurikuler;
use App\Models\BimbingPd;
use App\Models\AnggotaAktPd;
use App\Models\AktPd;
use App\Models\Mou;
use App\Models\Dudi;
use Carbon\Carbon;

function get_setting($key, $sekolah_id = NULL, $semester_id = NULL){
    $data = Setting::where(function($query) use ($key, $sekolah_id, $semester_id){
        $query->where('key', $key);
        if($sekolah_id){
            $query->where('sekolah_id', $sekolah_id);
        }
        if($semester_id){
            $query->where('semester_id', $semester_id);
        }
    })->first();
    return ($data) ? $data->value : NULL;
}
function semester_id(){
    return Semester::where('periode_aktif', 1)->first()?->semester_id;
}
function jam_sinkron(){
    $timezone = config('app.timezone');
    $start = Carbon::create(date('Y'), date('m'), date('d'), '00', '00', '01', 'Asia/Jakarta');
    $end = Carbon::create(date('Y'), date('m'), date('d'), '03', '00', '00', 'Asia/Jakarta');
    $now = Carbon::now()->timezone($timezone);
    $jam_sinkron = Carbon::now()->timezone($timezone)->isBetween($start, $end, false);
    return $jam_sinkron;
}
function http_client($satuan, $data_sync){
    $response = Http::withOptions([
        'verify' => false,
    ])->withHeaders([
        'x-api-key' => $data_sync['sekolah_id'],
    ])->retry(3, 100)->post(config('erapor.api_url').$satuan, $data_sync);
    return $response->object();
}
function http_dashboard($satuan, $data_sync){
    $response = Http::withOptions([
        'verify' => false,
    ])->retry(3, 100)->post(config('erapor.dashboard_url').$satuan, $data_sync);
    return $response->object();
}
function getMatev($sekolah_id, $npsn, $semester_id){
    $matev_rapor = [];
    $response = Http::withToken(get_setting('token_dapodik', $sekolah_id))->get(get_setting('url_dapodik', $sekolah_id).'/WebService/getMatevNilai?npsn='.$npsn.'&semester_id='.$semester_id.'&a_dari_template=1');
    if($response->successful()){
        $result = $response->object();
        if($result){
            $matev_rapor = collect($result->rows);
        }
    }
    return $matev_rapor;
}
function getUpdaterID($sekolah_id, $npsn, $semester_id, $email){
    $updater_id = NULL;
    try {
        $getPengguna = Http::withToken(get_setting('token_dapodik', $sekolah_id))->get(get_setting('url_dapodik', $sekolah_id).'/WebService/getPengguna?npsn='.$npsn.'&semester_id='.$semester_id);
        if($getPengguna->successful()){
            $users = $getPengguna->object();
            if($users){
                $pengguna = collect($users->rows);
                $user_id = $pengguna->first(function ($value, $key) use ($email){
                    return $value->username == $email;
                });
                $updater_id = $user_id->pengguna_id;
            }
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
    return $updater_id;
}
function table_sync(){
    return [
        'ref.paket_ukk',
        'ref.kompetensi_dasar',
        'ref.capaian_pembelajaran',
        'users',
        'unit_ukk',
        'tujuan_pembelajaran',
        'tp_pkl',
        'tp_nilai',
        'tp_mapel',
        'sekolah',
        'rombongan_belajar',
        'rombel_4_tahun',
        'rencana_ukk',
        'rencana_penilaian',
        'rencana_budaya_kerja',
        'rapor_pts',
        'ptk_keluar',
        'prestasi',
        'praktik_kerja_lapangan',
        'prakerin',
        'peserta_didik',
        'pembelajaran',
        'pd_pkl',
        'pd_keluar',
        'nilai_us',
        'nilai_un',
        'nilai_ukk',
        'nilai_tp',
        'nilai_sumatif',
        'nilai_sikap',
        'nilai_remedial',
        'nilai_rapor',
        'nilai_pts',
        'nilai_pkl',
        'nilai_karakter',
        'nilai_ekstrakurikuler',
        'nilai_budaya_kerja',
        'nilai_akhir',
        'nilai',
        'mou',
        'kewirausahaan',
        'kenaikan_kelas',
        'kd_nilai',
        'kasek',
        'jurusan_sp',
        'guru',
        'gelar_ptk',
        'ekstrakurikuler',
        'dudi',
        'deskripsi_sikap',
        'deskripsi_mata_pelajaran',
        'catatan_wali',
        'catatan_ppk',
        'catatan_budaya_kerja',
        'bobot_keterampilan',
        'bimbing_pd',
        'aspek_budaya_kerja',
        'asesor',
        'anggota_rombel',
        'anggota_kewirausahaan',
        'anggota_akt_pd',
        'akt_pd',
        'absensi_pkl',
        'absensi',
    ];
}
function get_table($table, $sekolah_id, $tahun_ajaran_id, $semester_id, $count = NULL){
    $request = DB::table($table)->where(function($query) use ($table, $sekolah_id, $tahun_ajaran_id, $semester_id){
        if(in_array($table, ['ref.kompetensi_dasar'])){
            $query->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('users')
                      ->whereColumn('ref.kompetensi_dasar.user_id', 'users.user_id');
            });
        }
        if(in_array($table, ['ref.paket_ukk', 'users']) || Schema::hasColumn($table, 'sekolah_id')){
            $query->where('sekolah_id', $sekolah_id);
        }
        if(in_array($table, ['ref.capaian_pembelajaran'])){
            $query->where('is_dir', 0);
        }
        if (Schema::hasColumn($table, 'tahun_ajaran_id')) {
            $query->where('tahun_ajaran_id', $tahun_ajaran_id);
        }
        if (Schema::hasColumn($table, 'semester_id')) {
            $query->where('semester_id', $semester_id);
        }
        if (Schema::hasColumn($table, 'last_sync')) {
            $query->whereRaw('updated_at > last_sync');
        }
    });
    if($count){
        return $request->count();
    } else {
        return $request->get();
    }
}
function nama_table($table){
    $data = str_replace('_', ' ', $table);
    $data = str_replace('ref.', '', $data);
    return ucwords($data);
}
function prepare_send($str){
    return rawurlencode(base64_encode(gzcompress(encryptor(serialize($str)))));
}
function prepare_receive($str){
    return unserialize(decryptor(gzuncompress(base64_decode(rawurldecode($str)))));
}
function encryptor($str){
    return $str;
}
function decryptor($str){
    return $str;
}
function jenis_gtk($query){
    $data['tendik'] = array(11, 30, 40, 41, 42, 43, 44, 57, 58, 59, 91, 93);
    $data['guru'] = array(3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 14, 20, 25, 26, 51, 52, 53, 54, 56, 92);
    $data['instruktur'] = array(97);
    $data['asesor'] = array(98);
    return collect($data[$query]);
}
function merdeka($nama_kurikulum){
    return Str::contains($nama_kurikulum, 'Merdeka');
}
function is_ppa($semester_id){
    return ($semester_id >= 20221);
}
function is_new_ppa($semester_id){
    return ($semester_id >= 20251);
}
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function hasRole($roles, $team){
    return auth()->user()->hasRole($roles, $team);
}
function fase($tingkat){
    $fase = 'F';
    if($tingkat == 10){
        $fase = 'E';
    }
    if($tingkat == 1 || $tingkat == 2){
        $fase = 'A';
    }
    if($tingkat == 3 || $tingkat == 4){
        $fase = 'B';
    }
    if($tingkat == 5 || $tingkat == 6){
        $fase = 'C';
    }
    if($tingkat == 7 || $tingkat == 8 || $tingkat == 9){
        $fase = 'D';
    }
    return $fase;
}
function tingkat($fase){
    $tingkat = [12];
    if($fase == 'A'){
        $tingkat = [1, 2];
    }
    if($fase == 'B'){
        $tingkat = [3, 4];
    }
    if($fase == 'C'){
        $tingkat = [5, 6];
    }
    if($fase == 'D'){
        $tingkat = [7, 8, 9];
    }
    if($fase == 'E'){
        $tingkat = [10];
    }
    return $tingkat;
}
function clean($string){
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
}
function clearing($string){
    $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
    $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    return preg_replace('/-+/', '', $string); // Replaces multiple hyphens with single one.
}
function filter_agama_siswa($pembelajaran_id, $rombongan_belajar_id){
    $ref_agama = Agama::all();
	$agama_id = [];
	foreach ($ref_agama as $agama) {
        $nama_agama = str_replace('Budha', 'Buddha', $agama->nama);
        $agama_id[$agama->agama_id] = $nama_agama;
    }
    $get_mapel = Pembelajaran::with('mata_pelajaran')->find($pembelajaran_id);
    if($get_mapel){
        $nama_mapel = str_replace('Pendidikan Agama', '', $get_mapel->mata_pelajaran->nama);
        $nama_mapel = str_replace('KongHuChu', 'Konghuchu', $nama_mapel);
        $nama_mapel = str_replace('Kong Hu Chu', 'Konghuchu', $nama_mapel);
        $nama_mapel = str_replace('dan Budi Pekerti', '', $nama_mapel);
        $nama_mapel = str_replace('Pendidikan Kepercayaan terhadap', '', $nama_mapel);
        $nama_mapel = str_replace('Tuhan YME', 'Kepercayaan kpd Tuhan YME', $nama_mapel);
        $nama_mapel = trim($nama_mapel);
        $agama_id = array_search($nama_mapel, $agama_id);
    }
    return $agama_id;
}
function keterangan_ukk($n, $lang = 'ID')
{
    if ($lang == 'ID') {
        if (!$n) {
            $predikat 	= '';
        /*} elseif ($n >= 90) {
            $predikat 	= 'Sangat Kompeten';
        } elseif ($n >= 75 && $n <= 89) {
            $predikat 	= 'Kompeten';
        } elseif ($n >= 70 && $n <= 74) {
            $predikat 	= 'Cukup Kompeten';
        } elseif ($n < 70) {
            $predikat 	= 'Belum Kompeten';
        }*/
        /*
        0-69 = Belum Kompeten
        70-100 = Kompeten
        */
        } elseif($n >= 70){
            $predikat 	= 'Kompeten';
        } else {
            $predikat 	= 'Belum Kompeten';
        }
    } else {
        if (!$n) {
            $predikat 	= '';
        /*} elseif ($n >= 90) {
            $predikat 	= 'Highly Competent';
        } elseif ($n >= 75 && $n <= 89) {
            $predikat 	= 'Competent';
        } elseif ($n >= 70 && $n <= 74) {
            $predikat 	= 'Partly Competent';
        } elseif ($n < 70) {
            $predikat 	= 'Not Yet Competent';
        }*/
        } elseif($n >= 70){
            $predikat 	= 'Competent';
        } else {
            $predikat 	= 'Not Yet Competent';
        }
    }
    return $predikat;
}
function get_current_git_commit( $branch='main' ) {
  if ( $hash = file_get_contents(base_path(sprintf( '.git/refs/heads/%s', $branch ))) ) {
    return trim($hash);
  } else {
    return false;
  }
}
function getLastCommit(){
    $url = 'https://api.github.com/repos/eraporsmk/erapor8';
    try {
        $response = Http::withOptions([
            'verify' => false,
        ])->withToken(config('app.github_token'))->get($url);
        $result = $response->object();
        $pushed_at = Carbon::parse($result->pushed_at)->format('Y-m-d H:i:s');
    } catch (\Throwable $th) {
        $pushed_at = NULL;
    }
    return $pushed_at;
}
function getCurrentHead(){
    $url = 'https://api.github.com/repos/eraporsmk/erapor8/commits/'.get_current_git_commit();
    try {
        $response = Http::withOptions([
            'verify' => false,
        ])->withToken(config('app.github_token'))->get($url);
        $result = $response->object();
        $pushed_at = Carbon::parse($result->commit->author->date)->format('Y-m-d H:i:s');
    } catch (\Throwable $th) {
        $pushed_at = NULL;
    }
    return $pushed_at;
}
function cekDiff($last, $head){
    $diff = [
        'invert' => NULL,
        'human' => NULL,
    ];
    if($last && $head){
        $diff = Carbon::parse($head)->diff(Carbon::parse($last));
        $diff = [
            'invert' => $diff->invert,
            'human' => Carbon::parse($head)->diffForHumans(Carbon::parse($last)),
            'second' => Carbon::parse($head)->diffInSeconds(Carbon::parse($last)),
        ];
    }
    return $diff;
}
function cekUpdate(){
    $tersedia = FALSE;
    try {
        $response = Http::post('sync.erapor-smk.net/api/v8/version');
        if($response->successful()){
            $version = $response->object();
            if (version_compare($version->version, get_setting('app_version')) > 0) {
                $tersedia = TRUE;
            }
        }
    } catch (\Throwable $th) {
        //throw $th;
    }
    return $tersedia;
}
function mapel_agama(){
	return ['100014000', '100014140', '100015000', '100015010', '100016000', '100016010', '109011000', '109011010', '100011000', '100011070', '100013000', '100013010', '100012000', '100012050'];
}
function filter_pembelajaran_agama($agama_siswa, $nama_agama){
    $nama_agama = str_replace('Budha', 'Buddha', $nama_agama);
	$nama_agama = str_replace('Pendidikan Agama', '', $nama_agama);
	$nama_agama = str_replace('dan Budi Pekerti', '', $nama_agama);
	$nama_agama = str_replace('Pendidikan Kepercayaan', '', $nama_agama);
	$nama_agama = str_replace('terhadap', 'kpd', $nama_agama);
	$nama_agama = str_replace('KongHuChu', 'Konghuchu', $nama_agama);
	$nama_agama = str_replace('Kong Hu Chu', 'Konghuchu', $nama_agama);
	$nama_agama = trim($nama_agama);
	$agama_siswa = str_replace('KongHuChu', 'Konghuchu', $agama_siswa);
	$agama_siswa = str_replace('Kong Hu Chu', 'Konghuchu', $agama_siswa);
    $agama_siswa = str_replace('Kepercayaan ', '', $agama_siswa);
    if ($agama_siswa == $nama_agama) {
        return true;
    } else {
        return false;
    }
}
function status_kenaikan($status){
    if ($status == 1) {
        $status_teks = 'Naik ke kelas';
    } elseif ($status == 2) {
        $status_teks = 'Tetap dikelas';
    } elseif ($status == 3) {
        $status_teks = 'Lulus';
    } else {
        $status_teks = 'Tidak Lulus';
    }
    return $status_teks;
}
function update_wilayah($wilayah){
    $data = MstWilayah::updateOrCreate(
        [
            'kode_wilayah' => $wilayah->kode_wilayah,
        ],
        [
            'nama' => $wilayah->nama,
            'id_level_wilayah' => $wilayah->id_level_wilayah,
            'mst_kode_wilayah' => $wilayah->mst_kode_wilayah,
            'negara_id' => $wilayah->negara_id,
            'asal_wilayah' => $wilayah->asal_wilayah,
            'kode_bps' => $wilayah->kode_bps,
            'kode_dagri' => $wilayah->kode_dagri,
            'kode_keu' => $wilayah->kode_keu,
            'deleted_at' => $wilayah->expired_date,
            'last_sync' => $wilayah->last_sync,
        ]
    );
    return $data;
}
function proses_wilayah($wilayah, $recursive){
    if(!$recursive){
        update_wilayah($wilayah);
    } else {
        $kecamatan = NULL;
        $kabupaten = NULL;
        $provinsi = NULL;
        if($wilayah->id_level_wilayah == 4){
            if($wilayah->parrent_recursive){
                if($wilayah->parrent_recursive->parrent_recursive){
                    if($wilayah->parrent_recursive->parrent_recursive->parrent_recursive){
                        $provinsi = update_wilayah($wilayah->parrent_recursive->parrent_recursive->parrent_recursive);
                        $kabupaten = update_wilayah($wilayah->parrent_recursive->parrent_recursive);
                        $kecamatan = update_wilayah($wilayah->parrent_recursive);
                        $desa = update_wilayah($wilayah);
                    }
                }
            }
        } else {
            $kecamatan = $wilayah->nama;
            if($wilayah->parrent_recursive){
                $kabupaten = $wilayah->parrent_recursive->nama;
                if($wilayah->parrent_recursive->parrent_recursive){
                    $provinsi = update_wilayah($wilayah->parrent_recursive->parrent_recursive);
                    $kabupaten = update_wilayah($wilayah->parrent_recursive);
                    $kecamatan = update_wilayah($wilayah);
                }
            }
        }
        return [
            'kecamatan' => $kecamatan,
            'kabupaten' => $kabupaten,
            'provinsi' => $provinsi,
        ];
    }
}
function array_to_object($array) {
   $obj = new stdClass();

   foreach ($array as $k => $v) {
      if (strlen($k)) {
         if (is_array($v)) {
            $obj->{$k} = array_to_object($v); //RECURSION
         } else {
            $obj->{$k} = $v;
         }
      }
   }
   
   return $obj;
}
function simpan_ptk($data){
    if($data){
        $data = array_to_object($data);
        proses_wilayah($data->wilayah, TRUE);
        $random = Str::random(6);
        $data->email = ($data->email) ? $data->email : strtolower($random).'@erapor-smk.net';
        $data->email = strtolower($data->email);
        $data->nuptk = ($data->nuptk) ? $data->nuptk : mt_rand();
        $jenis_ptk_id = 0;
        $create_guru = Ptk::withTrashed()->updateOrCreate(
            [
                'guru_id' => $data->ptk_id
            ],
            [
                'guru_id_dapodik'       => $data->ptk_id,
                'sekolah_id' 			=> request()->sekolah_id,
                'nama' 					=> $data->nama,
                'nuptk' 				=> $data->nuptk,
                'nip' 					=> $data->nip,
                'nik' 					=> $data->nik,
                'jenis_kelamin' 		=> $data->jenis_kelamin,
                'tempat_lahir' 			=> $data->tempat_lahir,
                'tanggal_lahir' 		=> $data->tanggal_lahir,
                'status_kepegawaian_id'	=> $data->status_kepegawaian_id,
                'jenis_ptk_id' 			=> $data->ptk_terdaftar->jenis_ptk_id,
                'jabatan_ptk_id' 		=> $data->tugas_tambahan?->jabatan_ptk_id,
                'agama_id' 				=> $data->agama_id,
                'alamat' 				=> $data->alamat_jalan,
                'rt' 					=> $data->rt,
                'rw' 					=> $data->rw,
                'desa_kelurahan' 		=> $data->desa_kelurahan,
                'kecamatan' 			=> $data->wilayah->nama,
                'kode_wilayah'			=> $data->kode_wilayah,
                'kode_pos'				=> ($data->kode_pos) ? $data->kode_pos : 0,
                'no_hp'					=> ($data->no_hp) ? $data->no_hp : 0,
                'email' 				=> $data->email,
                'is_dapodik'			=> 1,
                'last_sync'				=> Carbon::now()->subDays(30),
                'deleted_at' => NULL,
            ]
        );
        if(isset($data->rwy_pend_formal)){
            $gelar_ptk_id = [];
            foreach($data->rwy_pend_formal as $rwy_pend_formal){
                $gelar_ptk_id[] = $rwy_pend_formal->riwayat_pendidikan_formal_id;
                $riwayat_pendidikan_formal_id = strtolower($rwy_pend_formal->riwayat_pendidikan_formal_id);
                $ptk_id = $rwy_pend_formal->ptk_id;
            }
        }
        return $create_guru;
    }
}
function insert_jurusan($data){
    $jurusan_induk = NULL;
    if($data->jurusan_induk){
        $jurusan_induk = Jurusan::find($data->jurusan_induk);
    }
    Jurusan::updateOrCreate(
        [
            'jurusan_id' => $data->jurusan_id
        ],
        [
            'nama_jurusan' => $data->nama_jurusan,
            'untuk_sma' => $data->untuk_sma,
            'untuk_smk' => $data->untuk_smk,
            'untuk_pt' => $data->untuk_pt,
            'untuk_slb' => $data->untuk_slb,
            'untuk_smklb' => $data->untuk_smklb,
            'jenjang_pendidikan_id' => $data->jenjang_pendidikan_id,
            'jurusan_induk' => ($jurusan_induk) ? $data->jurusan_induk : NULL,
            'level_bidang_id' => $data->level_bidang_id,
            'deleted_at' => $data->expired_date,
            'last_sync' => Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
}
function insert_kurikulum($data){
    $jurusan = NULL;
    if($data->jurusan_id){
        $jurusan = Jurusan::find($data->jurusan_id);
    }
    Kurikulum::updateOrCreate(
        [
            'kurikulum_id' => $data->kurikulum_id
        ],
        [
            'nama_kurikulum'			=> $data->nama_kurikulum,
            'mulai_berlaku'				=> $data->mulai_berlaku,
            'sistem_sks'				=> $data->sistem_sks,
            'total_sks'					=> $data->total_sks,
            'jenjang_pendidikan_id'		=> $data->jenjang_pendidikan_id,
            'jurusan_id'				=> ($jurusan) ? $data->jurusan_id : NULL,
            'deleted_at'				=> $data->expired_date,
            'last_sync'					=> Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
}
function insert_jurusan_sp($data){
    $find = Jurusan::find($data->jurusan_id);
    if($find){
        JurusanSp::withTrashed()->updateOrCreate(
            [
                'jurusan_sp_id' => $data->jurusan_sp_id,
            ],
            [
                'jurusan_sp_id_dapodik' => $data->jurusan_sp_id,
                'sekolah_id' => request()->sekolah_id,
                'jurusan_id' => $data->jurusan_id,
                'nama_jurusan_sp' => $data->nama_jurusan_sp,
                'last_sync' => Carbon::now()->subDays(30),
                'deleted_at' => NULL,
            ]
        );
    }
}
function insert_rombel($data){
    $jurusan = NULL;
    $jurusan_sp = NULL;
    if(isset($data->jurusan_sp)){
        insert_jurusan_sp($data->jurusan_sp);
        $jurusan = Jurusan::find($data->jurusan_sp->jurusan_id);
        $jurusan_sp = JurusanSp::find($data->jurusan_sp_id);
    }
    if(isset($data->Soft_delete)){
        $soft_delete = ($data->Soft_delete) ? now() : NULL;
    } else {
        $soft_delete = ($data->soft_delete) ? now() : NULL;
    }
    RombonganBelajar::withTrashed()->updateOrCreate(
        [
            'rombongan_belajar_id' => $data->rombongan_belajar_id,
        ],
        [
            'sekolah_id' => request()->sekolah_id,
            'semester_id' => $data->semester_id,
            'jurusan_id' => ($jurusan) ? $data->jurusan_sp->jurusan_id : NULL,
            'jurusan_sp_id' => ($jurusan_sp) ? $data->jurusan_sp_id : NULL,
            'kurikulum_id' => $data->kurikulum_id,
            'nama' => $data->nama,
            'guru_id' => $data->ptk_id,
            'ptk_id' => $data->ptk_id,
            'tingkat' => $data->tingkat_pendidikan_id,
            'jenis_rombel' => $data->jenis_rombel,
            'rombel_id_dapodik' => $data->rombongan_belajar_id,
            'deleted_at' => $soft_delete,
            'last_sync' => Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
}
function simpan_rombel($data){
    $data = array_to_object($data);
    if($data->jurusan_sp){
        insert_jurusan($data->jurusan_sp->jurusan);
    }
    insert_kurikulum($data->kurikulum);
    insert_rombel($data);
}
function simpan_mapel($data){
    MataPelajaran::updateOrCreate(
        [
            'mata_pelajaran_id' => $data->mata_pelajaran_id,
        ],
        [
            'jurusan_id' 				=> $data->jurusan_id,
            'nama'						=> $data->nama,
            'pilihan_sekolah'			=> $data->pilihan_sekolah,
            'pilihan_kepengawasan'		=> $data->pilihan_kepengawasan,
            'pilihan_buku'				=> $data->pilihan_buku,
            'pilihan_evaluasi'			=> $data->pilihan_evaluasi,
            'deleted_at'				=> $data->expired_date,
            'last_sync'					=> Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
}
function insert_mata_pelajaran($data){
    if($data && $data->jurusan_id){
        $jurusan = Jurusan::find($data->jurusan_id);
        if($jurusan){
            simpan_mapel($data);
        }
    } else {
        simpan_mapel($data);
    }
}
function simpan_pembelajaran($data){
    $data = array_to_object($data);
    $find = RombonganBelajar::find($data->rombongan_belajar_id);
    simpan_ptk($data->ptk_terdaftar);
    insert_mata_pelajaran($data->mata_pelajaran);
    if($find){
        Pembelajaran::withTrashed()->updateOrCreate(
            [
                'pembelajaran_id' => $data->pembelajaran_id
            ],
            [
                'pembelajaran_id_dapodik' => $data->pembelajaran_id,
                'induk_pembelajaran_id' => $data->induk_pembelajaran_id,
                'semester_id' => $data->semester_id,
                'sekolah_id'				=> request()->sekolah_id,
                'rombongan_belajar_id'		=> $data->rombongan_belajar_id,
                'guru_id'					=> $data->ptk_terdaftar->ptk_id,
                'mata_pelajaran_id'			=> $data->mata_pelajaran_id,
                'nama_mata_pelajaran'		=> $data->nama_mata_pelajaran,
                'kkm'						=> 0,
                'is_dapodik'				=> 1,
                'deleted_at'                => NULL,
                'last_sync'					=> Carbon::now()->subDays(30),
                'deleted_at' => NULL,
            ]
        );
        foreach($data->sub_mapel as $sup){
            simpan_pembelajaran($sup);
        }    
    }
}
function simpan_anggota_rombel($data, $deleted_at){
    AnggotaRombel::withTrashed()->updateOrCreate(
        [
            'anggota_rombel_id' => $data->anggota_rombel_id,
        ],
        [
            'sekolah_id' => request()->sekolah_id,
            'semester_id' => request()->semester_id,
            'rombongan_belajar_id' => $data->rombongan_belajar_id,
            'peserta_didik_id' => $data->peserta_didik_id,
            'anggota_rombel_id_dapodik' => $data->anggota_rombel_id,
            'deleted_at' => $deleted_at,
            'last_sync' => Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
}
function simpan_pd_keluar($data){
    simpan_pd($data, now());
    PdKeluar::updateOrCreate(
        [
            'peserta_didik_id' => $data->peserta_didik_id,
        ],
        [
            'sekolah_id' => request()->sekolah_id,
            'semester_id' => request()->semester_id,
            'last_sync' => Carbon::now()->subDays(30),
        ]
    );
}
function simpan_pd_aktif($data){
    $data = array_to_object($data);
    simpan_pd($data, NULL);
}
function simpan_pd($data, $deleted_at){
    $wilayah = NULL;
    if(isset($data->wilayah)){
        $wilayah = proses_wilayah($data->wilayah, TRUE);
    }
    $kecamatan = NULL;
    if($wilayah){
        try {
            $kecamatan = ($wilayah['kecamatan']) ? $wilayah['kecamatan']->nama : 0;
        } catch (\Throwable $e) {
            //$e
        }
    }
    PesertaDidik::withTrashed()->updateOrCreate(
        [
            'peserta_didik_id' => $data->peserta_didik_id
        ],
        [
            'peserta_didik_id_dapodik' => $data->peserta_didik_id,
            'sekolah_id'		=> request()->sekolah_id,
            'nama' 				=> $data->nama,
            'no_induk' 			=> ($data->registrasi_peserta_didik->nipd) ? $data->registrasi_peserta_didik->nipd : 0,
            'nisn' 				=> $data->nisn,
            'nik'               => $data->nik,
            'jenis_kelamin' 	=> ($data->jenis_kelamin) ?? 0,
            'tempat_lahir' 		=> ($data->tempat_lahir) ?? 0,
            'tanggal_lahir' 	=> $data->tanggal_lahir,
            'agama_id' 			=> ($data->agama_id) ?? 0,
            'status' 			=> 'Anak Kandung',
            'anak_ke' 			=> ($data->anak_keberapa) ?? 0,
            'alamat' 			=> ($data->alamat_jalan) ?? 0,
            'rt' 				=> ($data->rt) ?? 0,
            'rw' 				=> ($data->rw) ?? 0,
            'desa_kelurahan' 	=> ($data->desa_kelurahan) ?? 0,
            'kecamatan' 		=> $kecamatan,
            'kode_pos' 			=> ($data->kode_pos) ?? 0,
            'no_telp' 			=> ($data->nomor_telepon_rumah) ?? 0,
            'no_hp' 			=> ($data->nomor_telepon_seluler) ?? 0,
            'sekolah_asal' 		=> ($data->registrasi_peserta_didik) ? $data->registrasi_peserta_didik->sekolah_asal : 0,
            'diterima' 			=> ($data->registrasi_peserta_didik) ? $data->registrasi_peserta_didik->tanggal_masuk_sekolah : NULL,
            'diterima_kelas'    => ($data->diterima_dikelas) ? ($data->diterima_dikelas->rombongan_belajar) ? $data->diterima_dikelas->rombongan_belajar->nama : NULL : NULL,
            'kode_wilayah' 		=> $data->kode_wilayah,
            'email' 			=> $data->email,
            'nama_ayah' 		=> ($data->nama_ayah) ?? 0,
            'nama_ibu' 			=> ($data->nama_ibu_kandung) ?? 0,
            'kerja_ayah' 		=> ($data->pekerjaan_id_ayah) ? $data->pekerjaan_id_ayah : 1,
            'kerja_ibu' 		=> ($data->pekerjaan_id_ibu) ? $data->pekerjaan_id_ibu : 1,
            'nama_wali' 		=> ($data->nama_wali) ?? 0,
            'alamat_wali' 		=> ($data->alamat_jalan) ?? 0,
            'telp_wali' 		=> ($data->nomor_telepon_seluler) ?? 0,
            'kerja_wali' 		=> ($data->pekerjaan_id_wali) ? $data->pekerjaan_id_wali : 1,
            'deleted_at'        => NULL,
            'active' 			=> 1,
            'last_sync' => Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
    if(isset($data->anggota_rombel)){
        $find = RombonganBelajar::find($data->anggota_rombel->rombongan_belajar_id);
        if($find){
            simpan_anggota_rombel($data->anggota_rombel, $deleted_at);
        }
    }
}
function simpan_ekskul($data){
    $data = array_to_object($data);
    insert_rombel($data->rombongan_belajar);
    $find = Ptk::find($data->rombongan_belajar->ptk_id);
    if($find){
        Ekstrakurikuler::withTrashed()->updateOrCreate(
            [
                'ekstrakurikuler_id' => $data->id_kelas_ekskul,
            ],
            [
                'id_kelas_ekskul' => $data->id_kelas_ekskul,
                'semester_id' => request()->semester_id,
                'sekolah_id'	=> request()->sekolah_id,
                'guru_id' => $data->rombongan_belajar->ptk_id,
                'nama_ekskul' => $data->nm_ekskul,
                'is_dapodik' => 1,
                'rombongan_belajar_id'	=> $data->rombongan_belajar_id,
                'alamat_ekskul' => $data->rombongan_belajar->ruang->nm_ruang, 
                'last_sync'	=> Carbon::now()->subDays(30),
                'deleted_at' => NULL,
            ]
        );
    }
}
function simpan_anggota_ekskul($data){
    $data = array_to_object($data);
    simpan_pd($data->pd, NULL);
    simpan_anggota_rombel($data, NULL);
}
function simpan_dudi($data){
    $data = array_to_object($data);
    $dudi = Dudi::withTrashed()->updateOrCreate(
        [
            'dudi_id' => $data->dudi_id
        ],
        [
            'dudi_id_dapodik' => $data->dudi_id,
            'sekolah_id'		=> request()->sekolah_id,
            'nama'				=> $data->nama,
            'bidang_usaha_id'	=> $data->bidang_usaha_id,
            'nama_bidang_usaha'	=> '-',
            'alamat_jalan'		=> $data->alamat_jalan,
            'rt'				=> $data->rt,
            'rw'				=> $data->rw,
            'nama_dusun'		=> $data->nama_dusun,
            'desa_kelurahan'	=> $data->desa_kelurahan,
            'kode_wilayah'		=> $data->kode_wilayah,
            'kode_pos'			=> $data->kode_pos,
            'lintang'			=> $data->lintang,
            'bujur'				=> $data->bujur,
            'nomor_telepon'		=> $data->nomor_telepon,
            'nomor_fax'			=> $data->nomor_fax,
            'email'				=> $data->email,
            'website'			=> $data->website,
            'npwp'				=> $data->npwp,
            'last_sync' => Carbon::now()->subDays(30),
            'deleted_at' => NULL,
        ]
    );
    foreach($data->mou as $mou){
        $dudi->nama_bidang_usaha = $mou->nama_bidang_usaha;
        $dudi->save();
        Mou::withTrashed()->updateOrCreate(
            [
                'mou_id' => $mou->mou_id
            ],
            [
                'mou_id_dapodik' => $mou->mou_id,
                'id_jns_ks'			=> $mou->id_jns_ks,
                'dudi_id'			=> $mou->dudi_id,
                'dudi_id_dapodik'	=> $mou->dudi_id,
                'sekolah_id'		=> request()->sekolah_id,
                'nomor_mou'			=> $mou->nomor_mou,
                'judul_mou'			=> $mou->judul_mou,
                'tanggal_mulai'		=> $mou->tanggal_mulai,
                'tanggal_selesai'	=> ($mou->tanggal_selesai) ? $mou->tanggal_selesai : date('Y-m-d'),
                'nama_dudi'			=> $mou->nama_dudi,
                'npwp_dudi'			=> $mou->npwp_dudi,
                'nama_bidang_usaha'	=> $mou->nama_bidang_usaha,
                'telp_kantor'		=> $mou->telp_kantor,
                'fax'				=> $mou->fax,
                'contact_person'	=> $mou->contact_person,
                'telp_cp'			=> $mou->telp_cp,
                'jabatan_cp'		=> $mou->jabatan_cp,
                'last_sync' => Carbon::now()->subDays(30),
                'deleted_at' => NULL,
            ]
        );
        foreach($mou->akt_pd as $akt_pd){
            AktPd::withTrashed()->updateOrCreate(
                [
                    'akt_pd_id' => $akt_pd->id_akt_pd
                ],
                [
                    'akt_pd_id_dapodik' => $akt_pd->id_akt_pd,
                    'sekolah_id'	=> request()->sekolah_id,
                    'mou_id'		=> $mou->mou_id,
                    'id_jns_akt_pd'	=> $akt_pd->id_jns_akt_pd,
                    'judul_akt_pd'	=> $akt_pd->judul_akt_pd,
                    'sk_tugas'		=> ($akt_pd->sk_tugas) ? $akt_pd->sk_tugas : '-',
                    'tgl_sk_tugas'	=> $akt_pd->tgl_sk_tugas,
                    'ket_akt'		=> $akt_pd->ket_akt,
                    'a_komunal'		=> $akt_pd->a_komunal,
                    'last_sync'		=> Carbon::now()->subDays(30),
                    'deleted_at' => NULL,
                ]
            );
            if($akt_pd->anggota_akt_pd){
                foreach($akt_pd->anggota_akt_pd as $anggota_akt_pd){
                    if($anggota_akt_pd->registrasi_peserta_didik){
                        $find = PesertaDidik::find($anggota_akt_pd->registrasi_peserta_didik->peserta_didik_id);
                        if($find){
                            $create_anggota_akt_pd = AnggotaAktPd::withTrashed()->updateOrCreate(
                                [
                                    'anggota_akt_pd_id' => $anggota_akt_pd->id_ang_akt_pd,
                                ],
                                [
                                    'id_ang_akt_pd' => $anggota_akt_pd->id_ang_akt_pd,
                                    'sekolah_id'		=> request()->sekolah_id,
                                    'akt_pd_id'			=> $akt_pd->id_akt_pd,
                                    'peserta_didik_id'	=> $anggota_akt_pd->registrasi_peserta_didik->peserta_didik_id,
                                    'nm_pd'				=> $anggota_akt_pd->nm_pd,
                                    'nipd'				=> $anggota_akt_pd->nipd,
                                    'jns_peran_pd'		=> $anggota_akt_pd->jns_peran_pd,
                                    'last_sync' => Carbon::now()->subDays(30),
                                    'deleted_at' => NULL,
                                ]
                            );
                        }
                    }
                }
            }
            if($akt_pd->bimbing_pd){
                foreach($akt_pd->bimbing_pd as $bimbing_pd){
                    $find = Ptk::withTrashed()->find($bimbing_pd->ptk_id);
                    if($find){
                        $create_bimbing_pd = BimbingPd::withTrashed()->updateOrCreate(
                            [
                                'bimbing_pd_id' => $bimbing_pd->id_bimb_pd
                            ],
                            [
                                'id_bimb_pd' => $bimbing_pd->id_bimb_pd,
                                'sekolah_id'		=> request()->sekolah_id,
                                'akt_pd_id'			=> $akt_pd->id_akt_pd,
                                'guru_id'			=> $bimbing_pd->ptk_id,
                                'ptk_id'			=> $bimbing_pd->ptk_id,
                                'urutan_pembimbing'	=> $bimbing_pd->urutan_pembimbing,
                                'last_sync' => Carbon::now()->subDays(30),
                                'deleted_at' => NULL,
                            ]
                        );
                    }
                }
            }
        }
    }
}
function simpan_anggota_matpil($data){
    $data = array_to_object($data);
    $pd = PesertaDidik::find($data->peserta_didik_id);
    $rombel = RombonganBelajar::find($data->rombongan_belajar_id);
    if($pd && $rombel){
        simpan_anggota_rombel($data, NULL);
    }
}
