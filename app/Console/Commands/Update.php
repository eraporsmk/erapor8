<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Setting;
use App\Models\TahunAjaran;
use App\Models\Semester;
use App\Models\Role;
use App\Models\User;
use App\Models\Team;
use File;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

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
        $win = Str::contains(php_uname('s'), 'Windows');
        $mac = Str::contains(php_uname('s'), 'Windows');
        $linux = Str::contains(php_uname('s'), 'Windows');
        $this->call('storage:unlink');
        if($win){
            exec('rmdir /s /q '.public_path('storage'));
        } elseif($mac || $linux){
            exec('rm -rf '.public_path('storage'));
        }
        $this->call('storage:link');
        $ajaran = [
            [
                'tahun_ajaran_id' => 2025,
                'nama' => '2025/2026',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20251,
                        'nama' => '2025/2026 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20252,
                        'nama' => '2025/2026 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ]
        ];
        foreach($ajaran as $a){
            TahunAjaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                ],
                [
                    'nama' => $a['nama'],
                    'periode_aktif' => $a['periode_aktif'],
                    'tanggal_mulai' => '2020-07-20',
                    'tanggal_selesai' => '2021-06-01',
                    'last_sync' => now(),
                ]
            );
            foreach($a['semester'] as $semester){
                Semester::updateOrCreate(
                    [
                        'semester_id' => $semester['semester_id'],
                    ],
                    [
                        'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                        'nama' => $semester['nama'],
                        'semester' => $semester['semester'],
                        'periode_aktif' => $semester['periode_aktif'],
                        'tanggal_mulai' => '2020-07-01',
                        'tanggal_selesai' => '2021-12-31',
                        'last_sync' => now(),
                    ]
                );
            }
        }
        $all_semester = Semester::whereHas('tahun_ajaran', function($query){
            $query->where('periode_aktif', 1);
        })->get();
        $adminRole = Role::where('name', 'admin')->first();
        $users = User::whereNotNull('sekolah_id')->whereNull('guru_id')->whereNull('peserta_didik_id')->get();
        foreach($all_semester as $semester){
            $team = Team::updateOrCreate([
                'name' => $semester->nama,
                'display_name' => $semester->nama,
                'description' => $semester->nama,
            ]);
            foreach($users as $user){
                if(!$user->hasRole($adminRole, $team)){
                    $user->addRole($adminRole, $team);
                }
            }
        }
        TahunAjaran::whereIn('tahun_ajaran_id', ['2025', '2024', '2023', '2022'])->update(['periode_aktif' => 1]);
        TahunAjaran::whereNotIn('tahun_ajaran_id', ['2025', '2024', '2023', '2022'])->update(['periode_aktif' => 0]);
        Semester::where('semester_id', '<>', '20242')->update(['periode_aktif' => 0]);
        Semester::where('semester_id', '20242')->update(['periode_aktif' => 1]);
        $version = File::get(base_path().'/app_version.txt');
        $db_version = File::get(base_path().'/db_version.txt');
        //$this->call('generate:akses');
        $this->call('migrate');
        $this->call('cache:clear');
        $this->call('view:clear');
        $this->call('config:cache');
        Setting::updateOrCreate(
            [
                'key' => 'app_version',
            ],
            [
                'value' => trim($version),
            ]
        );
        Setting::updateOrCreate(
            [
                'key' => 'db_version',
            ],
            [
                'value' => trim($db_version),
            ]
        );
        $this->info('Berhasil memperbaharui aplikasi e-Rapor SMK ke versi '.$version);
    }
}
