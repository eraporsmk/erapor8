<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

Schedule::call(function () {
    $now = Carbon::now();
    
    // Clean old temp files
    $files = Storage::disk('local')->allFiles('temp');
    foreach ($files as $file) {
        $lastModified = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file));
        if ($now->diffInHours($lastModified) >= 24) {
            Storage::disk('local')->delete($file);
        }
    }
    
    // Clean old temp directories
    $directories = Storage::disk('local')->allDirectories('temp');
    foreach ($directories as $directory) {
        $lastModified = Carbon::createFromTimestamp(Storage::disk('local')->lastModified($directory));
        if ($now->diffInHours($lastModified) >= 24) {
            Storage::disk('local')->deleteDirectory($directory);
        }
    }
})->daily();

