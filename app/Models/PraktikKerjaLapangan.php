<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Carbon\Carbon;

class PraktikKerjaLapangan extends Model
{
    use HasUuids;
    protected $table = 'praktik_kerja_lapangan';
	protected $primaryKey = 'pkl_id';
	protected $guarded = [];
	protected $appends = ['tanggal_mulai_str', 'tanggal_selesai_str', 'nama_dudi'];
	public function getTanggalMulaiStrAttribute()
	{
		return Carbon::parse($this->attributes['tanggal_mulai'])->translatedFormat('d F Y');
	}
	public function getTanggalSelesaiStrAttribute()
	{
		return Carbon::parse($this->attributes['tanggal_selesai'])->translatedFormat('d F Y');
	}
	public function rombongan_belajar()
	{
		return $this->belongsTo(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function akt_pd()
	{
		return $this->belongsTo(AktPd::class, 'akt_pd_id', 'akt_pd_id');
	}
	public function pd_pkl()
	{
		return $this->hasMany(PdPkl::class, 'pkl_id', 'pkl_id');
	}
	public function tp_pkl()
	{
		return $this->hasMany(TpPkl::class, 'pkl_id', 'pkl_id');
	}
	public function dudi(){
		return $this->hasOneThrough(
            Mou::class,
            AktPd::class,
            'akt_pd_id',
            'mou_id',
            'akt_pd_id',
            'mou_id'
        );
	}
	public function getNamaDudiAttribute()
	{
		return ($this->dudi) ? $this->dudi->nama_dudi : '';
	}
	public function guru()
	{
		return $this->belongsTo(Ptk::class, 'guru_id', 'guru_id');
	}
	public function nilai_pkl()
	{
		return $this->hasMany(NilaiPkl::class, 'pkl_id', 'pkl_id');
	}
}
