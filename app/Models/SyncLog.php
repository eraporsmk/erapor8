<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SyncLog extends Model
{
    use HasUuids;
    protected $table = 'sync_log';
	protected $guarded = [];
}
