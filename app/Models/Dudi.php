<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dudi extends Model
{
    use SoftDeletes;
    public $incrementing = false;
    public $keyType = 'string';
	protected $table = 'dudi';
	protected $primaryKey = 'dudi_id';
	protected $guarded = [];
}
