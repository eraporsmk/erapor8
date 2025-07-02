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
    protected $signature = 'app:version';

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
        $this->info('Mengecek versi aplikasi...');
        $response = Http::post('sync.erapor-smk.net/api/v7/version');
        $now = get_setting('app_version');
        if($response->successful()){
            $version = $response->object();
            if (version_compare($version->version, $now) < 0) {
                $this->info('Aplikasi Versi baru tersedia: '.$version->version);
                $this->info('Versi Aplikasi saat ini: '.$now);
                $update = $this->anticipate('Apakah Anda ingin mengupdate versi aplikasi? (Y/y: Ya, N/n: Tidak)', ['Y', 'N']);
                if(strtolower($update) == 'y'){
                    $this->info('Silahkan tunggu, sedang proses update aplikasi....');
                    exec("git pull origin main");
                    exec("composer update");
                    $this->call('app:update');
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
    }
}
