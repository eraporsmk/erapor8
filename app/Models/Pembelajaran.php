<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Pembelajaran extends Model
{
	use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
	use \Staudenmeir\EloquentHasManyDeep\HasTableAlias;
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
	public function rombongan_belajar()
	{
		return $this->belongsTo(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function anggota_rombel(){
		return $this->hasManyThrough(
            AnggotaRombel::class,
			RombonganBelajar::class,
			'rombongan_belajar_id',
			'rombongan_belajar_id',
			'rombongan_belajar_id',
			'rombongan_belajar_id'
        );
    }
	public function pd_pkl()
    {
		return $this->hasManyDeep(
			PdPkl::class, 
			[RombonganBelajar::class, PraktikKerjaLapangan::class],
			[
				'rombongan_belajar_id', // Foreign key on the "Rombongan_belajar" table.
				'rombongan_belajar_id',    // Foreign key on the "Praktik_kerja_lapangan" table.
				'pkl_id'     // Foreign key on the "Pd_pkl" table.
			],
			[
				'rombongan_belajar_id', // Local key on the "Praktik_kerja_lapangan" table.
				'rombongan_belajar_id', // Local key on the "Rombongan_belajar" table.
				'pkl_id'  // Local key on the "Praktik_kerja_lapangan" table.
			]
		);
	}
	public function pengajar(){
		return $this->hasOne(Ptk::class, 'guru_id', 'guru_pengajar_id');
	}
	public function induk(){
		return $this->belongsTo(Pembelajaran::class, 'induk_pembelajaran_id', 'pembelajaran_id');
	}
	public function rencana_projek(){
		return $this->hasMany(RencanaBudayaKerja::class, 'pembelajaran_id', 'pembelajaran_id');
	}
	public function kelompok(){
		return $this->hasOne(Kelompok::class, 'kelompok_id', 'kelompok_id');
	}
	public function nilai_akhir_pengetahuan(){
		return $this->hasOne(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 1);
	}
	public function nilai_akhir_keterampilan(){
		return $this->hasOne(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 2);
	}
	public function nilai_akhir_kurmer(){
		return $this->hasOne(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 4);
	}
	public function single_deskripsi_mata_pelajaran(){
		return $this->hasOne(DeskripsiMataPelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
	}
	public function all_nilai_akhir_keterampilan(){
		return $this->hasMany(NilaiAkhir::class, 'pembelajaran_id', 'pembelajaran_id')->where('kompetensi_id', 2);
	}
}
