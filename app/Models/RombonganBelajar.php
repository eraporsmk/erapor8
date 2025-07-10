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
	public function pd(){
		return $this->hasManyThrough(
            PesertaDidik::class,
            AnggotaRombel::class,
            'rombongan_belajar_id',
            'peserta_didik_id',
            'rombongan_belajar_id',
            'peserta_didik_id'
        );
	}
	public function kurikulum()
	{
		return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'kurikulum_id');
	}
	public function jurusan_sp()
	{
		return $this->belongsTo(JurusanSp::class, 'jurusan_sp_id', 'jurusan_sp_id');
	}
	public function anggota_rombel()
	{
		return $this->hasMany(AnggotaRombel::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function semester()
	{
		return $this->hasOne(Semester::class, 'semester_id', 'semester_id');
	}
	public function mapel()
	{
		return $this->hasOne(Pembelajaran::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
}
