<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Ptk extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'guru';
	protected $primaryKey = 'guru_id';
	protected $guarded = [];
    protected $appends = ['nama_lengkap'];
    protected function namaLengkap(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => strtoupper($attributes['nama']),
        );
    }
    public function ptk_keluar()
    {
        return $this->hasOne(PtkKeluar::class, 'guru_id', 'guru_id');
    }
    public function bimbing_pd()
	{
		return $this->hasOne(BimbingPd::class, 'guru_id', 'guru_id');
	}
}
