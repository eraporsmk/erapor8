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
});
Route::get('{any?}', function() {
    return view('application');
})->where('any', '.*');
