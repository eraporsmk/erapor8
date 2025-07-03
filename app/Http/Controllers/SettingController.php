<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Team;
use App\Models\Setting;
use App\Models\Semester;
use App\Models\Sekolah;
use App\Models\RombelEmpatTahun;
use App\Models\Ptk;
use App\Models\RombonganBelajar;
use App\Models\Kasek;
use App\Models\PesertaDidik;
use App\Models\Pembelajaran;
use App\Models\Ekstrakurikuler;
use Config;
use File;
class SettingController extends Controller
{
    public function index(){
        $sekolah = Sekolah::with(['kepala_sekolah' => function($query){
            $query->where('semester_id', request()->semester_id);
        }])->find(request()->sekolah_id);
        $get_rombel_4_tahun = RombelEmpatTahun::with(['rombongan_belajar'])->where('sekolah_id', request()->sekolah_id)->where('semester_id', request()->semester_id)->get();
        $rombel_4_tahun = RombelEmpatTahun::where('sekolah_id', request()->sekolah_id)->where('semester_id', request()->semester_id)->get();
        $plucked = $rombel_4_tahun->pluck('rombongan_belajar_id');
        $data = [
            'semester_id' => semester_id(),
            'semester' => Semester::whereHas('tahun_ajaran', function($query){
                $query->where('periode_aktif', 1);
            })->orderBy('semester_id', 'DESC')->get(),
            'tanggal_rapor' => get_setting('tanggal_rapor', request()->sekolah_id, request()->semester_id),
            'tanggal_rapor_kelas_akhir' => get_setting('tanggal_rapor_kelas_akhir', request()->sekolah_id, request()->semester_id),
            'kepala_sekolah' => ($sekolah->kepala_sekolah) ? $sekolah->kepala_sekolah->guru_id : $sekolah->guru_id,
            'jabatan' => get_setting('jabatan', request()->sekolah_id, request()->semester_id),
            'zona' => get_setting('zona', request()->sekolah_id),
            'data_guru' => Ptk::where('sekolah_id', request()->sekolah_id)->select('guru_id', 'nama', 'gelar_depan', 'gelar_belakang')->get(),
            'data_rombel' => RombonganBelajar::where(function($query){
                $query->where('jenis_rombel', 1);
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
                $query->whereIn('tingkat', [11, 12, 13]);
            })->select('rombongan_belajar_id', 'nama')->get(),
            'rombel_4_tahun' => $plucked->all(),
            'url_dapodik' => get_setting('url_dapodik', request()->sekolah_id),
            'token_dapodik' => get_setting('token_dapodik', request()->sekolah_id),
            'logo_sekolah' => get_setting('logo_sekolah', request()->sekolah_id),
            'bg_login' => get_setting('bg_login'),
            'periode' => substr(request()->semester_id, -1),
            'sekolah' => $sekolah,
        ];
        return response()->json($data);
    }
    public function update(Request $request){
        $request->validate(
            [
                'jabatan' => 'required',
                'semester_id' => 'required',
                'zona' => 'required',
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
                'bg_login' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            ],
            [
                'semester_id.required' => 'Periode Aktif tidak boleh kosong.',
                'zona.required' => 'Zona Waktu tidak boleh kosong.',
                'jabatan.required' => 'Jabatan Kepala sekolah tidak boleh kosong.',
                'photo.image' => 'Logo sekolah harus berupa berkas gambar',
                'photo.mimes' => 'Logo sekolah harus berekstensi (jpg, jpeg, png)',
                'photo.max' => 'Logo sekolah maksimal 1 MB.',
                'bg_login.image' => 'Kustom Background harus berupa berkas gambar',
                'bg_login.mimes' => 'Kustom Background harus berekstensi (jpg, jpeg, png)',
                'bg_login.max' => 'Kustom Background maksimal 1 MB.',
            ]
        );
        Semester::where('periode_aktif', 1)->update(['periode_aktif' => 0]);
        $s = Semester::find($request->semester_id);
        $s->periode_aktif = 1;
        $s->save();
        if($request->tanggal_rapor){
            Setting::updateOrCreate(
                [
                    'key' => 'tanggal_rapor',
                    'sekolah_id' => $request->sekolah_id,
                    'semester_id' => $request->semester_aktif,
                ],
                [
                    'value' => $request->tanggal_rapor,
                ]
            );
        }
        if($request->tanggal_rapor_pts){
            Setting::updateOrCreate(
                [
                    'key' => 'tanggal_rapor_pts',
                    'sekolah_id' => $request->sekolah_id,
                    'semester_id' => $request->semester_aktif,
                ],
                [
                    'value' => $request->tanggal_rapor_pts,
                ]
            );
        }
        if($request->tanggal_rapor_kelas_akhir){
            Setting::updateOrCreate(
                [
                    'key' => 'tanggal_rapor_kelas_akhir',
                    'sekolah_id' => $request->sekolah_id,
                    'semester_id' => $request->semester_aktif,
                ],
                [
                    'value' => $request->tanggal_rapor_kelas_akhir,
                ]
            );
        }
        if($request->tanggal_rapor_uts){
            Setting::updateOrCreate(
                [
                    'key' => 'tanggal_rapor_uts',
                    'sekolah_id' => $request->sekolah_id,
                    'semester_id' => $request->semester_aktif,
                ],
                [
                    'value' => $request->tanggal_rapor_uts,
                ]
            );
        }
        Setting::updateOrCreate(
            [
                'key' => 'zona',
                'sekolah_id' => $request->sekolah_id,
            ],
            [
                'value' => $request->zona,
            ]
        );
        Config::set('global.'.$request->sekolah_id.'.zona', $request->zona);
        if($request->rombel_4_tahun){
            $rombongan_belajar_id = [];
            foreach(json_decode($request->rombel_4_tahun) as $rombel_4_tahun){
                $rombongan_belajar_id[] = $rombel_4_tahun;
                RombelEmpatTahun::updateOrCreate(
                    [
                        'rombongan_belajar_id' => $rombel_4_tahun,
                        'sekolah_id' => $request->sekolah_id,
                        'semester_id' => $request->semester_aktif,
                    ],
                    [
                        'last_sync' => now(),
                    ]
                );
            }
            RombelEmpatTahun::whereNotIn('rombongan_belajar_id', $rombongan_belajar_id)->where('sekolah_id', $request->sekolah_id)->where('semester_id', $request->semester_aktif)->delete();
        } else {
            RombelEmpatTahun::where('sekolah_id', $request->sekolah_id)->where('semester_id', $request->semester_aktif)->delete();
        }
        Setting::where('key', 'token_dapodik')->where('sekolah_id',  request()->sekolah_id)->delete();
        Setting::where('key', 'url_dapodik')->where('sekolah_id',  request()->sekolah_id)->delete();
        if($request->token_dapodik){
            Setting::updateOrCreate(
                [
                    'key' => 'token_dapodik',
                    'sekolah_id' => request()->sekolah_id,
                    //'semester_id' => request()->semester_id,
                ],
                [
                    'value' => request()->token_dapodik,
                ]
            );
        }
        if($request->url_dapodik){
            Setting::updateOrCreate(
                [
                    'key' => 'url_dapodik',
                    'sekolah_id' => request()->sekolah_id,
                    //'semester_id' => request()->semester_id,
                ],
                [
                    'value' => request()->url_dapodik,
                ]
            );
        }
        $logo_sekolah = NULL;
        if($request->bg_login){
            $bg_login = $request->bg_login->store('images');
            Setting::updateOrCreate(
                [
                    'key' => 'bg_login',
                ],
                [
                    'value' => '/storage/images/'.basename($bg_login),
                ]
            );
        }
        if($request->photo){
            $sekolah = Sekolah::find($request->sekolah_id);
            $logo = $request->photo->store('images');
            $sekolah->logo_sekolah = basename($logo);
            $sekolah->save();
            Setting::updateOrCreate(
                [
                    'key' => 'logo_sekolah',
                    'sekolah_id' => request()->sekolah_id,
                ],
                [
                    'value' => '/storage/images/'.basename($logo),
                ]
            );
            $logo_sekolah = '/storage/images/'.basename($logo);
        }
        //$sekolah->guru_id = $request->kepala_sekolah;
        Kasek::updateOrCreate(
            [
                'sekolah_id' => $request->sekolah_id,
                'semester_id' => $request->semester_aktif,
            ],
            [
                'guru_id' => $request->kepala_sekolah,
            ]
        );
        Setting::updateOrCreate(
            [
                'key' => 'jabatan',
                'sekolah_id' => request()->sekolah_id,
                'semester_id' => request()->semester_id,
            ],
            [
                'value' => request()->jabatan,
            ]
        );
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Pengaturan berhasil disimpan',
            'logo_sekolah' => $logo_sekolah,
        ];
        return response()->json($data);
    }
    public function reset_bg(){
        Setting::where('key', 'bg_login')->delete();
    }
    public function users(){
        $team = Team::where('name', request()->periode_aktif)->first();
        $where = function($query){
            $query->whereHasRole(['guru', 'siswa', 'tu'], request()->periode_aktif);
            $query->where('sekolah_id', request()->sekolah_id);
        };
        $data = User::with(['roles' => function($query) use ($team){
            $query->wherePivot('team_id', $team->id);
        }])->where($where)->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) use ($where){
            $query->where($where);
            $query->where('name', 'ILIKE', '%' . request()->q . '%');
            $query->orWhere('nuptk', 'ILIKE', '%' . request()->q . '%');
            $query->where($where);
            $query->orWhere('nisn', 'ILIKE', '%' . request()->q . '%');
            $query->where($where);
            $query->orWhere('email', 'ILIKE', '%' . request()->q . '%');
            $query->where($where);
        })->when(request()->role_id, function($query) {
            if(request()->role_id !== 'all'){
                $query->whereHasRole(request()->role_id, request()->periode_aktif);
            }
        })->paginate(request()->per_page);
        $roles = Role::whereNotIn('id', [1,2,6])->get();
        return response()->json(['status' => 'success', 'data' => $data, 'roles' => $roles]);
    }
    public function detil_user(){
        $team = Team::where('name', request()->periode_aktif)->first();
        $user = User::with(['roles'])->find(request()->user_id);
        $user_roles = $user->rolesTeams;
        $roles = [];
        if($user->guru_id){
            $roles = Role::select('id as value', 'display_name as text')->whereIn('name', ['waka', 'kaprog', 'internal'])->orderBy('id')->get();
        }
        $collection = collect($user_roles);
        $sorted = $collection->sortBy('name', SORT_NATURAL);
        $data = [
            'user' => $user,
            'roles' => $roles,
            'permission' => $sorted->values()->all(),
        ];
        return response()->json($data);
    }
    public function hapus_akses(){
        $user = User::find(request()->user_id);
        if($user->removeRole(request()->role, request()->periode_aktif)){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Hak Akses berhasil dihapus',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => 'Hak Akses Pengguna gagal dihapus. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
    public function update_akses(){
        $user = User::find(request()->user_id);
        foreach(request()->akses as $akses){
            if(!$user->hasRole($akses, request()->periode_aktif)){
                $user->addRole($akses, request()->periode_aktif);
            }
        }
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data Pengguna berhasil diperbaharui',
        ];
        return response()->json($data);
    }
    public function generate_pengguna(){
        $function = 'generate_'.request()->aksi;
        $data = $this->{$function}();
        return response()->json($data);
    }
    private function generate_ptk(){
        $insert = 0;
        $data = Ptk::where(function($query){
            $query->whereDoesntHave('ptk_keluar', function($query){
                $query->where('semester_id', request()->semester_id);
            });
            $query->where('sekolah_id', request()->sekolah_id);
            $query->whereNotNull('email');
        })->with(['bimbing_pd' => function($query){
            $query->whereHas('akt_pd', function($query){
                $query->whereHas('anggota_akt_pd', function($query){
                    $query->whereHas('siswa', function($query){
                        $query->whereHas('anggota_rombel', function($query){
                            $query->where('semester_id', request()->semester_id);
                        });
                    });
                });
            });
        }])->get();
        $jenis_tu = jenis_gtk('tendik');
		$asesor = jenis_gtk('asesor');
        $PembinaRole = Role::where('name', 'pembina_ekskul')->first();
        $p5Role = Role::where('name', 'guru-p5')->first();
        $WalasRole = Role::where('name', 'wali')->first();
        $PilihanRole = Role::where('name', 'pilihan')->first();
        $adminRole = Role::where('name', 'admin')->first();
        $pembimbingRole = Role::where('name', 'pembimbing')->first();
        $all_role = ['pembina_ekskul', 'guru-p5', 'wali', 'admin', 'pembimbing'];
        if($data){
            foreach($data as $d){
                $insert++;
                $new_password = strtolower(Str::random(8));
                $user = User::where('guru_id', $d->guru_id)->first();
                $user_email = $this->check_email($d, 'guru_id');
                if($user){
                    $user->name = $d->nama_lengkap;
                    $user->save();
                } else {
                    $user = User::create([
                        'name' => $d->nama_lengkap,
						'email' => $user_email,
						'nuptk'	=> $d->nuptk,
						'password' => bcrypt($new_password),
						'last_sync'	=> now(),
						'sekolah_id'	=> request()->sekolah_id,
						'password_dapo'	=> md5($new_password),
						'guru_id'	=> $d->guru_id,
						'default_password' => $new_password,
                    ]);
                }
                $user->removeRoles($all_role, request()->periode_aktif);
                if($jenis_tu->contains($d->jenis_ptk_id)){
                    $role = Role::where('name', 'tu')->first();
                } elseif($asesor->contains($d->jenis_ptk_id)){
                    $role = Role::where('name', 'user')->first();
                } else {
                    $role = Role::where('name', 'guru')->first();
                }
                if($role && !$user->hasRole($role, request()->periode_aktif)){
                    $user->addRole($role, request()->periode_aktif);
                }
                $find_rombel = RombonganBelajar::where('guru_id', $d->guru_id)->where('semester_id', request()->semester_id)->where('jenis_rombel', 1)->first();
				if($find_rombel){
                    if(!$user->hasRole($WalasRole, request()->periode_aktif)){
                        $user->addRole($WalasRole, request()->periode_aktif);
                    }
                } else {
                    $user->removeRole($WalasRole, request()->periode_aktif);
                }
                $find_pilihan = RombonganBelajar::where('guru_id', $d->guru_id)->where('semester_id', request()->semester_id)->where('jenis_rombel', 16)->first();
				if($find_pilihan){
                    if(!$user->hasRole($PilihanRole, request()->periode_aktif)){
                        $user->addRole($PilihanRole, request()->periode_aktif);
                    }
                } else {
                    $user->removeRole($PilihanRole, request()->periode_aktif);
                }
                $find_mapel_p5 = Pembelajaran::where('guru_id', $d->guru_id)->where('semester_id', request()->semester_id)->where('mata_pelajaran_id', 200040000)->has('tema')->first();
                if($find_mapel_p5){
                    if(!$user->hasRole($p5Role, request()->periode_aktif)){
                        $user->addRole($p5Role, request()->periode_aktif);
                    }
                } else {
                    $user->removeRole($p5Role, request()->periode_aktif);
                }
                $find_mapel_pkl = Pembelajaran::where('guru_id', $d->guru_id)->where('semester_id', request()->semester_id)->where('mata_pelajaran_id', 800001000)->first();
                if($find_mapel_pkl){
                    if(!$user->hasRole($pembimbingRole, request()->periode_aktif)){
                        $user->addRole($pembimbingRole, request()->periode_aktif);
                    }
                } else {
                    $user->removeRole($pembimbingRole, request()->periode_aktif);
                }
                $find_ekskul = Ekstrakurikuler::where('guru_id', $d->guru_id)->where('semester_id', request()->semester_id)->first();
                if($find_ekskul){
                    if(!$user->hasRole($PembinaRole, request()->periode_aktif)){
                        $user->addRole($PembinaRole, request()->periode_aktif);
                    }
                } else {
                    $user->removeRole($PembinaRole, request()->periode_aktif);
                }
            }
        }
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Pengguna PTK berhasil diperbaharui',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => 'Pengguna PTK gagal diperbaharui. Silahkan coba beberapa saat lagi!',
            ];
        }
        return $data;
    }
    private function generate_pd(){
        $insert = 0;
        $role = Role::where('name', 'siswa')->first();
        $adminRole = Role::where('name', 'admin')->first();
        PesertaDidik::where(function($query){
            $query->whereDoesntHave('pd_keluar', function($query){
                $query->where('semester_id', request()->semester_id);
            });
            $query->where('sekolah_id', request()->sekolah_id);
        })->orderBy('peserta_didik_id')->chunk(100, function ($data) use ($role, $adminRole, &$insert) {
            foreach($data as $d){
                $insert++;
                $new_password = strtolower(Str::random(8));
                $user = User::where('peserta_didik_id', $d->peserta_didik_id)->first();
                if(!$user){
                    $user_email = $this->check_email($d, 'peserta_didik_id');
                    $user = User::create([
                        'name' => $d->nama,
						'email' => $user_email,
						'nisn'	=> $d->nisn,
						'password' => bcrypt($new_password),
						'last_sync'	=> now(),
						'sekolah_id'	=> request()->sekolah_id,
						'password_dapo'	=> md5($new_password),
						'peserta_didik_id'	=> $d->peserta_didik_id,
						'default_password' => $new_password,
                    ]);
                } elseif(!$user->email){
                    $user_email = $this->check_email($d, 'peserta_didik_id');
                    $user->email = $user_email;
                    $user->save();
                }
                if(!$d->email){
                    $d->email = $user->email;
                    $d->save();
                }
                $user->removeRole($adminRole, request()->periode_aktif);
                if(!$user->hasRole($role, request()->periode_aktif)){
                    $user->addRole($role, request()->periode_aktif);
                }
            }
        });
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Pengguna Peserta Didik berhasil diperbaharui',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => 'Pengguna Peserta Didik gagal diperbaharui. Silahkan coba beberapa saat lagi!',
            ];
        }
        return $data;
    }
    private function check_email($user, $field){
        $loggedUser = auth()->user();
        $random = Str::random(8);
		$user->email = ($user->email != $loggedUser->email) ? $user->email : strtolower($random).'@erapor-smk.net';
		$user->email = strtolower($user->email);
        if($field == 'guru_id'){
            $find_user_email = User::where('email', $user->email)->where($field, '<>', $user->ptk_id)->first();
		} else {
            $find_user_email = User::where('email', $user->email)->where($field, '<>', $user->peserta_didik_id)->first();
		}
        $find_user_email = User::where('email', $user->email)->first();
		if($find_user_email){
			$user->email = strtolower($random).'@erapor-smk.net';
		}
        return $user->email;
    }
    public function users_roles(){
        //update beda server
        if (request()->isMethod('post')) {
            $data = [
                'color' => 'success',
                'title' => 'Success!',
                'text' => 'Kategori Barang berhasil disimpan!',
            ];
        } else {
            $data = [
                'users' => User::with('roles')->orderBy(request()->sortBy, request()->orderBy)
                ->when(request()->q, function($query) {
                    $query->where('name', 'LIKE', '%' . request()->q . '%');
                    $query->orWhere('email', 'LIKE', '%' . request()->q . '%');
                })->when(request()->role_id, function($query) {
                    $query->whereHas('roles', function($query){
                        $query->where('id', request()->role_id);
                    });
                })->paginate(request()->per_page),
                'roles' => Role::withCount('users')->with(['users' => function($query){
                    $query->take(4);
                }])->orderBy('id')->get(),
            ];
        }
        return response()->json($data);
    }
    public function permissions(){
        if (request()->isMethod('post')) {
            $data = [
                'color' => 'success',
                'title' => 'Success!',
                'text' => 'Kategori Barang berhasil disimpan!',
            ];
        } else {
            $data = [];
        }
        return response()->json($data);
    }
    public function profile(){
        if (request()->isMethod('post')) {
            $data = [
                'color' => 'success',
                'title' => 'Success!',
                'text' => 'Kategori Barang berhasil disimpan!',
            ];
        } else {
            $data = [];
        }
        return response()->json($data);
    }
    public function languages(){
        if (request()->isMethod('post')) {
            request()->validate([
                'pt' => ['required', 'string', Rule::unique('languages')->ignore(request()->lang_id, 'lang_id')],
                'en' => ['required', 'string', Rule::unique('languages')->ignore(request()->lang_id, 'lang_id')],
                'te' => ['required', 'string', Rule::unique('languages')->ignore(request()->lang_id, 'lang_id')],
                'id' => ['required', 'string', Rule::unique('languages')->ignore(request()->lang_id, 'lang_id')],
            ]);
            $find = new Language;
            if(request()->lang_id){
                $find = $find::find(request()->lang_id);
            }
            $find->pt = request()->pt;
            $find->en = request()->en;
            $find->te = request()->te;
            $find->id = request()->id;
            $find->save();
            $data = [
                'color' => 'success',
                'title' => 'Success!',
                'text' => 'Kategori Barang berhasil disimpan!',
            ];
        } else {
            $data = Language::orderBy(request()->sortBy, request()->orderBy)
            ->when(request()->q, function($query) {
                $query->where('pt', 'LIKE', '%' . request()->q . '%');
                $query->orWhere('en', 'LIKE', '%' . request()->q . '%');
                $query->orWhere('te', 'LIKE', '%' . request()->q . '%');
                $query->orWhere('id', 'LIKE', '%' . request()->q . '%');
            })->paginate(request()->per_page);
        }
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function destroy($query, $id){
        $data = [
            'color' => 'error',
            'title' => 'Failed!',
            'text' => 'Query not found. Please try again later!',
        ];
        if($query == 'languages'){
            $find = Language::find($id);
            if($find && $find->delete()){
                $data = [
                    'color' => 'success',
                    'title' => 'Success!',
                    'text' => 'Language deleted successfully!',
                ];
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Failed!',
                    'text' => 'Language failed to delete. Please try again later!',
                ];
            }
        }
        return response()->json($data);
    }
}
