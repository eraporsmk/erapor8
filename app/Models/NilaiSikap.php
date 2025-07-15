<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class NilaiSikap extends Model
{
    use HasUuids;
    protected $table = 'nilai_sikap';
	protected $primaryKey = 'nilai_sikap_id';
	protected $guarded = [];
	public function ref_sikap(){
		return $this->hasOne(Sikap::class, 'sikap_id', 'sikap_id');
	}
	public function anggota_rombel(){
		return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
    }
	public function guru(){
		return $this->hasOne(Ptk::class, 'guru_id', 'guru_id');
	}
	public function getOpsiSikapAttribute($value)
    {
		$opsi_sikap = [1 => 'Posisif', 2 => 'Negatif'];
        return $opsi_sikap[$value];
    }
}
