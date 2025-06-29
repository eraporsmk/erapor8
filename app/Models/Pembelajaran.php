<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Pembelajaran extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'pembelajaran';
	protected $primaryKey = 'pembelajaran_id';
	protected $guarded = [];
	public function matev_rapor(){
		return $this->hasOne(MatevRapor::class, 'pembelajaran_id', 'pembelajaran_id');
	}
	public function mata_pelajaran()
	{
		return $this->belongsTo(MataPelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
	public function all_nilai_akhir_pengetahuan(){
		return $this->hasMany(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 1);
	}
	public function all_nilai_akhir_kurmer(){
		return $this->hasMany(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 4);
	}
	public function deskripsi_mata_pelajaran(){
		return $this->hasMany(DeskripsiMataPelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
	}
	public function guru()
	{
		return $this->belongsTo(Ptk::class, 'guru_id', 'guru_id');
	}
	public function tema()
	{
		return $this->hasMany(Pembelajaran::class, 'induk_pembelajaran_id', 'pembelajaran_id');
	}
}
