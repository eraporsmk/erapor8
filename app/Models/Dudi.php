<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dudi extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'dudi';
	protected $primaryKey = 'dudi_id';
	protected $guarded = [];
    public function mou()
    {
        return $this->hasMany(Mou::class, 'dudi_id', 'dudi_id');
    }
    public function akt_pd(){
		return $this->hasManyThrough(
            AktPd::class,
            Mou::class,
            'dudi_id',
            'mou_id',
            'dudi_id',
            'mou_id'
        );
	}
}
