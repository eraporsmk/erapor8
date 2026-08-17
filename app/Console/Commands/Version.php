<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class Version extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:version {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (version_compare(phpversion(), '8.2', '<')) {
            $this->error('Versi PHP tidak kompatible! Versi PHP saat ini: '.phpversion().'. Versi PHP yang dibutuhkan: 8.2');
        } else {
            $now = get_setting('app_version');
            $this->info('Versi Aplikasi: '.$now);
            $this->info('Versi Database: '.get_setting('db_version'));
            $this->info('Untuk mengupdate aplikasi, silahkan jalankan: php artisan app:update');
            // Menambahkan opsi konfirmasi interaktif (default: no)
            if ($this->confirm('Apakah Anda ingin mengupdate aplikasi sekarang?', false)) {
                $this->info('Menjalankan proses update...');
                
                // Memanggil artisan command app:update secara langsung
                $exitCode = $this->call('app:update');

                if ($exitCode === self::SUCCESS) {
                    $this->info('Proses update selesai.');
                } else {
                    $this->error('Proses update gagal.');
                }
            } else {
                $this->comment('Update dibatalkan. Anda dapat mengupdate kapan saja dengan menjalankan: php artisan app:update');
            }

            return self::SUCCESS;
            //$this->prosesUpdate();
            /*if ($this->option('force')){
                $this->prosesUpdate();
            } else {
                $this->info('Mengecek versi aplikasi...');
                $response = Http::post('sync.erapor-smk.net/api/v8/version');
                $now = get_setting('app_version');
                if($response->successful()){
                    $version = $response->object();
                    if (version_compare($version->version, $now) < 0) {
                        $this->info('Aplikasi Versi baru tersedia: '.$version->version);
                        $this->info('Versi Aplikasi saat ini: '.$now);
                        $update = $this->anticipate('Apakah Anda ingin mengupdate versi aplikasi? (Y/y: Ya, N/n: Tidak)', ['Y', 'N']);
                        if(strtolower($update) == 'y'){
                            $this->prosesUpdate();
                        }
                    } else {
                        $this->error('Aplikasi Versi baru belum tersedia');
                        $this->info('Versi Aplikasi: '.$now);
                        $this->info('Versi Database: '.get_setting('db_version'));
                    }
                } else {
                    $this->info('Versi Aplikasi: '.$now);
                    $this->info('Versi Database: '.get_setting('db_version'));
                }
            }*/
        }
    }
    private function prosesUpdate(){
        $token = config('app.github_token');
        $this->info('Silahkan tunggu, sedang proses update aplikasi....');
        //exec("git clean -df");
        exec("git stash");
        exec("git config --global --add safe.directory C:/eRaporSMK/dataweb");
        exec("git pull origin main");
        //exec("git pull https://eraporsmk:$token@github.com/eraporsmk/erapor8.git main");
        exec("composer update");
        $this->call('app:update');
    }
}
