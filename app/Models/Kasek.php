<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Kasek extends Model
{
    use HasUuids;
    protected $table = 'kasek';
	protected $primaryKey = 'kasek_id';
	protected $guarded = [];
}
