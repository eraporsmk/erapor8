<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanWali extends Model
{
    protected $table = 'catatan_wali';
	protected $primaryKey = 'catatan_wali_id';
	protected $guarded = [];
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
	}
}
