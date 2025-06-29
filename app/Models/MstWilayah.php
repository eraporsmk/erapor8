<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstWilayah extends Model
{
    public $incrementing = false;
    public $timestamps = false;
    public $keyType = 'string';
	protected $table = 'ref.mst_wilayah';
	protected $primaryKey = 'kode_wilayah';
	protected $guarded = [];
	public function get_kabupaten(){
		return $this->hasOne(MstWilayah::class, 'kode_wilayah', 'mst_kode_wilayah');
    }
	public function parent()
    {
        return $this->belongsTo(MstWilayah::class, 'mst_kode_wilayah', 'kode_wilayah');
    }
    public function parrentRecursive()
    {
        return $this->parent()->with('parrentRecursive');
    }
}
