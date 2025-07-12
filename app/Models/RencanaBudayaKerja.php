<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RencanaBudayaKerja extends Model
{
    use HasUuids;
    protected $table = 'rencana_budaya_kerja';
	protected $primaryKey = 'rencana_budaya_kerja_id';
	protected $guarded = [];
    public function pembelajaran()
	{
		return $this->belongsTo(Pembelajaran::class, 'pembelajaran_id', 'pembelajaran_id');
	}
	public function rombongan_belajar()
	{
		return $this->belongsTo(RombonganBelajar::class, 'rombongan_belajar_id', 'rombongan_belajar_id');
	}
	public function aspek_budaya_kerja()
	{
		return $this->hasMany(AspekBudayaKerja::class, 'rencana_budaya_kerja_id', 'rencana_budaya_kerja_id');
	}
	public function catatan_budaya_kerja()
	{
		return $this->hasOne(CatatanBudayaKerja::class, 'rencana_budaya_kerja_id', 'rencana_budaya_kerja_id');
	}
	public function all_catatan_budaya_kerja()
	{
		return $this->hasMany(CatatanBudayaKerja::class, 'rencana_budaya_kerja_id', 'rencana_budaya_kerja_id');
	}
}
