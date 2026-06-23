<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(private SyncService $syncService)
    {
    }

    public function push(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'changes' => 'required|array',
            'changes.*.entity_type' => 'required|string',
            'changes.*.entity_id' => 'nullable|integer',
            'changes.*.action' => 'required|in:create,update,delete',
            'changes.*.payload' => 'nullable|array',
        ]);

        $tenantId = $request->user()->tenant_id;
        $results = $this->syncService->pushChanges($validated['changes'], $tenantId, $validated['device_id']);

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function pull(Request $request)
    {
        $validated = $request->validate([
            'last_sync_at' => 'required|date',
        ]);

        $tenantId = $request->user()->tenant_id;
        $changes = $this->syncService->pullChanges($tenantId, $validated['last_sync_at']);

        return response()->json(['success' => true, 'data' => $changes, 'server_time' => now()->toIso8601String()]);
    }

    public function status()
    {
        $pendingCount = \App\Models\SyncLog::where('sync_status', 'pending')->count();
        $failedCount = \App\Models\SyncLog::where('sync_status', 'failed')->count();
        $syncedCount = \App\Models\SyncLog::where('sync_status', 'synced')->count();

        return response()->json(['success' => true, 'data' => [
            'pending' => $pendingCount,
            'failed' => $failedCount,
            'synced' => $syncedCount,
            'last_sync' => \App\Models\SyncLog::where('sync_status', 'synced')->latest('synced_at')->first()?->synced_at,
        ]]);
    }
}
