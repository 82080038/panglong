<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IotSensorReading extends Model
{
    protected $fillable = ['sensor_id', 'value', 'unit', 'read_at'];

    public function sensor() { return $this->belongsTo(IotSensor::class, 'sensor_id'); }
}
