<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PdKeluar extends Model
{
    use HasUuids;
    protected $table = 'pd_keluar';
	protected $primaryKey = 'pd_keluar_id';
	protected $guarded = [];
}
