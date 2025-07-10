<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnggotaRombel extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'anggota_rombel';
	protected $primaryKey = 'anggota_rombel_id';
	protected $guarded = [];
	public function rombongan_belajar()
	{
		return $this->belongsTo(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function peserta_didik()
	{
		return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
	}
	public function nilai_akhir(){
		return $this->hasMany(NilaiAkhir::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function nilai_sumatif()
	{
		return $this->hasMany(NilaiSumatif::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function nilai_tp()
	{
		return $this->hasMany(NilaiTp::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function capaian_kompeten(){
		return $this->hasMany(TpNilai::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function tp_kompeten(){
		return $this->hasMany(TpNilai::class, 'anggota_rombel_id', 'anggota_rombel_id')->where('kompeten', 1);
	}
	public function tp_inkompeten(){
		return $this->hasMany(TpNilai::class, 'anggota_rombel_id', 'anggota_rombel_id')->where('kompeten', 0);
	}
	public function nilai_sumatif_semester()
	{
		return $this->hasOne(NilaiSumatif::class, 'anggota_rombel_id', 'anggota_rombel_id')->where('jenis', 'na');
	}
	public function nilai_akhir_mapel(){
		return $this->hasOne(NilaiAkhir::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function nilai_akhir_kurmer(){
		return $this->hasOne(NilaiAkhir::class, 'anggota_rombel_id', 'anggota_rombel_id')->where('kompetensi_id', 4);
	}
	public function nilai_akhir_induk(){
		return $this->hasOne(NilaiAkhir::class, 'anggota_rombel_id', 'anggota_rombel_id')->where('kompetensi_id', 99);
	}
	public function single_deskripsi_mata_pelajaran(){
		return $this->hasOne(DeskripsiMataPelajaran::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function anggota_ekskul(){
		return $this->hasManyThrough(
            AnggotaRombel::class,
            PesertaDidik::class,
            'peserta_didik_id', 
            'peserta_didik_id',
            'peserta_didik_id',
            'peserta_didik_id'
        );
	}
	public function single_nilai_ekstrakurikuler(){
		return $this->hasOne(NilaiEkstrakurikuler::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function tp_nilai(){
		return $this->hasMany(TpNilai::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
}
