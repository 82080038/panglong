<?php
namespace App\Services;

use App\Models\IotSensor;
use App\Models\IotSensorReading;

class IoTService
{
    public function registerSensor(array $data): IotSensor
    {
        return IotSensor::create([
            'tenant_id' => session('tenant_id'),
            'sensor_id' => $data['sensor_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'location' => $data['location'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'is_active' => true,
        ]);
    }

    public function recordReading(string $sensorId, float $value, ?string $unit = null): IotSensorReading
    {
        $sensor = IotSensor::where('sensor_id', $sensorId)->firstOrFail();

        return IotSensorReading::create([
            'sensor_id' => $sensor->id,
            'value' => $value,
            'unit' => $unit,
            'read_at' => now(),
        ]);
    }

    public function getSensorReadings(int $sensorId, int $hours = 24): array
    {
        return IotSensorReading::where('sensor_id', $sensorId)
            ->where('read_at', '>=', now()->subHours($hours))
            ->orderBy('read_at')
            ->get()
            ->toArray();
    }

    public function checkAlerts(): array
    {
        $alerts = [];
        $sensors = IotSensor::where('is_active', true)->get();

        foreach ($sensors as $sensor) {
            $latest = $sensor->readings()->latest('read_at')->first();
            if (!$latest) continue;

            switch ($sensor->type) {
                case 'temperature':
                    if ($latest->value > 35) {
                        $alerts[] = ['sensor' => $sensor->name, 'type' => 'temperature', 'value' => $latest->value, 'message' => 'High temperature alert!'];
                    } elseif ($latest->value < 5) {
                        $alerts[] = ['sensor' => $sensor->name, 'type' => 'temperature', 'value' => $latest->value, 'message' => 'Low temperature alert!'];
                    }
                    break;
                case 'humidity':
                    if ($latest->value > 80) {
                        $alerts[] = ['sensor' => $sensor->name, 'type' => 'humidity', 'value' => $latest->value, 'message' => 'High humidity alert!'];
                    }
                    break;
                case 'door':
                    if ($latest->value > 0) {
                        $alerts[] = ['sensor' => $sensor->name, 'type' => 'door', 'value' => $latest->value, 'message' => 'Door opened!'];
                    }
                    break;
            }
        }

        return $alerts;
    }
}
