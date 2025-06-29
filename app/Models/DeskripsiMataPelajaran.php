<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DeskripsiMataPelajaran extends Model
{
    use HasUuids;
    protected $table = 'deskripsi_mata_pelajaran';
	protected $primaryKey = 'deskripsi_mata_pelajaran_id';
	protected $guarded = [];
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function pembelajaran(){
		return $this->hasOne(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
	}
}
