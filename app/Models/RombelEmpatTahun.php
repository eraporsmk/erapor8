<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RombelEmpatTahun extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'rombel_4_tahun';
	protected $primaryKey = 'rombongan_belajar_id';
	protected $guarded = [];
	public function rombongan_belajar()
	{
		return $this->hasOne(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
}
