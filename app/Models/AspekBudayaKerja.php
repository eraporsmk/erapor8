<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AspekBudayaKerja extends Model
{
    use HasUuids;
    protected $table = 'aspek_budaya_kerja';
	protected $primaryKey = 'aspek_budaya_kerja_id';
	protected $guarded = [];
	public function budaya_kerja()
	{
		return $this->belongsTo(BudayaKerja::class, 'budaya_kerja_id', 'budaya_kerja_id');
	}
	public function elemen_budaya_kerja()
	{
		return $this->belongsTo(ElemenBudayaKerja::class, 'elemen_id', 'elemen_id');
	}
	public function rencana_budaya_kerja()
	{
		return $this->belongsTo(RencanaBudayaKerja::class, 'rencana_budaya_kerja_id', 'rencana_budaya_kerja_id');
	}
}
