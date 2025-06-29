<?php
use Illuminate\Support\Facades\Http;
use App\Models\Semester;
use App\Models\Setting;
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
