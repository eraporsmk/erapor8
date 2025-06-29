<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JurusanSp extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'jurusan_sp';
	protected $primaryKey = 'jurusan_sp_id';
	protected $guarded = [];
	public function rombongan_belajar(){
		return $this->hasMany(RombonganBelajar::class, 'jurusan_sp_id', 'jurusan_sp_id');
	}
	public function kurikulum(){
		return $this->hasMany(Kurikulum::class, 'jurusan_id', 'jurusan_id');
	}
	public function paket_ukk(){
		return $this->hasMany(PaketUkk::class, 'jurusan_id', 'jurusan_id');
	}
	public function jurusan()
	{
		return $this->belongsTo(Jurusan::class, 'jurusan_id', 'jurusan_id');
	}
}
