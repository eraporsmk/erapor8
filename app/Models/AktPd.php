<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AktPd extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'akt_pd';
	protected $primaryKey = 'akt_pd_id';
	protected $guarded = [];
	public function anggota_akt_pd(){
		return $this->hasMany(AnggotaAktPd::class, 'akt_pd_id', 'akt_pd_id');
	}
	public function bimbing_pd(){
		return $this->hasMany(BimbingPd::class, 'akt_pd_id', 'akt_pd_id');
	}
	public function dudi(){
		return $this->hasOneThrough(
            Dudi::class,
            Mou::class,
            'mou_id', // Foreign key on users table...
            'dudi_id', // Foreign key on history table...
            'mou_id', // Local key on suppliers table...
            'dudi_id' // Local key on users table...
        );
	}
	public function mou()
	{
		return $this->hasOne(Mou::class, 'mou_id', 'mou_id');
	}
	public function praktik_kerja_lapangan()
	{
		return $this->hasMany(PraktikKerjaLapangan::class, 'akt_pd_id', 'akt_pd_id');
	}
}
