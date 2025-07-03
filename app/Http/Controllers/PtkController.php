<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ptk;
use App\Models\Dudi;
use App\Models\Asesor;

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
    public function show($id){
        $ptk = Ptk::with(['agama', 'dudi'])->find($id);
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
        }
        $data = [
            'color' => 'success',
            'title' => 'Berhasil!',
            'text' => 'Data Guru berhasil diperbaharui',
        ];
        return response()->json($data);
    }
}
