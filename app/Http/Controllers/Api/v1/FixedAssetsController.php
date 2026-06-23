<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\FixedAssetService;
use App\Models\FixedAsset;
use Illuminate\Http\Request;

class FixedAssetsController extends Controller
{
    private FixedAssetService $assetService;

    public function __construct(FixedAssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    public function index(Request $request)
    {
        $query = FixedAsset::with(['branch', 'assetAccount', 'accumDepAccount', 'depExpenseAccount']);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $assets = $query->orderBy('asset_code')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $assets->items(),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
                'last_page' => $assets->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $asset = FixedAsset::with(['branch', 'depreciations', 'assetAccount', 'accumDepAccount', 'depExpenseAccount'])
            ->findOrFail($id);
        return response()->json(['success' => true, 'data' => $asset]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_code' => 'required|string|unique:fixed_assets,asset_code',
            'name' => 'required|string',
            'category' => 'required|in:kendaraan,bangunan,peralatan,inventaris,tanah,lainnya',
            'branch_id' => 'nullable|exists:branches,id',
            'serial_no' => 'nullable|string',
            'plate_no' => 'nullable|string',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'depreciation_method' => 'nullable|in:straight_line,declining_balance',
            'notes' => 'nullable|string',
        ]);

        $asset = $this->assetService->createAsset($validated, auth()->id());

        return response()->json(['success' => true, 'data' => $asset, 'message' => 'Fixed asset created'], 201);
    }

    public function runDepreciation(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $dep = $this->assetService->runDepreciation($id, $validated['date'], auth()->id());
            return response()->json(['success' => true, 'data' => $dep, 'message' => 'Depreciation posted']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function runMonthlyDepreciationAll(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $results = $this->assetService->runMonthlyDepreciationAll($validated['date'], auth()->id());

        return response()->json(['success' => true, 'data' => $results]);
    }

    public function dispose(Request $request, $id)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'disposal_value' => 'nullable|numeric|min:0',
        ]);

        $asset = $this->assetService->disposeAsset($id, $validated['date'], $validated['disposal_value'] ?? 0, auth()->id());

        return response()->json(['success' => true, 'data' => $asset, 'message' => 'Asset disposed']);
    }
}
