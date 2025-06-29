<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnggotaAktPd extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'anggota_akt_pd';
	protected $primaryKey = 'anggota_akt_pd_id';
	protected $guarded = [];
	
    public function siswa()
    {
        return $this->belongsTo(PesertaDidik::class, 'peserta_didik_id', 'peserta_didik_id');
    }
    public function anggota_rombel()
    {
        return $this->belongsTo(AnggotaRombel::class, 'peserta_didik_id', 'peserta_didik_id');
    }
    
    public function akt_pd()
    {
        return $this->belongsTo(AktPd::class, 'akt_pd_id', 'akt_pd_id');
    }
}
