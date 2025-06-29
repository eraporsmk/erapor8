<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GelarPtk extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'gelar_ptk';
	protected $primaryKey = 'gelar_ptk_id';
	protected $guarded = [];
	public function gelar(){
		return $this->hasOne(Gelar::class, 'gelar_akademik_id', 'gelar_akademik_id');
	}
	public function gelar_depan(){
		return $this->hasOne(Gelar::class, 'gelar_akademik_id', 'gelar_akademik_id')->where('posisi_gelar', 1);
	}
	public function gelar_belakang(){
		return $this->hasOne(Gelar::class, 'gelar_akademik_id', 'gelar_akademik_id')->where('posisi_gelar', 2);
	}
}
