<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KompetensiDasar extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'ref.kompetensi_dasar';
	protected $primaryKey = 'kompetensi_dasar_id';
	protected $guarded = [];
	public function mata_pelajaran(){
		return $this->hasOne(MataPelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
	public function pembelajaran(){
		return $this->hasOne(Pembelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
}
