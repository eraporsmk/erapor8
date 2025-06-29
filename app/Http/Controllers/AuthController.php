<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Semester;
use App\Models\Team;
use App\Models\Sekolah;

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
        ];
        return response()->json($data);
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
                'message' => [
                    'password' => 'Password salah!',
                ]
            ],422);
        }

        $pengguna = $request->user();
        $tokenResult = $pengguna->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;
        $user = $this->loggedUser($pengguna);
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
        $user->ability = array_filter(array_merge($general, $admin, $tu, $guru, $waka, $wali, $pilihan, $kaprog, $projek, $internal, $pembina_ekskul, $pembimbing, $siswa));
        if($user->allPermissions(['display_name'], $semester->nama)->count()){
            $user->role = $user->allPermissions(['display_name'], $semester->nama)->implode('display_name', ', ');
            $user->roles = $user->allPermissions(['name'], $semester->nama)->pluck('name')->toArray();
            $user->check = 'ada';
        } else {
            $user->role = $user->roles->unique()->implode('display_name', ', ');
            $user->roles = $user->roles->unique()->pluck('name')->toArray();
            $user->check = 'ga ada';
        }
        $user->sekolah = $user->sekolah;
        $user->semester = $semester;
        return $user;
    }
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
