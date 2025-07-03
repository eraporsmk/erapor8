<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MataPelajaran extends Model
{
    public $incrementing = false;
	protected $table = 'ref.mata_pelajaran';
	protected $primaryKey = 'mata_pelajaran_id';
	protected $guarded = [];
	public function pembelajaran()
	{
		return $this->hasMany(Pembelajaran::class, 'mata_pelajaran_id', 'mata_pelajaran_id');
	}
}
