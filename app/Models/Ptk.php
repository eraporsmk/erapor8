<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class Ptk extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'guru';
	protected $primaryKey = 'guru_id';
	protected $guarded = [];
    protected $appends = ['nama_lengkap', 'tempat_tanggal_lahir', 'tanggal_lahir_indo', 'jenis_kelamin_str'];
    /*protected function namaLengkap(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['gelar_depan'].' '.strtoupper($attributes['nama']).$attributes['gelar_belakang'],
        );
    }*/
    public function getNamaLengkapAttribute(){
		$gelar_depan = ($this->attributes['gelar_depan']) ? $this->attributes['gelar_depan'].' ' : NULL;
		$gelar_belakang = ($this->attributes['gelar_belakang']) ? ', '.$this->attributes['gelar_belakang'] : NULL;
		return $gelar_depan . strtoupper($this->attributes['nama']). $gelar_belakang;
	}
    protected function tempatTanggalLahir(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['tempat_lahir']) ? strtoupper($attributes['tempat_lahir']).', '.Carbon::parse($this->attributes['tanggal_lahir'])->translatedFormat('d F Y') : NULL,
        );
    }
    protected function tanggalLahirIndo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['tanggal_lahir']) ? Carbon::parse($this->attributes['tanggal_lahir'])->translatedFormat('d F Y') : NULL,
        );
    }
    protected function jenisKelaminStr(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['jenis_kelamin']) ? ($attributes['jenis_kelamin'] == 'L') ? 'Laki-laki' : 'Perempuan' : NULL,
        );
    }
    public function ptk_keluar()
    {
        return $this->hasOne(PtkKeluar::class, 'guru_id', 'guru_id');
    }
    public function bimbing_pd()
	{
		return $this->hasOne(BimbingPd::class, 'guru_id', 'guru_id');
	}
    public function sekolah()
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id', 'sekolah_id');
    }
    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value) ? "/storage/$value" : '/images/avatars/blank-profile.png',
        );
    }
    public function agama()
    {
        return $this->belongsTo(Agama::class, 'agama_id', 'agama_id');
    }
    public function dudi(){
		return $this->hasOneThrough(
            Dudi::class,
            Asesor::class,
            'guru_id',
            'dudi_id',
            'guru_id',
            'dudi_id'
        );
	}
    public function pengguna(){
		return $this->hasOne(User::class, 'guru_id', 'guru_id');
	}
}
