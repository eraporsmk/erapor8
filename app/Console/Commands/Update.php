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
use App\Models\Permission;
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
        $this->call('app:update-smt');
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
        Semester::where('semester_id', '<>', '20251')->update(['periode_aktif' => 0]);
        Semester::where('semester_id', '20251')->update(['periode_aktif' => 1]);
        $version = File::get(base_path().'/app_version.txt');
        $db_version = File::get(base_path().'/db_version.txt');
        $newRoles = [
            [
                'name' => 'pembimbing',
                'display_name' => 'Pembimbing PKL',
				'description' => 'Guru Pembimbing PKL', 
            ],
            [
                'name' => 'tu',
                'display_name' => 'Tata Usaha',
                'description' => 'Tata Usaha',
            ]
        ];
        foreach($newRoles as $roleData){
            Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                ],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'], 
                ],
            );
        }
        $roles = Role::get();
        foreach($roles as $role){
            $permissions = Permission::updateOrCreate(
                [ 'name' => $role->name ],
                [
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                ]
            )->id;
            $role->permissions()->sync($permissions);
        }
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
        $win = Str::contains(php_uname('s'), 'Windows');
        $mac = Str::contains(php_uname('s'), 'Darwin');
        $linux = Str::contains(php_uname('s'), 'Linux');
        $this->call('storage:unlink');
        if(is_dir(public_path('storage'))){
            if($win){
                exec('rmdir /s /q '.public_path('storage'));
            } elseif($mac || $linux){
                exec('rm -rf '.public_path('storage'));
            }
        }
        try {
            linkinfo(public_path('storage'));
        } catch (\Throwable $th) {
            $this->call('storage:link');
        }
        $this->info('Berhasil memperbaharui aplikasi e-Rapor SMK ke versi '.$version);
    }
}
