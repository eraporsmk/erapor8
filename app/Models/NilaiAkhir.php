<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class NilaiAkhir extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'nilai_akhir';
	protected $primaryKey = 'nilai_akhir_id';
	protected $guarded = [];
	public function pembelajaran(){
		return $this->hasOne(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id')->whereNotNull('kelompok_id');
	}
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
}
