<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnggotaRombel extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'anggota_rombel';
	protected $primaryKey = 'anggota_rombel_id';
	protected $guarded = [];
	public function rombongan_belajar()
	{
		return $this->belongsTo(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function peserta_didik()
	{
		return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
	}
}
