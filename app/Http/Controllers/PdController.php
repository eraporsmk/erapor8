<?php

namespace App\Http\Controllers;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\PesertaDidik;
use App\Models\JurusanSp;
use App\Models\RombonganBelajar;
use App\Models\Pekerjaan;
use App\Models\User;

class PdController extends Controller
{
    private function kondisi(){
        return function($query){
            if(request()->status == 'aktif' || request()->status == 'password'){
                $query->whereHas('anggota_rombel', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                    $query->whereHas('rombongan_belajar', function($query){
                        $query->where('sekolah_id', request()->sekolah_id);
                        $query->where('semester_id', request()->semester_id);
                        $query->where('jenis_rombel', 1);
                        if(auth()->user()->hasRole('wali', request()->periode_aktif)){
                            $query->where('guru_id', auth()->user()->guru_id);
                        }
                    });
                });
            } else {
                $query->whereHas('pd_keluar', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                });
            }
        };
    }
    public function index(){
        $data = PesertaDidik::where($this->kondisi())
        ->with([
            'agama', 
            'anggota_rombel' => function($query){
                $query->withWhereHas('rombongan_belajar', function($query){
                    $query->where('sekolah_id', request()->sekolah_id);
                    $query->where('semester_id', request()->semester_id);
                    $query->where('jenis_rombel', 1);
                });
            },
            'user' => function($query){
                $query->select('user_id', 'email', 'peserta_didik_id', 'password', 'default_password', 'last_login_at');
            }
        ])
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->where($this->kondisi());
            $query->orWhere('nisn', 'ILIKE', '%' . request()->q . '%');
            $query->where($this->kondisi());
            $query->orWhere('tempat_lahir', 'ILIKE', '%' . request()->q . '%');
            $query->where($this->kondisi());
            $query->orWhereHas('agama', function($query){
                $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            });
            $query->where($this->kondisi());
        })->when(request()->tingkat, function($query){
            $query->whereHas('anggota_rombel', function($query){
                $query->wherehas('rombongan_belajar', function($query){
                    $query->where('semester_id', request()->semester_id);
                    $query->where('tingkat', request()->tingkat);
                });
            });
            $query->where($this->kondisi());
        })->when(request()->jurusan_sp_id, function($query){
            $query->whereHas('anggota_rombel', function($query){
                $query->wherehas('rombongan_belajar', function($query){
                    $query->where('semester_id', request()->semester_id);
                    $query->where('jurusan_sp_id', request()->jurusan_sp_id);
                });
            });
            $query->where($this->kondisi());
        })->when(request()->rombongan_belajar_id, function($query){
            $query->whereHas('anggota_rombel', function($query){
                $query->where('rombongan_belajar_id', request()->rombongan_belajar_id);
            });
            $query->where($this->kondisi());
        })->paginate(request()->per_page);
        $jurusan_sp = [];
        $rombel = [];
        if(request()->tingkat){
            $jurusan_sp = JurusanSp::select('jurusan_sp_id', 'nama_jurusan_sp')->whereHas('rombongan_belajar', function($query){
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
            })->orderBy('nama_jurusan_sp')->get();
        }
        if(request()->jurusan_sp_id){
            $rombel = RombonganBelajar::select('rombongan_belajar_id', 'nama', 'jurusan_sp_id')->where(function($query){
                $query->where('tingkat', request()->tingkat);
                $query->where('jurusan_sp_id', request()->jurusan_sp_id);
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('semester_id', request()->semester_id);
            })->orderBy('tingkat')->orderBy('nama')->get();
        }
        return response()->json(['status' => 'success', 'data' => $data, 'jurusan_sp' => $jurusan_sp, 'rombel' => $rombel]);
    }
    public function show($id){
        $data = [
            'pd' => PesertaDidik::with(['agama', 'pekerjaan_ayah', 'pekerjaan_ibu', 'pekerjaan_wali'])->find($id),
            'pekerjaan' => Pekerjaan::orderBy('pekerjaan_id')->get(),
        ];
        return response()->json($data);
    }
    public function update(){
        request()->validate(
            [
                'email' => [
                    'required', 
                    Rule::unique('peserta_didik')->ignore(request()->peserta_didik_id, 'peserta_didik_id'),
                    Rule::unique('users')->ignore(request()->peserta_didik_id, 'peserta_didik_id')
                ],
                'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
            ],
            [
                'email.required' => 'Email tidak boleh kosong',
                'email.unique' => 'Email telah terdaftar',
                'photo.image' => 'Foto harus berupa berkas gambar',
                'photo.mimes' => 'Foto harus berekstensi (jpg, jpeg, png)',
                'photo.max' => 'Foto maksimal 1 MB.',
            ]
        );
        $pd = PesertaDidik::find(request()->peserta_didik_id);
        $pd->status = request()->status;
        $pd->anak_ke = request()->anak_ke;
        $pd->diterima_kelas = request()->diterima_kelas;
        $pd->email = request()->email;
        $pd->nama_wali = request()->nama_wali;
        $pd->alamat_wali = request()->alamat_wali;
        $pd->telp_wali = request()->telp_wali;
        $pd->kerja_wali = request()->kerja_wali;
        $photo_user = NULL;
        if(request()->photo){
            $photo = request()->photo->store('profile-photos');
            $pd->photo = $photo_user = 'profile-photos/'.basename($photo);
        }
        if($pd->save()){
            $user = User::where('peserta_didik_id', request()->peserta_didik_id)->first();
            if($user){
                $user->email = request()->email;
                if($photo_user){
                    $user->profile_photo_path = $photo_user;
                } else {
                    $user->profile_photo_path = str_replace('/storage/', '', $pd->getOriginal('photo'));
                }
                $user->save();
            }
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => 'Data '.$pd->nama.' berhasil diperbaharui',
                'getOriginal' => $pd->getOriginal('photo'),
                'photo' => $pd->photo,
                'asli' => $pd->getOriginal(),
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => 'Data '.$pd->nama.' Gagal diperbaharui. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
}
