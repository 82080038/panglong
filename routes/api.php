<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/user', function (Request $request) {
    return response()->json([
        'message' => 'API is working',
        'version' => '1.0.0',
    ]);
});

// API v1 routes
Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [App\Http\Controllers\Api\v1\AuthController::class, 'login']);
    Route::get('/auth/me', [App\Http\Controllers\Api\v1\AuthController::class, 'me']);
    Route::post('/auth/logout', [App\Http\Controllers\Api\v1\AuthController::class, 'logout']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Sales
        Route::apiResource('sales', App\Http\Controllers\Api\v1\SalesController::class);
        Route::post('/sales/{id}/payment', [App\Http\Controllers\Api\v1\SalesController::class, 'payment']);
        
        // Products
        Route::apiResource('products', App\Http\Controllers\Api\v1\ProductsController::class);
        Route::get('/products/search', [App\Http\Controllers\Api\v1\ProductsController::class, 'search']);
        
        // Customers
        Route::apiResource('customers', App\Http\Controllers\Api\v1\CustomersController::class);
        
        // Inventory
        Route::get('/stock', [App\Http\Controllers\Api\v1\InventoryController::class, 'index']);
        Route::get('/stock/{product_id}', [App\Http\Controllers\Api\v1\InventoryController::class, 'show']);
        Route::post('/stock/adjustments', [App\Http\Controllers\Api\v1\InventoryController::class, 'adjustment']);
        Route::post('/stock/adjustments/{id}/approve', [App\Http\Controllers\Api\v1\InventoryController::class, 'approveAdjustment']);
        Route::post('/stock/opnames', [App\Http\Controllers\Api\v1\InventoryController::class, 'opname']);
        Route::post('/stock/opnames/{id}/approve', [App\Http\Controllers\Api\v1\InventoryController::class, 'approveOpname']);
        
        // Suppliers
        Route::apiResource('suppliers', App\Http\Controllers\Api\v1\SuppliersController::class);
        
        // Purchase Orders
        Route::apiResource('purchase-orders', App\Http\Controllers\Api\v1\PurchaseOrdersController::class);
        Route::post('/purchase-orders/{id}/receive', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'receive']);
        
        // Categories
        Route::apiResource('categories', App\Http\Controllers\Api\v1\CategoriesController::class);
        
        // Customer Groups
        Route::apiResource('customer-groups', App\Http\Controllers\Api\v1\CustomerGroupsController::class);
        
        // Reports
        Route::get('/reports/sales/daily', [App\Http\Controllers\Api\v1\ReportsController::class, 'dailySales']);
        Route::get('/reports/sales/monthly', [App\Http\Controllers\Api\v1\ReportsController::class, 'monthlySales']);
        Route::get('/reports/inventory/low-stock', [App\Http\Controllers\Api\v1\ReportsController::class, 'lowStock']);
        Route::get('/reports/accounts/receivable/aging', [App\Http\Controllers\Api\v1\ReportsController::class, 'arAging']);
    });
});
