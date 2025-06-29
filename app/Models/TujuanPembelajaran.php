<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TujuanPembelajaran extends Model
{
    use HasUuids;
    protected $table = 'tujuan_pembelajaran';
	protected $primaryKey = 'tp_id';
	protected $guarded = [];
	
    public function cp()
    {
        return $this->belongsTo(CapaianPembelajaran::class, 'cp_id', 'cp_id');
    }
    public function kd()
    {
        return $this->belongsTo(KompetensiDasar::class, 'kd_id', 'kompetensi_dasar_id');
    }
}
