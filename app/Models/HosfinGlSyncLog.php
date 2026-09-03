<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HosfinGlSyncLog extends Model
{
    use HasFactory;

    protected $table = 'hosfin_gl_sync_logs';

    protected $fillable = [
        'sync_type',
        'records_count',
        'status',
        'message',
        'agent_ip',
        'agent_version',
        'duration_seconds',
    ];
}
