<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsiBudayaKerja extends Model
{
    public $incrementing = false;
	protected $table = 'ref.opsi_budaya_kerja';
	protected $primaryKey = 'opsi_id';
	protected $guarded = [];
}
