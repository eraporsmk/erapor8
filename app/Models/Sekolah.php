<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sekolah extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'sekolah';
	protected $primaryKey = 'sekolah_id';
	protected $guarded = [];
	public function kepala_sekolah(){
		return $this->hasOneThrough(
            Ptk::class,
            Kasek::class,
            'sekolah_id', // Foreign key on the cars table...
            'guru_id', // Foreign key on the owners table...
            'sekolah_id', // Local key on the mechanics table...
            'guru_id' // Local key on the cars table...
        );
	}
	public function ptk()
	{
		return $this->hasMany(Ptk::class, 'sekolah_id', 'sekolah_id');
	}
	public function pd_aktif(){
		return $this->hasManyThrough(
            AnggotaRombel::class,
            PesertaDidik::class,
            'sekolah_id',
            'peserta_didik_id',
            'sekolah_id',
            'peserta_didik_id'
        );
	}
	public function nilai_akhir(){
		return $this->hasMany(NilaiAkhir::class, 'sekolah_id', 'sekolah_id');
	}
	public function cp(){
		return $this->hasMany(DeskripsiMataPelajaran::class, 'sekolah_id', 'sekolah_id');
	}
	public function nilai_projek(){
		return $this->hasMany(CatatanBudayaKerja::class, 'sekolah_id', 'sekolah_id');
	}
	public function rombongan_belajar()
	{
		return $this->hasMany(RombonganBelajar::class, 'sekolah_id', 'sekolah_id');
	}
	public function peserta_didik(){
		return $this->hasMany(PesertaDidik::class, 'sekolah_id', 'sekolah_id');
	}
	public function anggota_rombel(){
		return $this->hasMany(AnggotaRombel::class, 'sekolah_id', 'sekolah_id');
	}
	public function pembelajaran()
	{
		return $this->hasMany(Pembelajaran::class, 'sekolah_id', 'sekolah_id');
	}
	public function ekstrakurikuler()
	{
		return $this->hasMany(RombonganBelajar::class, 'sekolah_id', 'sekolah_id')->where('jenis_rombel', 51);
	}
	public function dudi()
	{
		return $this->hasMany(Dudi::class, 'sekolah_id', 'sekolah_id');
	}
	public function user()
	{
		return $this->belongsTo(User::class, 'sekolah_id', 'sekolah_id');
	}
}
