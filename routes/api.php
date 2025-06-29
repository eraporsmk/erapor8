<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SinkronisasiController;

Route::group(['prefix' => 'auth'], function () {
    Route::get('semester', [AuthController::class, 'semester']);
    Route::post('login', [AuthController::class, 'login']);
    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::get('logout', [AuthController::class, 'logout']);
    });
});
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::post('/status-penilaian', [DashboardController::class, 'status_penilaian']);
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
    });
    Route::group(['prefix' => 'setting'], function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/update', [SettingController::class, 'update']);
        Route::get('users', [SettingController::class, 'users']);
        Route::post('detil-user', [SettingController::class, 'detil_user']);
        Route::post('hapus-akses', [SettingController::class, 'hapus_akses']);
        Route::post('update-akses', [SettingController::class, 'update_akses']);
        Route::post('generate-pengguna', [SettingController::class, 'generate_pengguna']);
        Route::post('users-roles', [SettingController::class, 'users_roles']);
        Route::get('permissions', [SettingController::class, 'permissions']);
        Route::post('permissions', [SettingController::class, 'permissions']);
        Route::get('profile', [SettingController::class, 'profile']);
        Route::post('profile', [SettingController::class, 'profile']);
        Route::get('languages', [SettingController::class, 'languages']);
        Route::post('languages', [SettingController::class, 'languages']);
        Route::delete('/destroy/{data}/{id}', [SettingController::class, 'destroy']);
    });
});
