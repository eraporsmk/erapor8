<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Prakerin extends Model
{
    use HasUuids;
    protected $table = 'prakerin';
	protected $primaryKey = 'prakerin_id';
	protected $guarded = [];
	
    public function anggota_rombel(){
        return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
    }
}
