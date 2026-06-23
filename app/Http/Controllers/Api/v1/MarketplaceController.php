<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceService;
use App\Models\MarketplaceIntegration;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function __construct(private MarketplaceService $marketplaceService)
    {
    }

    public function index()
    {
        $integrations = MarketplaceIntegration::with('mappings.product')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $integrations]);
    }

    public function connect(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|in:tokopedia,shopee,bukalapak,lazada,blibli',
            'shop_id' => 'required|string',
            'shop_name' => 'required|string',
            'access_token' => 'nullable|string',
        ]);

        $integration = $this->marketplaceService->connect(
            $validated['platform'],
            $validated['shop_id'],
            $validated['shop_name'],
            $validated['access_token'] ?? null
        );

        return response()->json(['success' => true, 'data' => $integration], 201);
    }

    public function syncStock($integrationId)
    {
        $integration = MarketplaceIntegration::findOrFail($integrationId);
        $result = $this->marketplaceService->syncStock($integration);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function syncProducts($integrationId)
    {
        $integration = MarketplaceIntegration::findOrFail($integrationId);
        $result = $this->marketplaceService->syncProducts($integration);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function mapProduct(Request $request, $integrationId)
    {
        $integration = MarketplaceIntegration::findOrFail($integrationId);
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'marketplace_item_id' => 'required|string',
        ]);

        $mapping = $this->marketplaceService->mapProduct($integration, $validated['product_id'], $validated['marketplace_item_id']);
        return response()->json(['success' => true, 'data' => $mapping], 201);
    }

    public function disconnect($integrationId)
    {
        $integration = MarketplaceIntegration::findOrFail($integrationId);
        $integration->update(['status' => 'disconnected']);
        return response()->json(['success' => true, 'message' => 'Disconnected']);
    }
}
