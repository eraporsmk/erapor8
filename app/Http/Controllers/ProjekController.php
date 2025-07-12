<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\RencanaBudayaKerja;
use App\Models\AspekBudayaKerja;

class ProjekController extends Controller
{
    public function index(){
        $data = RencanaBudayaKerja::withWhereHas('pembelajaran', function($query){
            $query->with(['rombongan_belajar' => function($query){
                $query->select('rombongan_belajar_id', 'nama');
            }]);
            $query->where('sekolah_id', request()->sekolah_id);
			$query->where('semester_id', request()->semester_id);
            $query->whereHas('induk', function($query){
                $query->where('guru_id', request()->guru_id);
                $query->orWhere('guru_pengajar_id', request()->guru_id);
            });
        })
        ->withCount('aspek_budaya_kerja')
        ->orderBy(request()->sortby, request()->sortbydesc)
        ->when(request()->q, function($query) {
            $query->where('nama_penilaian', 'ILIKE', '%' . request()->q . '%');
            $query->orWhereHas('pembelajaran', function($query){
                $query->whereNotNull('kelompok_id');
                $query->whereNotNull('no_urut');
                $query->where('sekolah_id', request()->sekolah_id);
                $query->where('nama_mata_pelajaran', 'ILIKE', '%' . request()->q . '%');
            });
        })->paginate(request()->per_page);
        return response()->json(['status' => 'success', 'data' => $data]);
    }
    public function save(){
        $insert = 0;
        $text = 'Unknow';
        if(request()->data == 'rencana'){
            request()->validate(
                [
                    'nama_projek' => 'required',
                    'deskripsi' => 'required',
                    'no_urut' => 'required|integer',
                ],
                [
                    'nama_projek.required' => 'Nama Projek tidak boleh kosong!!',
                    'deskripsi.required' => 'Deskripsi Projek tidak boleh kosong!!',
                    'no_urut.required' => 'Nomor Urut Projek tidak boleh kosong!!',
                    'no_urut.integer' => 'Nomor Urut Projek harus berupa angka!!',
                ]
            );
            $text = 'Rencana Penilaian P5';
            $rencana = RencanaBudayaKerja::create([
                'sekolah_id' => request()->sekolah_id,
                'rombongan_belajar_id' => request()->rombongan_belajar_id,
                'pembelajaran_id' => request()->pembelajaran_id,
                'nama' => request()->nama_projek,
                'deskripsi' => request()->deskripsi,
                'no_urut' => request()->no_urut,
                'last_sync' => now(),
            ]);
            foreach(request()->sub_elemen as $sub_elemen){
                $insert++;
                $segments = Str::of($sub_elemen)->split('/[\s#]+/');
                AspekBudayaKerja::create([
                    'sekolah_id' => request()->sekolah_id,
                    'rencana_budaya_kerja_id' => $rencana->rencana_budaya_kerja_id,
                    'budaya_kerja_id' => $segments->first(),
                    'elemen_id' => $segments->last(),
                    'last_sync' => now(),
                ]);
            }
        }
        if(request()->data == 'update-rencana'){
            $text = 'Rencana Penilaian P5';
            $insert = RencanaBudayaKerja::where('rencana_budaya_kerja_id', request()->rencana_budaya_kerja_id)->update([
                'nama' => request()->nama_projek,
                'deskripsi' => request()->deskripsi,
                'no_urut' => request()->no_urut,
            ]);
        }
        if($insert){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil disimpan',
                'request' => request()->all(),
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal disimpan. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
    public function show(){
        $data = RencanaBudayaKerja::with(['pembelajaran.rombongan_belajar', 'aspek_budaya_kerja'])->find(request()->rencana_budaya_kerja_id);
        return response()->json($data);
    }
    public function hapus(){
        $deleted = FALSE;
        $text = 'Unknow';
        if(request()->data == 'rencana'){
            $text = 'Rencana Penilaian P5';
            $find = RencanaBudayaKerja::find(request()->rencana_budaya_kerja_id);
            $deleted = $find->delete();
        }
        if($deleted){
            $data = [
                'color' => 'success',
                'title' => 'Berhasil!',
                'text' => $text.' berhasil di hapus',
            ];
        } else {
            $data = [
                'color' => 'error',
                'title' => 'Gagal!',
                'text' => $text.' gagal di hapus. Silahkan coba beberapa saat lagi!',
            ];
        }
        return response()->json($data);
    }
}
