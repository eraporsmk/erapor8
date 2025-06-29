<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    public $incrementing = false;
	public $keyType = 'string';
	protected $table = 'ref.jurusan';
	protected $primaryKey = 'jurusan_id';
	protected $guarded = [];
}
