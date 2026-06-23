<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotSensor extends Model
{
    protected $fillable = ['tenant_id', 'warehouse_id', 'sensor_id', 'name',
        'type', 'location', 'is_active'];

    public function readings() { return $this->hasMany(IotSensorReading::class, 'sensor_id'); }
}
