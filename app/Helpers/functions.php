<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\Pembelajaran;
use App\Models\Agama;
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
