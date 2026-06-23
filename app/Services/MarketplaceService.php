<?php
namespace App\Services;

use App\Models\MarketplaceIntegration;
use App\Models\MarketplaceProductMapping;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplaceService
{
    public function connect(string $platform, string $shopId, string $shopName, ?string $accessToken = null): MarketplaceIntegration
    {
        return MarketplaceIntegration::create([
            'tenant_id' => session('tenant_id'),
            'platform' => $platform,
            'shop_id' => $shopId,
            'shop_name' => $shopName,
            'access_token' => $accessToken,
            'status' => $accessToken ? 'connected' : 'disconnected',
        ]);
    }

    public function syncProducts(MarketplaceIntegration $integration): array
    {
        // Placeholder for actual marketplace API sync
        // In production, use platform-specific API clients:
        // - Tokopedia: https://developer.tokopedia.com
        // - Shopee: https://open.shopee.com
        // - Bukalapak: https://developer.bukalapak.com

        Log::info("Syncing products for {$integration->platform} shop {$integration->shop_id}");

        return [
            'synced' => 0,
            'message' => "Marketplace API integration for {$integration->platform} requires API credentials. Configure in settings.",
        ];
    }

    public function syncStock(MarketplaceIntegration $integration): array
    {
        $mappings = MarketplaceProductMapping::where('integration_id', $integration->id)->get();
        $synced = 0;

        foreach ($mappings as $mapping) {
            $product = Product::find($mapping->product_id);
            if (!$product) continue;

            $stock = \App\Models\StockMovement::where('product_id', $product->id)->sum('quantity');

            // In production: call marketplace API to update stock
            // Example for Tokopedia:
            // Http::withToken($integration->access_token)
            //     ->put("https://api.tokopedia.com/v1/products/{$mapping->marketplace_item_id}/stock", [
            //         'stock' => $stock
            //     ]);

            $mapping->update([
                'marketplace_stock' => $stock,
                'last_synced_at' => now(),
            ]);
            $synced++;
        }

        $integration->update(['last_synced_at' => now()]);
        return ['synced' => $synced, 'message' => "Stock synced to {$integration->platform}"];
    }

    public function mapProduct(MarketplaceIntegration $integration, int $productId, string $marketplaceItemId): MarketplaceProductMapping
    {
        return MarketplaceProductMapping::create([
            'integration_id' => $integration->id,
            'product_id' => $productId,
            'marketplace_item_id' => $marketplaceItemId,
        ]);
    }
}
