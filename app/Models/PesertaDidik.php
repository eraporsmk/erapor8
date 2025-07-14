<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PesertaDidik extends Model
{
    use SoftDeletes;
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'peserta_didik';
	protected $primaryKey = 'peserta_didik_id';
	protected $guarded = [];
    protected $appends = ['tempat_tanggal_lahir', 'tanggal_lahir_indo', 'jenis_kelamin_str'];
    protected function nama(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => strtoupper($attributes['nama']),
        );
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
	public function anggota_rombel()
	{
		return $this->hasOne(AnggotaRombel::class, 'peserta_didik_id', 'peserta_didik_id');
	}
	public function pd_keluar()
	{
		return $this->hasOne(PdKeluar::class, 'peserta_didik_id', 'peserta_didik_id');
	}
	public function nilai_akhir_kurmer(){
		return $this->hasOneThrough(
            NilaiAkhir::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        )->where('kompetensi_id', 4);
	}
	public function nilai_akhir_pengetahuan(){
		return $this->hasOneThrough(
            NilaiAkhir::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        )->where('kompetensi_id', 1);
	}
	public function nilai_akhir_keterampilan(){
		return $this->hasOneThrough(
            NilaiAkhir::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        )->where('kompetensi_id', 2);
	}
	public function deskripsi_mapel(){
		return $this->hasOneThrough(
            DeskripsiMataPelajaran::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        );
	}
	public function agama()
	{
		return $this->belongsTo(Agama::class, 'agama_id', 'agama_id');
	}
    public function nilai_akhir_induk(){
		return $this->hasOneThrough(
            NilaiAkhir::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        )->where('kompetensi_id', 4);
	}
    public function user()
    {
        return $this->belongsTo(User::class, 'peserta_didik_id', 'peserta_didik_id');
    }
    public function pekerjaan_ayah(){
		return $this->hasOne(Pekerjaan::class, 'pekerjaan_id', 'kerja_ayah');
	}
	public function pekerjaan_ibu(){
		return $this->hasOne(Pekerjaan::class, 'pekerjaan_id', 'kerja_ibu');
	}
	public function pekerjaan_wali(){
		return $this->hasOne(Pekerjaan::class, 'pekerjaan_id', 'kerja_wali');
	}
    protected function photo(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ($value) ? "/storage/$value" : '/images/avatars/blank-profile.png',
        );
    }
    public function anggota_akt_pd()
	{
		return $this->hasOne(AnggotaAktPd::class, 'peserta_didik_id', 'peserta_didik_id');
	}
    public function kelas(){
		return $this->hasOneThrough(
            RombonganBelajar::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'rombongan_belajar_id', 
            'peserta_didik_id',
            'rombongan_belajar_id'
        );
	}
    public function anggota_ekskul()
	{
		return $this->hasOne(AnggotaRombel::class, 'peserta_didik_id', 'peserta_didik_id')->whereHas('rombongan_belajar', function($query){
			$query->where('jenis_rombel', 51);
		});
	}
    public function nilai_ukk(){
		return $this->hasOneThrough(
            NilaiUkk::class,
            AnggotaRombel::class,
            'peserta_didik_id',
            'anggota_rombel_id',
            'peserta_didik_id',
            'anggota_rombel_id'
        );
	}
    public function wilayah(){
		return $this->hasOne(MstWilayah::class, 'kode_wilayah', 'kode_wilayah')->with(['parrentRecursive']);
    }
    public function sekolah(){
		return $this->hasOne(Sekolah::class, 'sekolah_id', 'sekolah_id');
	}
    public function anggota_pilihan()
	{
		return $this->hasOne(AnggotaRombel::class, 'peserta_didik_id', 'peserta_didik_id')->whereHas('rombongan_belajar', function($query){
			$query->where('jenis_rombel', 16);
		});
	}
}
