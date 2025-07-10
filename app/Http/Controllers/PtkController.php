<?php

namespace App\Http\Controllers;

use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Ptk;
use App\Models\Dudi;
use App\Models\Asesor;
use App\Models\Agama;

class PtkController extends Controller
{
    public function index(){
        $data = Ptk::where(function($query){
            $query->whereIn('jenis_ptk_id', jenis_gtk(request()->jenis_gtk));
            $query->where('sekolah_id', request()->sekolah_id);
            $query->whereDoesntHave('ptk_keluar', function($query){
                $query->where('semester_id', request()->semester_id);
            });
        })->with(['sekolah' => function($query){
            $query->select('sekolah_id', 'nama');
        }])->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama', 'ILIKE', '%' . request()->q . '%');
            $query->orWhere('nuptk', 'ILIKE', '%' . request()->q . '%');
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function show(){
        $ptk = Ptk::with(['agama', 'dudi'])->find(request()->guru_id);
        $data = [
            'ptk' => $ptk,
            'dudi' => (request()->asesor) ? Dudi::orderBy('nama')->where('sekolah_id', request()->sekolah_id)->get() : [],
            'dudi_id' => ($ptk->dudi) ? $ptk->dudi->dudi_id : NULL,

        ];
        return response()->json($data);
    }
    public function update(){
        $ptk = Ptk::find(request()->guru_id);
        $ptk->gelar_depan = request()->gelar_depan;
        $ptk->gelar_belakang = request()->gelar_belakang;
        $ptk->save();
        if(request()->asesor){
            if(request()->dudi_id){
                Asesor::updateOrCreate(
                    [
                        'sekolah_id' => request()->sekolah_id,
                        'guru_id' => request()->guru_id,
                    ],
                    [
                        'dudi_id' => request()->dudi_id,
                        'last_sync' => now()
                    ]
                );
            } else {
                Asesor::where(function($query){
                    $query->where('guru_id', request()->guru_id);
                    $query->where('dudi_id', request()->dudi_id);
                })->delete();
            }
        }
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data Guru berhasil diperbaharui',
        ];
        return response()->json($data);
    }
    public function upload(){
        request()->validate(
            [
                'template_excel' => 'required|mimes:xlsx',
            ],
            [
                'template_excel.required' => 'File Excel tidak boleh kosong',
                'template_excel.mimes' => 'File harus berupa file dengan tipe: xlsx.',
            ]
        );
        $template_excel = request()->template_excel->store('files', 'public');
        $data = ['imported_data' => $this->imported_data($template_excel)];
        return response()->json($data);
    }
    private function imported_data($template_excel){
        $imported_data = (new FastExcel)->import(storage_path('/app/public/'.$template_excel));
        $collection = collect($imported_data);
        $multiplied = $collection->map(function ($items, $key) {
            foreach($items as $k => $v){
                $k = str_replace('.','',$k);
                $k = str_replace(' ','_',$k);
                $k = str_replace('/','_',$k);
                $k = strtolower($k);
                $item[$k] = $v;
            }
            return $item;
        });
        $result = [];
        foreach($multiplied->all() as $urut => $data){
            $result[$urut] = [
                'no' => $data['no'],
                'nama' => $data['nama'],
                'nuptk' => $data['nuptk'],
                'nip' => $data['nip'],
                'nik' => $data['nik'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => (is_object($data['tanggal_lahir'])) ? $data['tanggal_lahir']->format('Y-m-d') : now()->format('Y-m-d'),
                'agama' => $data['agama'],
                'alamat_jalan' => $data['alamat_jalan'],
                'rt' => $data['rt'],
                'rw' => $data['rw'],
                'desa_kelurahan' => $data['desa_kelurahan'],
                'kecamatan' => $data['kecamatan'],
                'kodepos' => $data['kodepos'],
                'telp_hp' => $data['telp_hp'],
                'email' => $data['email'],
            ];
        }
        return $result;
    }
    public function simpan(){
        request()->validate(
            [
                'nama.*' => 'required',
                'nik.*' => 'required|numeric|digits:16|unique:guru,nik,NULL,guru_id,deleted_at,NULL',
                'jenis_kelamin.*' => 'required',
                'tempat_lahir.*' => 'required',
                'tanggal_lahir.*' => 'required|date|date_format:Y-m-d',
                'agama.*' => 'required|exists:pgsql.ref.agama,nama',
                'email.*' => 'required|unique:guru,email,NULL,guru_id,deleted_at,NULL',
            ],
            [
                'nama.*.required' => 'Nama tidak boleh kosong!',
                'nik.*.required' => 'NIK tidak boleh kosong!',
                'nik.*.numeric' => 'NIK harus berupa angka!',
                'nik.*.digits' => 'NIK harus 16 digit!',
                'jenis_kelamin.*.required' => 'Jenis Kelamin tidak boleh kosong!',
                'tempat_lahir.*.required' => 'Tanggal Lahir tidak boleh kosong!',
                'tanggal_lahir.*.required' => 'Tanggal Lahir tidak boleh kosong!',
                'tanggal_lahir.*.date' => 'Tanggal Lahir format tanggal salah!',
                'tanggal_lahir.*.date_format' => 'Tanggal Lahir format tanggal salah!',
                'agama.*.required' => 'Agama tidak boleh kosong!',
                'agama.*.exists' => 'Agama tidak ditemukan!',
                'email.*.required' => 'Email tidak boleh kosong!',
                'email.*.unique' => 'Email sudah terdaftar!',
                'nik.*.unique' => 'NIK sudah terdaftar!',
            ]
        );
        foreach(request()->nama as $urut => $nama){
            $agama = Agama::where('nama', request()->agama[$urut])->first();
            Ptk::updateOrcreate(
                [
                    'nik' => request()->nik[$urut],
                ],
                [
                    'guru_id' => Str::uuid(),
                    'sekolah_id' => request()->sekolah_id,
                    'status_kepegawaian_id' => 0,
                    'kode_wilayah' => '016001AA',
                    'nama' => $nama,
                    'nuptk' => request()->nuptk[$urut],
                    'nip' => request()->nip[$urut],
                    'jenis_kelamin' => request()->jenis_kelamin[$urut],
                    'tempat_lahir' => request()->tempat_lahir[$urut],
                    'tanggal_lahir' => request()->tanggal_lahir[$urut],
                    'agama_id' => $agama->agama_id,
                    'alamat' => request()->alamat_jalan[$urut],
                    'rt' => request()->rt[$urut],
                    'rw' => request()->rw[$urut],
                    'desa_kelurahan' => request()->desa_kelurahan[$urut],
                    'kecamatan' => request()->kecamatan[$urut],
                    'kode_pos' => request()->kodepos[$urut],
                    'no_hp' => request()->telp_hp[$urut],
                    'email' => request()->email[$urut],
                    'jenis_ptk_id' => (request()->jenis_gtk == 'asesor') ? 98 : 97,
                    'last_sync' => now(),
                ]
            );
        }
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data '.ucfirst(request()->jenis_gtk).' berhasil disimpan',
            'request' => request()->all(),
        ];
        return response()->json($data);
    }
    public function hapus(){
        $find = Ptk::find(request()->guru_id);
        if($find){
            if($find->delete()){
                $data = [
                    'color' => 'success',
                    'title' => 'Berhasil!',
                    'text' => 'Data '.request()->data.' berhasil dihapus',
                ];
            } else {
                $data = [
                    'color' => 'error',
                    'title' => 'Gagal!',
                    'text' => 'Data '.request()->data.' gagal dihapus',
                ];
            }
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => 'Data '.request()->data.' tidak ditemukan',
            ];
        }
        return response()->json($data);
    }
}
