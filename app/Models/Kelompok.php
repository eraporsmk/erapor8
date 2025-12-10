<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Kelompok extends Model
{
    protected $table = 'ref.kelompok';
	protected $primaryKey = 'kelompok_id';
	protected $guarded = [];
	protected function namaKelompok(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes){
				$nama_kelompok = $attributes['nama_kelompok'];
				//$nama_kelompok = Str::of($nama_kelompok)->replace('A. Kelompok', '');
				//$nama_kelompok = Str::of($nama_kelompok)->replace('B. Kelompok', '');
				$nama_kelompok = Str::replaceFirst('A. Kelompok', '', $nama_kelompok);
				$nama_kelompok = Str::replaceFirst('B. Kelompok', '', $nama_kelompok);
				$nama_kelompok = Str::trim($nama_kelompok);
				return $nama_kelompok;
			}
        );
    }
}
