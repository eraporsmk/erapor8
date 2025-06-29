<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StatusPenilaian extends Model
{
    use HasUuids;
    protected $table = 'status_penilaian';
	protected $primaryKey = 'status_penilaian_id';
	protected $guarded = [];
}
