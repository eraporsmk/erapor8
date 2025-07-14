<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KenaikanKelas extends Model
{
    use HasUuids;
    protected $table = 'kenaikan_kelas';
	protected $primaryKey = 'kenaikan_kelas_id';
	protected $guarded = [];
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function rombongan_belajar(){
		return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
}
