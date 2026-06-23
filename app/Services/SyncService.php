<?php
namespace App\Services;

use App\Models\SyncLog;
use Illuminate\Support\Facades\DB;

class SyncService
{
    public function pushChanges(array $changes, ?int $tenantId, string $deviceId): array
    {
        $results = ['synced' => 0, 'failed' => 0, 'conflicts' => 0];

        foreach ($changes as $change) {
            try {
                $modelClass = 'App\\Models\\' . ucfirst($change['entity_type']);
                if (!class_exists($modelClass)) {
                    $this->logSync($tenantId, $deviceId, $change, 'failed', 'Model not found');
                    $results['failed']++;
                    continue;
                }

                switch ($change['action']) {
                    case 'create':
                        $model = $modelClass::create($change['payload']);
                        $this->logSync($tenantId, $deviceId, $change, 'synced', null, $model->id);
                        $results['synced']++;
                        break;
                    case 'update':
                        $model = $modelClass::find($change['entity_id']);
                        if ($model) {
                            $model->update($change['payload']);
                            $this->logSync($tenantId, $deviceId, $change, 'synced');
                            $results['synced']++;
                        } else {
                            $this->logSync($tenantId, $deviceId, $change, 'failed', 'Entity not found');
                            $results['failed']++;
                        }
                        break;
                    case 'delete':
                        $model = $modelClass::find($change['entity_id']);
                        if ($model) {
                            $model->delete();
                            $this->logSync($tenantId, $deviceId, $change, 'synced');
                            $results['synced']++;
                        } else {
                            $this->logSync($tenantId, $deviceId, $change, 'synced');
                            $results['synced']++;
                        }
                        break;
                }
            } catch (\Exception $e) {
                $this->logSync($tenantId, $deviceId, $change, 'failed', $e->getMessage());
                $results['failed']++;
            }
        }

        return $results;
    }

    public function pullChanges(?int $tenantId, string $lastSyncAt): array
    {
        $entities = ['Product', 'Customer', 'Sale', 'Supplier'];
        $changes = [];

        foreach ($entities as $entity) {
            $modelClass = 'App\\Models\\' . $entity;
            $query = $modelClass::where('updated_at', '>', $lastSyncAt);
            if ($tenantId) $query->where('tenant_id', $tenantId);

            $records = $query->get();
            foreach ($records as $record) {
                $changes[] = [
                    'entity_type' => $entity,
                    'entity_id' => $record->id,
                    'action' => 'update',
                    'payload' => $record->toArray(),
                    'synced_at' => now()->toIso8601String(),
                ];
            }
        }

        // StockMovement uses created_at only (no updated_at)
        $query = \App\Models\StockMovement::where('created_at', '>', $lastSyncAt);
        if ($tenantId) $query->where('tenant_id', $tenantId);
        $records = $query->get();
        foreach ($records as $record) {
            $changes[] = [
                'entity_type' => 'StockMovement',
                'entity_id' => $record->id,
                'action' => 'update',
                'payload' => $record->toArray(),
                'synced_at' => now()->toIso8601String(),
            ];
        }

        return $changes;
    }

    private function logSync(?int $tenantId, string $deviceId, array $change, string $status, ?string $error = null, ?int $entityId = null): void
    {
        SyncLog::create([
            'tenant_id' => $tenantId,
            'device_id' => $deviceId,
            'entity_type' => $change['entity_type'],
            'entity_id' => $entityId ?? $change['entity_id'] ?? null,
            'action' => $change['action'],
            'payload' => $change['payload'] ?? null,
            'sync_status' => $status,
            'error_message' => $error,
            'synced_at' => $status === 'synced' ? now() : null,
        ]);
    }
}
