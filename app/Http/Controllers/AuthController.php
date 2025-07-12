<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Semester;
use App\Models\Team;
use App\Models\Sekolah;
use App\Models\MstWilayah;
use App\Models\Role;
use App\Models\User;
use App\Models\Ptk;
use App\Models\PesertaDidik;
use Carbon\Carbon;
use Hash;
use Mail;
use DB; 

class AuthController extends Controller
{
    public function semester(){
        $data = [
            'semester' => Semester::whereHas('tahun_ajaran', function($query){
                $query->where('periode_aktif', 1);
            })->orderBy('semester_id', 'DESC')->get(),
            'semester_id' => Semester::where('periode_aktif', 1)->first()->semester_id,
            'allowRegister' => config('app.registration'),
            'sekolah' => Sekolah::count(),
            'bg_login' => get_setting('bg_login'),
        ];
        return response()->json($data);
    }
    public function register(Request $request){
        $request->validate(
            [
                'npsn' => 'required',
                'email'=>'required|string|unique:users',
                'password'=>'required|string',
            ],
            [
                'npsn.required' => 'NPSN tidak boleh kosong',
                'email.required' => 'Email tidak boleh kosong',
                'email.unique' => 'Email telah terdaftar',
                'password.required' => 'Password tidak boleh kosong'
            ]
        );
        try {
            $data_sync = [
                'npsn' => $request->npsn,
                'email' => $request->email,
                'password' => $request->password,
            ];
            $response = Http::post('http://sync.erapor-smk.net/api/v8/register', $data_sync);
            $data = $response->object();
            if($response->successful()){
                return $this->create_user($data, $request->email, $request->password);
            } else {
                return response()->json([
                    'error'=> TRUE,
                    'message' => $data->message,
                    'errors' => $data->errors,
                ]);
            }
        } catch (\Exception $e){
            return response()->json([
                'error'=> TRUE,
                'message' => $e->getMessage()
            ]);
        }
    }
    public function login(Request $request)
    {
        $login = request()->input('email');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $namaField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'Email' : 'Username';
        request()->merge([$field => $login]);
        $request->validate(
            [
                $field => 'required|string|exists:users,'.$field ,
                'password' => 'required|string',
                'remember_me' => 'boolean'
            ],
            [
                $field.'.required' => $namaField.' tidak boleh kosong',
                $field.'.exists' => $namaField.' tidak terdaftar',
                'password.required' => 'Password tidak boleh kosong'
            ]
        );
        $credentials = request([$field,'password']);
        if(!Auth::attempt($credentials)){
            return response()->json([
                'user' => NULL,
                'errors' => [
                    'password' => ['Password salah!'],
                ],
                'message' => [
                    'password' => 'Password salah!',
                ]
            ],422);
        }

        $pengguna = $request->user();
        $user = $this->loggedUser($pengguna);
        return response()->json($user);
        return response()->json([
            'accessToken' =>$token,
            'userData' => $pengguna,
            'token_type' => 'Bearer',
            'userAbilityRules' => [
                [
                    'action' => 'manage',
                    'subject' => 'all',
                ]
            ],
        ]);
    }
    private function loggedUser($user){
        $semester = Semester::find(request()->semester_id);
        if($user->sekolah_id && !$user->peserta_didik_id && !$user->guru_id){
            $team = Team::updateOrCreate([
                'name' => $semester->nama,
                'display_name' => $semester->nama,
                'description' => $semester->nama,
            ]);
            if(!$user->hasRole('admin', $semester->nama)){
                $user->attachRole('admin', $team);
            }
        }
        $general  = [
            [
                'action' => 'read',
                'subject' => 'Web'
            ]
        ];
        $admin = [];
        $tu = [];
        $waka = [];
        $wali = [];
        $pilihan = [];
        $kaprog = [];
        $projek = [];
        $internal = [];
        $pembina_ekskul = [];
        $pembimbing = [];
        $guru = [];
        $siswa = [];
        if($user->hasRole('waka', $semester->nama)){ 
            $waka = [
                [
                    'action' => 'read',
                    'subject' => 'Waka'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Rombel'
                ],
            ];
        }
        if($user->hasRole('pilihan', $semester->nama)){
            $pilihan = [
                [
                    'action' => 'read',
                    'subject' => 'Pilihan'
                ],
            ];
        }
        if($user->hasRole('tu', $semester->nama)){
            $pilihan = [
                [
                    'action' => 'read',
                    'subject' => 'TataUsaha'
                ],
            ];
        }
        if($user->hasRole('wali', $semester->nama)){
            if($semester->semester == 1){
                $wali = [
                    [
                        'action' => 'read',
                        'subject' => 'Password_pd',
                    ],
                    [
                        'action' => 'read',
                        'subject' => 'Wali'
                    ],
                ];
            } else {
                $wali = [
                    [
                        'action' => 'read',
                        'subject' => 'Password_pd',
                    ],
                    [
                        'action' => 'read',
                        'subject' => 'Wali'
                    ],
                    [
                        'action' => 'read',
                        'subject' => 'Kenaikan'
                    ],
                ];
            }
            if($semester->tahun_ajaran_id < '2023'){
                $wali = array_merge($wali, [
                    [
                        'action' => 'read',
                        'subject' => 'Wali_pkl',
                    ],
                ]);
            }
        }
        if($user->hasRole('kaprog', $semester->nama)){ 
            $kaprog = [
                [
                    'action' => 'read',
                    'subject' => 'Kaprog'
                ],
            ];
        }
        if($user->hasRole('guru-p5', $semester->nama)){ 
            $projek = [
                [
                    'action' => 'read',
                    'subject' => 'Projek'
                ],
            ];
        }
        if($user->hasRole('internal', $semester->nama)){ 
            $internal = [
                [
                    'action' => 'read',
                    'subject' => 'Internal'
                ],
            ];
        }
        if($user->hasRole('pembina_ekskul', $semester->nama)){ 
            $pembina_ekskul = [
                [
                    'action' => 'read',
                    'subject' => 'Ekskul'
                ],
            ];
        }
        if($user->hasRole('pembimbing', $semester->nama) && $semester->tahun_ajaran_id >= '2023'){ 
            $pembimbing = [
                [
                    'action' => 'read',
                    'subject' => 'Pkl'
                ],
            ];
        }
        if($user->hasRole('admin', $semester->nama)){
            $admin = [
                [
                    'action' => 'read',
                    'subject' => 'Administrator'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Guru'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Siswa'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Siswa_Keluar'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Rombel'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Akses'
                ]
            ];
        }
        if($user->hasRole('tu', $semester->nama)){ 
            $tu = [
                [
                    'action' => 'read',
                    'subject' => 'Ref_Guru'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Rombel'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Guru'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Siswa'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Siswa_Keluar'
                ],
            ];
        }
        if($user->hasRole('guru', $semester->nama)){
            $guru = [
                [
                    'action' => 'read',
                    'subject' => 'Guru'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Ref_Siswa'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Akses'
                ]
            ];
        } 
        if($user->hasRole('siswa', $semester->nama)){ 
            $siswa = [
                [
                    'action' => 'read',
                    'subject' => 'Siswa'
                ],
                [
                    'action' => 'read',
                    'subject' => 'Akses'
                ],
            ];
        }
        $userAbility = array_filter(array_merge($general, $admin, $tu, $guru, $waka, $wali, $pilihan, $kaprog, $projek, $internal, $pembina_ekskul, $pembimbing, $siswa));
        if($user->allPermissions(['display_name'], $semester->nama)->count()){
            $roles = $user->allPermissions(['display_name'], $semester->nama)->pluck('display_name')->toArray();
        } else {
            $roles = $user->roles->unique()->pluck('display_name')->toArray();
        }
        $sekolah = $user->sekolah;
        unset($user->sekolah);
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;
        $data = [
            'accessToken' =>  $token,
            'userData' => $user,
            'token_type' => 'Bearer',
            'sekolah' => $sekolah,
            'semester' => $semester,
            'userAbility' => $userAbility,
            'roles' => $roles,
        ];
        return $data;
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
    public function user(){
        $user = request()->user();
        if(request()->isMethod('POST')){
            request()->validate(
                [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->user_id, 'user_id')],
                    'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
                    'password' => [
                        'nullable',
                        'confirmed',
                    ],
                    'password_confirmation' => ['required_with:password'],
                ],
                [
                    'name.required' => 'Nama Lengkap tidak boleh kosong!',
                    'email.required' => 'Email tidak boleh kosong!',
                    'email.email' => 'Email tidak valid!',
                    'email.unique' => 'Email sudah terdaftar di Database!',
                    'photo.mimes' => 'Foto harus berekstensi jpg/jpeg/png',
                    'photo.max' => 'Ukuran foto tidak boleh lebih dari 1 MB!',
                    'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai dengan Kata sandi baru',
                    'password_confirmation.required_with' => 'Konfirmasi kata sandi tidak boleh kosong',
                ],
            );
            $user->name = request()->name;
            $user->email = request()->email;
            if(request()->password){
                $user->password = bcrypt(request()->password);
            }
            if(request()->photo){
                $photo = request()->photo->store('profile-photos');
                $user->profile_photo_path = 'profile-photos/'.basename($photo);
                $ptk = Ptk::find($user->guru_id);
                if($ptk){
                    $ptk->photo = 'profile-photos/'.basename($photo);
                    $ptk->save();
                }
                $pd = PesertaDidik::find($user->peserta_didik_id);
                if($pd){
                    $pd->photo = 'profile-photos/'.basename($photo);
                    $pd->save();
                }
            }
            if($user->save()){
                $data = [
                    'color' => 'success',
                    'title' => 'Berhasil!',
                    'text' => 'Profil Pengguna berhasil diperbaharui',
                    'user' => $user,
                ];
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Profil Pengguna gagal diperbaharui. Silahkan coba beberapa saat lagi!',
                    'user' => $user,
                ];
            }
        } else {
            $data = $user;
        }
        return response()->json($data);
    }
    private function create_user($data, $email, $password){
        if(!$data->data){
            return response()->json([
                'error'=> TRUE,
                'message' => $data->message,
                'data' => $data,
            ]);
        }
        $set_data = $data->data->sekolah;
        $bentuk_pendidikan = config('erapor.bentuk_pendidikan');
        $allowed = FALSE;
        if($bentuk_pendidikan){
            if(in_array($set_data->bentuk_pendidikan_id, $bentuk_pendidikan)){
                $allowed = TRUE;
            }
        }
        if($allowed){
            $get_kode_wilayah = $set_data->wilayah;
            $kode_wilayah = $set_data->kode_wilayah;
            $kecamatan = '-';
            $kabupaten = '-';
            $provinsi = '-';
            if($get_kode_wilayah){
                $kode_wilayah = $get_kode_wilayah->kode_wilayah;
                if($get_kode_wilayah->parrent_recursive){
                    $kecamatan = $get_kode_wilayah->parrent_recursive->nama;
                    if($get_kode_wilayah->parrent_recursive->parrent_recursive){
                        $kabupaten = $get_kode_wilayah->parrent_recursive->parrent_recursive->nama;
                        if($get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive){
                            $provinsi = $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->nama;
                            MstWilayah::updateOrCreate(
                                [
                                    'kode_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->kode_wilayah,
                                ],
                                [
                                    'nama' => $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->nama,
                                    'id_level_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->id_level_wilayah,
                                    'mst_kode_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->mst_kode_wilayah,
                                    'negara_id' => $get_kode_wilayah->parrent_recursive->parrent_recursive->parrent_recursive->negara_id,
                                    'last_sync' => now(),
                                ]
                            );
                        }
                        MstWilayah::updateOrCreate(
                            [
                                'kode_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->kode_wilayah,
                            ],
                            [
                                'nama' => $get_kode_wilayah->parrent_recursive->parrent_recursive->nama,
                                'id_level_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->id_level_wilayah,
                                'mst_kode_wilayah' => $get_kode_wilayah->parrent_recursive->parrent_recursive->mst_kode_wilayah,
                                'negara_id' => $get_kode_wilayah->parrent_recursive->parrent_recursive->negara_id,
                                'last_sync' => now(),
                            ]
                        );
                    }
                    MstWilayah::updateOrCreate(
                        [
                            'kode_wilayah' => $get_kode_wilayah->parrent_recursive->kode_wilayah,
                        ],
                        [
                            'nama' => $get_kode_wilayah->parrent_recursive->nama,
                            'id_level_wilayah' => $get_kode_wilayah->parrent_recursive->id_level_wilayah,
                            'mst_kode_wilayah' => $get_kode_wilayah->parrent_recursive->mst_kode_wilayah,
                            'negara_id' => $get_kode_wilayah->parrent_recursive->negara_id,
                            'last_sync' => now(),
                        ]
                    );
                }
                MstWilayah::updateOrCreate(
                    [
                        'kode_wilayah' => $get_kode_wilayah->kode_wilayah,
                    ],
                    [
                        'nama' => $get_kode_wilayah->nama,
                        'id_level_wilayah' => $get_kode_wilayah->id_level_wilayah,
                        'mst_kode_wilayah' => $get_kode_wilayah->mst_kode_wilayah,
                        'negara_id' => $get_kode_wilayah->negara_id,
                        'last_sync' => now(),
                    ]
                );
            }
            $sekolah = Sekolah::updateOrCreate(
                ['sekolah_id' => $set_data->sekolah_id],
                [
                    'npsn' 					=> $set_data->npsn,
                    'nss' 					=> $set_data->nss,
                    'nama' 					=> $set_data->nama,
                    'alamat' 				=> $set_data->alamat_jalan,
                    'desa_kelurahan'		=> $set_data->desa_kelurahan,
                    'kode_wilayah'			=> $kode_wilayah,
                    'kecamatan' 			=> $kecamatan,
                    'kabupaten' 			=> $kabupaten,
                    'provinsi' 				=> $provinsi,
                    'kode_pos' 				=> $set_data->kode_pos,
                    'lintang' 				=> $set_data->lintang,
                    'bujur' 				=> $set_data->bujur,
                    'no_telp' 				=> $set_data->nomor_telepon,
                    'no_fax' 				=> $set_data->nomor_fax,
                    'email' 				=> $set_data->email,
                    'website' 				=> $set_data->website,
                    'status_sekolah'		=> $set_data->status_sekolah,
                    'bentuk_pendidikan_id'  => $set_data->bentuk_pendidikan_id,
                    'last_sync'				=> now(),
                ]
            );
            $semester = Semester::where('periode_aktif', 1)->first();
            $user = User::create([
                'sekolah_id' => $sekolah->sekolah_id,
                'name' => 'Administrator',
                'email' => $email,
                'password' => bcrypt($password),
                'last_sync'	=> now(),
            ]);
            $adminRole = Role::where('name', 'admin')->first();
            $team = Team::updateOrCreate([
                'name' => $semester->nama,
                'display_name' => $semester->nama,
                'description' => $semester->nama,
            ]);
            $user->addRole($adminRole, $team);
            return response()->json([
                'error'=> FALSE,
                'message' => 'Register berhasil'
            ]);
        } else {
            return response()->json([
                'error'=> TRUE,
                'message' => 'Jenjang Sekolah Salah'
            ]);
        }
    }
    public function allow_register(){
        $data = [
            'allowRegister' => config('app.registration'),
            'sekolah' => Sekolah::count(),
        ];
        return response()->json($data);
    }
    public function reset_password(){
        if(request()->token){
            request()->validate(
                [
                    'email' => 'required|email|exists:users',
                    'password' => 'required|string|min:6|confirmed',
                    'password_confirmation' => 'required'
                ],
                [
                    'email.required' => 'Email tidak boleh kosong',
                    'email.email' => 'Email tidak valid',
                    'email.exists' => 'Email tidak terdaftar',
                    'password.required' => 'Password tidak boleh kosong',
                    'password.min' => 'Password minimal 6 karakter',
                    'password.confirmed' => 'Kombinasi password dan konfirmasi tidak sesuai',
                    'password_confirmation.required' => 'Konfirmasi password tidak boleh kosong'
                ]
            );
            $updatePassword = DB::table('password_resets')->where([
                'email' => request()->email,
                'token' => request()->token,
            ])->first();
            if(!$updatePassword){
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Token invalid. Silahkan lakukan reset password lagi!',
                    'error' => TRUE,
                ];
            } else {
                $user = User::where('email', request()->email)->update(['password' => Hash::make(request()->password)]);
                DB::table('password_resets')->where(['email'=> request()->email])->delete();
                $data = [
                    'color' => 'success',
                    'title' => 'Berhasil!',
                    'text' => 'Password berhasil diperbaharui',
                    'error' => FALSE,
                ];
            }
            return response()->json($data);
        } else {
            request()->validate(
                [
                    'email' => 'required|email|exists:users',
                ],
                [
                    'email.required' => 'Email tidak boleh kosong',
                    'email.email' => 'Email tidak valid',
                    'email.exists' => 'Email tidak terdaftar',
                ]
            );
            $token = Str::random(64);
            DB::table('password_resets')->insert([
                'email' => request()->email, 
                'token' => $token, 
                'created_at' => Carbon::now()
            ]);
            $mail = Mail::send('cetak.lupa-password', ['token' => $token], function($message){
                $message->to(request()->email);
                $message->subject('Reset Password');
            });
            $data = [
                'email' => request()->email,
                'token' => $token,
                'mail' => $mail,
            ];
            return response()->json($data);
        }
    }
    public function get_email(){
        $data = DB::table('password_resets')->where('token', request()->token)->first();
        return response()->json($data);
    }
}
