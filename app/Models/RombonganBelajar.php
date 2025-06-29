<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RombonganBelajar extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'rombongan_belajar';
	protected $primaryKey = 'rombongan_belajar_id';
	protected $guarded = [];
	public function pembelajaran()
	{
		return $this->hasMany(Pembelajaran::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function wali_kelas()
	{
		return $this->belongsTo(Ptk::class, 'guru_id', 'guru_id');
	}
	public function sekolah()
	{
		return $this->belongsTo(Sekolah::class, 'sekolah_id', 'sekolah_id');
	}
}
