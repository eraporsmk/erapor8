<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\DownloadController;
//http://erapor7.test/downloads/template-sumatif-lingkup-materi/eced7be6-7317-4b77-80cc-a47c76d894cd
Route::group(['prefix' => 'downloads'], function () {
    Route::get('/template-tp/{id?}/{rombongan_belajar_id?}/{pembelajaran_id?}', [DownloadController::class, 'template_tp']);
    Route::get('/template-sumatif-lingkup-materi/{pembelajaran_id?}', [DownloadController::class, 'template_sumatif_lingkup_materi'])->name('template-sumatif-lingkup-materi');
    Route::get('/template-sumatif-akhir-semester/{pembelajaran_id?}', [DownloadController::class, 'template_sumatif_akhir_semester'])->name('template-sumatif-akhir-semester');
    Route::get('/template-nilai-akhir/{pembelajaran_id?}', [DownloadController::class, 'template_nilai_akhir'])->name('template-nilai-akhir');
    Route::get('/leger-nilai-kurmer/{rombongan_belajar_id}/{sekolah_id}/{semester_id}', [DownloadController::class, 'unduh_leger_nilai_kurmer'])->name('unduh-leger-nilai-kurmer');
});
Route::group(['prefix' => 'cetak'], function () {
    Route::get('/sertifikat/{anggota_rombel_id}/{rencana_ukk_id}', [CetakController::class, 'sertifikat'])->name('sertifikat');
    Route::get('/rapor-cover/{anggota_rombel_id}/{rombongan_belajar_id?}', [CetakController::class, 'rapor_cover'])->name('rapor-cover');
    Route::get('/rapor-semester/{anggota_rombel_id}/{sekolah_id}/{semester_id}', [CetakController::class, 'rapor_semester'])->name('rapor-semester');
    Route::get('/rapor-nilai-akhir/{anggota_rombel_id}/{sekolah_id}/{semester_id}', [CetakController::class, 'rapor_nilai_akhir'])->name('rapor-nilai-akhir');
    Route::get('/rapor-p5/{anggota_rombel_id}/{semester_id}', [CetakController::class, 'rapor_p5'])->name('rapor-p5');
    Route::get('/rapor-pelengkap/{anggota_rombel_id}/{rombongan_belajar_id?}', [CetakController::class, 'rapor_pelengkap'])->name('rapor-pelengkap');
});
Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');
