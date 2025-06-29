<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ekstrakurikuler extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'ekstrakurikuler';
	protected $primaryKey = 'ekstrakurikuler_id';
	protected $guarded = [];
    public function guru()
	{
		return $this->hasOne(Ptk::class, 'guru_id', 'guru_id');
	}
    public function rombongan_belajar()
	{
		return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
}
