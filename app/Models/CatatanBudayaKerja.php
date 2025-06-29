<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CatatanBudayaKerja extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'catatan_budaya_kerja';
	protected $primaryKey = 'catatan_budaya_kerja_id';
	protected $guarded = [];
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
	public function budaya_kerja()
	{
		return $this->belongsTo(BudayaKerja::class, 'budaya_kerja_id', 'budaya_kerja_id');
	}
}
