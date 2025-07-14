<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Prestasi extends Model
{
    use HasUuids;
    protected $table = 'prestasi';
	protected $primaryKey = 'prestasi_id';
	protected $guarded = [];
	
    public function anggota_rombel()
    {
        return $this->hasOne(AnggotaRombel::class, 'anggota_rombel_id', 'anggota_rombel_id');
    }
    public function peserta_didik(){
        return $this->hasOneThrough(
            PesertaDidik::class,
            AnggotaRombel::class,
            'anggota_rombel_id', // Foreign key on the cars table...
            'peserta_didik_id', // Foreign key on the owners table...
            'anggota_rombel_id', // Local key on the mechanics table...
            'peserta_didik_id' // Local key on the cars table...
        );
    }
}
