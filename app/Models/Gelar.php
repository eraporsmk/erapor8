<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gelar extends Model
{
    protected $table = 'ref.gelar_akademik';
	protected $primaryKey = 'gelar_akademik_id';
    protected $guarded = [];
}
