<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    public $incrementing = false;
	protected $table = 'ref.kurikulum';
	protected $primaryKey = 'kurikulum_id';
	protected $guarded = [];
}
