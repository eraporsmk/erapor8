<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudayaKerja extends Model
{
    public $incrementing = false;
	protected $table = 'ref.budaya_kerja';
	protected $primaryKey = 'budaya_kerja_id';
	protected $guarded = [];
    public function elemen_budaya_kerja(){
        return $this->hasMany(ElemenBudayaKerja::class, 'budaya_kerja_id', 'budaya_kerja_id');
    }
    public function catatan_budaya_kerja(){
        return $this->hasOne(CatatanBudayaKerja::class, 'budaya_kerja_id', 'budaya_kerja_id');
    }
}
