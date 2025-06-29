<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PesertaDidik extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'peserta_didik';
	protected $primaryKey = 'peserta_didik_id';
	protected $guarded = [];
	public function anggota_rombel()
	{
		return $this->hasOne(AnggotaRombel::class, 'peserta_didik_id', 'peserta_didik_id');
	}
	public function pd_keluar()
	{
		return $this->hasOne(PdKeluar::class, 'peserta_didik_id', 'peserta_didik_id');
	}
}
