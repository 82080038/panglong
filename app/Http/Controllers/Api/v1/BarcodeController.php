<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    public function lookup(Request $request)
    {
        $validated = $request->validate(['barcode' => 'required|string']);
        $barcode = $validated['barcode'];

        // Search in product barcodes table
        $productBarcode = \App\Models\ProductBarcode::where('barcode', $barcode)->first();
        if ($productBarcode) {
            $product = Product::with(['category', 'units.unit', 'barcodes'])->find($productBarcode->product_id);
            if ($product) {
                return response()->json(['success' => true, 'data' => $product]);
            }
        }

        // Fallback: search by product code
        $product = Product::where('code', $barcode)->with(['category', 'units.unit', 'barcodes'])->first();
        if ($product) {
            return response()->json(['success' => true, 'data' => $product]);
        }

        return response()->json(['success' => false, 'message' => 'Product not found'], 404);
    }
}
