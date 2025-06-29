<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapaianPembelajaran extends Model
{
    public $incrementing = false;
	public $timestamps = false;
	protected $table = 'ref.capaian_pembelajaran';
	protected $primaryKey = 'cp_id';
	protected $guarded = [];
	public function mata_pelajaran(){
		return $this->hasOne(MataPelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
	public function pembelajaran(){
		return $this->hasOne(Pembelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
    public function tp()
    {
        return $this->hasMany(TujuanPembelajaran::class, 'cp_id', 'cp_id');
    }
}
