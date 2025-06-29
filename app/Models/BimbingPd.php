<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BimbingPd extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'bimbing_pd';
	protected $primaryKey = 'bimbing_pd_id';
	protected $guarded = [];
	public function akt_pd(){
        return $this->belongsTo(AktPd::class, 'akt_pd_id', 'akt_pd_id');
	}
    public function guru()
    {
        return $this->hasOne(Ptk::class, 'guru_id', 'guru_id');
    }
}
