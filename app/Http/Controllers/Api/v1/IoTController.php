<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\IoTService;
use App\Models\IotSensor;
use Illuminate\Http\Request;

class IoTController extends Controller
{
    public function __construct(private IoTService $iotService)
    {
    }

    public function sensors()
    {
        return response()->json(['success' => true, 'data' => IotSensor::with('readings')->orderBy('created_at', 'desc')->get()]);
    }

    public function registerSensor(Request $request)
    {
        $validated = $request->validate([
            'sensor_id' => 'required|string|unique:iot_sensors,sensor_id',
            'name' => 'required|string',
            'type' => 'required|in:temperature,humidity,weight,proximity,door',
            'location' => 'nullable|string',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $sensor = $this->iotService->registerSensor($validated);
        return response()->json(['success' => true, 'data' => $sensor], 201);
    }

    public function recordReading(Request $request)
    {
        $validated = $request->validate([
            'sensor_id' => 'required|string|exists:iot_sensors,sensor_id',
            'value' => 'required|numeric',
            'unit' => 'nullable|string',
        ]);

        $reading = $this->iotService->recordReading($validated['sensor_id'], $validated['value'], $validated['unit'] ?? null);
        return response()->json(['success' => true, 'data' => $reading], 201);
    }

    public function sensorReadings(Request $request, $sensorId)
    {
        $hours = (int)$request->input('hours', 24);
        $readings = $this->iotService->getSensorReadings($sensorId, $hours);
        return response()->json(['success' => true, 'data' => $readings]);
    }

    public function alerts()
    {
        return response()->json(['success' => true, 'data' => $this->iotService->checkAlerts()]);
    }
}
