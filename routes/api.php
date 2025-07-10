<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SinkronisasiController;
use App\Http\Controllers\PtkController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\PdController;
use App\Http\Controllers\ReferensiController;
use App\Http\Controllers\PenilaianController;

Route::group(['prefix' => 'auth'], function () {
    Route::get('semester', [AuthController::class, 'semester']);
    Route::get('/allow-register', [AuthController::class, 'allow_register']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::post('user', [AuthController::class, 'user']);
    });
});
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::post('/', [DashboardController::class, 'index']);
        Route::post('/wali', [DashboardController::class, 'wali']);
        Route::post('/status-penilaian', [DashboardController::class, 'status_penilaian']);
        Route::post('/detil-penilaian', [DashboardController::class, 'detil_penilaian']);
        Route::post('/generate-nilai', [DashboardController::class, 'generate_nilai']);
    });
    Route::group(['prefix' => 'sinkronisasi'], function () {
        Route::get('/', [SinkronisasiController::class, 'index']);
        Route::post('/dapodik', [SinkronisasiController::class, 'proses_sync']);
        Route::get('/hitung/{sekolah_id}', [SinkronisasiController::class, 'hitung']);
        Route::get('/rombongan-belajar', [SinkronisasiController::class, 'rombongan_belajar']);
        Route::post('/matev-rapor', [SinkronisasiController::class, 'matev_rapor']);
        Route::post('/kirim-nilai', [SinkronisasiController::class, 'kirim_nilai']);
        Route::get('/get-matev-rapor', [SinkronisasiController::class, 'get_matev_rapor']);
        Route::get('/erapor', [SinkronisasiController::class, 'erapor']);
        Route::post('/kirim-data', [SinkronisasiController::class, 'kirim_data']);
        Route::get('/nilai-dapodik', [SinkronisasiController::class, 'nilai_dapodik']);
        Route::post('/cek-koneksi', [SinkronisasiController::class, 'cek_koneksi']);
    });
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/update', [SettingController::class, 'update']);
        Route::get('users', [SettingController::class, 'users']);
        Route::post('detil-user', [SettingController::class, 'detil_user']);
        Route::post('hapus-akses', [SettingController::class, 'hapus_akses']);
        Route::post('update-akses', [SettingController::class, 'update_akses']);
        Route::post('generate-pengguna', [SettingController::class, 'generate_pengguna']);
        Route::post('reset-bg', [SettingController::class, 'reset_bg']);
        Route::get('/unduhan', [SettingController::class, 'unduhan']);
        Route::get('/changelog', [SettingController::class, 'changelog']);
        Route::get('/check-update', [SettingController::class, 'check_update']);
        Route::post('/proses-update', [SettingController::class, 'proses_update']);
    });
    Route::group(['prefix' => 'referensi'], function () {
        Route::group(['prefix' => 'ptk'], function () {
            Route::get('/', [PtkController::class, 'index']);
            Route::post('/detil', [PtkController::class, 'show']);
            Route::post('/update', [PtkController::class, 'update']);
            Route::post('/upload', [PtkController::class, 'upload']);
            Route::post('/simpan', [PtkController::class, 'simpan']);
            Route::post('/hapus', [PtkController::class, 'hapus']);
        });
        Route::group(['prefix' => 'rombongan-belajar'], function () {
            Route::get('/', [RombelController::class, 'index']);
            Route::post('/pembelajaran', [RombelController::class, 'pembelajaran']);
            Route::post('/simpan-pembelajaran', [RombelController::class, 'simpan_pembelajaran']);
            Route::post('/hapus-pembelajaran', [RombelController::class, 'hapus_pembelajaran']);
            Route::post('/anggota-rombel', [RombelController::class, 'anggota_rombel']);
            Route::post('/hapus-anggota-rombel', [RombelController::class, 'hapus_anggota_rombel']);
        });
        Route::group(['prefix' => 'pd'], function () {
            Route::get('/', [PdController::class, 'index']);
            Route::get('/detil/{id}', [PdController::class, 'show']);
            Route::post('/update', [PdController::class, 'update']);
        });
        Route::get('/mata-pelajaran', [ReferensiController::class, 'index']);
        Route::group(['prefix' => 'ekstrakurikuler'], function () {
            Route::get('/', [ReferensiController::class, 'ekstrakurikuler']);
        });
        Route::group(['prefix' => 'dudi'], function () {
            Route::get('/', [ReferensiController::class, 'dudi']);
            Route::post('/detil-dudi', [ReferensiController::class, 'detil_dudi']);
            Route::post('/anggota-prakerin', [ReferensiController::class, 'anggota_prakerin']);
        });
        Route::group(['prefix' => 'get-data'], function () {
            Route::post('/', [ReferensiController::class, 'get_data']);
        });
        Route::group(['prefix' => 'kompetensi-dasar'], function () {
            Route::get('/', [ReferensiController::class, 'kompetensi_dasar']);
            Route::post('/save', [ReferensiController::class, 'save_kd']);
            Route::post('/update', [ReferensiController::class, 'update_kd']);
        });
        Route::group(['prefix' => 'capaian-pembelajaran'], function () {
            Route::get('/', [ReferensiController::class, 'capaian_pembelajaran']);
            Route::post('/save', [ReferensiController::class, 'save_cp']);
            Route::post('/update', [ReferensiController::class, 'update_cp']);
        });
        Route::group(['prefix' => 'tujuan-pembelajaran'], function () {
            Route::get('/', [ReferensiController::class, 'tujuan_pembelajaran']);
            Route::post('/delete', [ReferensiController::class, 'hapus_tp']);
            Route::post('/save', [ReferensiController::class, 'save_tp']);
        });
        Route::group(['prefix' => 'bobot-penilaian'], function () {
            Route::get('/', [ReferensiController::class, 'bobot_penilaian']);
            Route::post('/', [ReferensiController::class, 'bobot_penilaian']);
        });
        Route::group(['prefix' => 'ukk'], function () {
            Route::get('/', [ReferensiController::class, 'ukk']);
            Route::post('/update', [ReferensiController::class, 'update_ukk']);
            Route::post('/save', [ReferensiController::class, 'save_ukk']);
        });
        Route::get('/sikap', [ReferensiController::class, 'sikap']);
    });
    Route::group(['prefix' => 'penilaian'], function () {
        Route::post('/get-cp', [PenilaianController::class, 'get_cp']);
        Route::post('/get-nilai-akhir', [PenilaianController::class, 'get_nilai_akhir']);
        Route::post('/get-capaian-kompetensi', [PenilaianController::class, 'get_capaian_kompetensi']);
        Route::post('/simpan', [PenilaianController::class, 'simpan']);
        Route::post('/destroy', [PenilaianController::class, 'destroy']);
        Route::get('/nilai-sikap/{id?}', [PenilaianController::class, 'nilai_sikap']);
        Route::post('/upload-nilai', [PenilaianController::class, 'upload_nilai']);
    });
});
