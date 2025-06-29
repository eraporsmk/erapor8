<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NilaiBudayaKerja extends Model
{
    use SoftDeletes, HasUuids;
    protected $table = 'nilai_budaya_kerja';
	protected $primaryKey = 'nilai_budaya_kerja_id';
	protected $guarded = [];
    public function budaya_kerja()
	{
		return $this->belongsTo(BudayaKerja::class, 'budaya_kerja_id', 'budaya_kerja_id');
	}
    public function elemen_budaya_kerja()
	{
		return $this->belongsTo(ElemenBudayaKerja::class, 'elemen_id', 'elemen_id');
	}
    public function guru()
	{
		return $this->belongsTo(Ptk::class, 'guru_id', 'guru_id');
	}
    public function rencana_budaya_kerja()
    {
        return $this->hasOneThrough(
            RencanaBudayaKerja::class, 
            AspekBudayaKerja::class,
            'aspek_budaya_kerja_id',
            'rencana_budaya_kerja_id',
            'aspek_budaya_kerja_id',
            'rencana_budaya_kerja_id'
        );
        return $this->belongsTo(RencanaBudayaKerja::class, 'rencana_budaya_kerja_id', 'rencana_budaya_kerja_id');
    }
    public function aspek_budaya_kerja()
    {
        return $this->belongsTo(AspekBudayaKerja::class, 'aspek_budaya_kerja_id', 'aspek_budaya_kerja_id');
    }
    public function anggota_rombel()
    {
        return $this->belongsTo(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
    }
    public function opsi_budaya_kerja()
    {
        return $this->belongsTo(OpsiBudayaKerja::class, 'opsi_id', 'opsi_id');
    }
}
