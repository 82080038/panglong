<?php
namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Barcode;
use Tests\TestCase;

class BarcodeApiTest extends TestCase
{
    public function test_barcode_lookup_finds_product(): void
    {
        $product = Product::factory()->create();
        $barcode = Barcode::create([
            'product_id' => $product->id,
            'barcode' => '8990001234567',
            'is_primary' => true,
        ]);

        $response = $this->actingAsUser()->getJson('/api/v1/barcode/lookup?barcode=8990001234567');
        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_barcode_lookup_returns_404_for_not_found(): void
    {
        $response = $this->actingAsUser()->getJson('/api/v1/barcode/lookup?barcode=NOTEXIST');
        $response->assertStatus(404);
    }
}
