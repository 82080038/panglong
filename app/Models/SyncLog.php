<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = ['tenant_id', 'device_id', 'entity_type', 'entity_id',
        'action', 'payload', 'sync_status', 'error_message', 'synced_at'];

    protected $casts = ['payload' => 'array'];
}
