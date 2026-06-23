<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return response()->json(['message' => 'API is working', 'version' => '1.0.0']);
});

Route::prefix('v1')->group(function () {
    // Authentication
    Route::post('/auth/login', [App\Http\Controllers\Api\v1\AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        // Auth
        Route::get('/auth/me', [App\Http\Controllers\Api\v1\AuthController::class, 'me']);
        Route::post('/auth/logout', [App\Http\Controllers\Api\v1\AuthController::class, 'logout']);

        // Roles
        Route::get('/roles', function () {
            return response()->json(['success' => true, 'data' => \App\Models\Role::with('permissions')->get()]);
        });

        // App Settings
        Route::get('/settings', [App\Http\Controllers\Api\v1\AppSettingsController::class, 'index']);
        Route::middleware('permission:manage_users')->put('/settings', [App\Http\Controllers\Api\v1\AppSettingsController::class, 'update']);

        // Sales
        Route::get('/sales', [App\Http\Controllers\Api\v1\SalesController::class, 'index']);
        Route::get('/sales/{id}', [App\Http\Controllers\Api\v1\SalesController::class, 'show']);
        Route::middleware('permission:create_sales')->post('/sales', [App\Http\Controllers\Api\v1\SalesController::class, 'store']);
        Route::middleware('permission:create_sales')->put('/sales/{id}', [App\Http\Controllers\Api\v1\SalesController::class, 'update']);
        Route::middleware('permission:void_sales')->delete('/sales/{id}', [App\Http\Controllers\Api\v1\SalesController::class, 'destroy']);
        Route::middleware('permission:record_payment')->post('/sales/{id}/payment', [App\Http\Controllers\Api\v1\SalesController::class, 'payment']);
        Route::get('/sales/price', [App\Http\Controllers\Api\v1\SalesController::class, 'getPrice']);

        // Deliveries
        Route::get('/deliveries', [App\Http\Controllers\Api\v1\DeliveriesController::class, 'index']);
        Route::get('/deliveries/{id}', [App\Http\Controllers\Api\v1\DeliveriesController::class, 'show']);
        Route::middleware('permission:create_sales')->post('/deliveries', [App\Http\Controllers\Api\v1\DeliveriesController::class, 'store']);
        Route::middleware('permission:create_sales')->put('/deliveries/{id}/status', [App\Http\Controllers\Api\v1\DeliveriesController::class, 'updateStatus']);
        Route::middleware('permission:create_sales')->delete('/deliveries/{id}', [App\Http\Controllers\Api\v1\DeliveriesController::class, 'destroy']);

        // Sales Returns
        Route::get('/sales-returns', [App\Http\Controllers\Api\v1\ReturnsController::class, 'salesIndex']);
        Route::get('/sales-returns/{id}', [App\Http\Controllers\Api\v1\ReturnsController::class, 'salesShow']);
        Route::middleware('permission:create_sales')->post('/sales-returns', [App\Http\Controllers\Api\v1\ReturnsController::class, 'salesStore']);

        // Purchase Returns
        Route::get('/purchase-returns', [App\Http\Controllers\Api\v1\ReturnsController::class, 'purchaseIndex']);
        Route::get('/purchase-returns/{id}', [App\Http\Controllers\Api\v1\ReturnsController::class, 'purchaseShow']);
        Route::middleware('permission:manage_suppliers')->post('/purchase-returns', [App\Http\Controllers\Api\v1\ReturnsController::class, 'purchaseStore']);

        // Quotations
        Route::get('/quotations', [App\Http\Controllers\Api\v1\QuotationsController::class, 'index']);
        Route::get('/quotations/{id}', [App\Http\Controllers\Api\v1\QuotationsController::class, 'show']);
        Route::middleware('permission:create_sales')->post('/quotations', [App\Http\Controllers\Api\v1\QuotationsController::class, 'store']);
        Route::middleware('permission:create_sales')->put('/quotations/{id}/status', [App\Http\Controllers\Api\v1\QuotationsController::class, 'updateStatus']);
        Route::middleware('permission:create_sales')->post('/quotations/{id}/convert-to-sales-order', [App\Http\Controllers\Api\v1\QuotationsController::class, 'convertToSalesOrder']);

        // Sales Orders
        Route::get('/sales-orders', [App\Http\Controllers\Api\v1\SalesOrdersController::class, 'index']);
        Route::get('/sales-orders/{id}', [App\Http\Controllers\Api\v1\SalesOrdersController::class, 'show']);
        Route::middleware('permission:create_sales')->put('/sales-orders/{id}/status', [App\Http\Controllers\Api\v1\SalesOrdersController::class, 'updateStatus']);
        Route::middleware('permission:create_sales')->post('/sales-orders/{id}/convert-to-invoice', [App\Http\Controllers\Api\v1\SalesOrdersController::class, 'convertToInvoice']);

        // Pricing Management
        Route::middleware('permission:manage_customers')->group(function () {
            Route::get('/pricing/customer-prices', [App\Http\Controllers\Api\v1\PricingController::class, 'customerPrices']);
            Route::post('/pricing/customer-prices', [App\Http\Controllers\Api\v1\PricingController::class, 'storeCustomerPrice']);
            Route::delete('/pricing/customer-prices/{id}', [App\Http\Controllers\Api\v1\PricingController::class, 'destroyCustomerPrice']);
        });
        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/pricing/tier-prices', [App\Http\Controllers\Api\v1\PricingController::class, 'tierPrices']);
            Route::post('/pricing/tier-prices', [App\Http\Controllers\Api\v1\PricingController::class, 'storeTierPrice']);
            Route::delete('/pricing/tier-prices/{id}', [App\Http\Controllers\Api\v1\PricingController::class, 'destroyTierPrice']);
        });
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('/pricing/supplier-price-history', [App\Http\Controllers\Api\v1\PricingController::class, 'supplierPriceHistory']);
        });

        // Branches (Cabang Usaha)
        Route::get('/branches', [App\Http\Controllers\Api\v1\BranchesController::class, 'index']);
        Route::get('/branches/{id}', [App\Http\Controllers\Api\v1\BranchesController::class, 'show']);
        Route::middleware('permission:manage_users')->post('/branches', [App\Http\Controllers\Api\v1\BranchesController::class, 'store']);
        Route::middleware('permission:manage_users')->put('/branches/{id}', [App\Http\Controllers\Api\v1\BranchesController::class, 'update']);
        Route::middleware('permission:manage_users')->delete('/branches/{id}', [App\Http\Controllers\Api\v1\BranchesController::class, 'destroy']);

        // Employees (Manajemen Orang)
        Route::get('/employees', [App\Http\Controllers\Api\v1\EmployeesController::class, 'index']);
        Route::get('/employees/{id}', [App\Http\Controllers\Api\v1\EmployeesController::class, 'show']);
        Route::middleware('permission:manage_users')->post('/employees', [App\Http\Controllers\Api\v1\EmployeesController::class, 'store']);
        Route::middleware('permission:manage_users')->put('/employees/{id}', [App\Http\Controllers\Api\v1\EmployeesController::class, 'update']);
        Route::middleware('permission:manage_users')->delete('/employees/{id}', [App\Http\Controllers\Api\v1\EmployeesController::class, 'destroy']);

        // Warehouse Locations (Rak/Blok/Lokasi)
        Route::get('/warehouse-locations', [App\Http\Controllers\Api\v1\WarehouseLocationsController::class, 'index']);
        Route::middleware('permission:manage_products')->post('/warehouse-locations', [App\Http\Controllers\Api\v1\WarehouseLocationsController::class, 'store']);
        Route::middleware('permission:manage_products')->put('/warehouse-locations/{id}', [App\Http\Controllers\Api\v1\WarehouseLocationsController::class, 'update']);
        Route::middleware('permission:manage_products')->delete('/warehouse-locations/{id}', [App\Http\Controllers\Api\v1\WarehouseLocationsController::class, 'destroy']);

        // Fixed Assets (Inventaris & Penyusutan)
        Route::get('/fixed-assets', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'index']);
        Route::get('/fixed-assets/{id}', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'show']);
        Route::middleware('permission:view_reports')->post('/fixed-assets', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'store']);
        Route::middleware('permission:view_reports')->post('/fixed-assets/{id}/depreciate', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'runDepreciation']);
        Route::middleware('permission:view_reports')->post('/fixed-assets/depreciate-all', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'runMonthlyDepreciationAll']);
        Route::middleware('permission:view_reports')->post('/fixed-assets/{id}/dispose', [App\Http\Controllers\Api\v1\FixedAssetsController::class, 'dispose']);

        // Cash Management (Uang/Surat)
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('/cash/balances', [App\Http\Controllers\Api\v1\CashManagementController::class, 'balances']);
            Route::get('/cash/flow-summary', [App\Http\Controllers\Api\v1\CashManagementController::class, 'cashFlowSummary']);
            Route::get('/cash/transactions', [App\Http\Controllers\Api\v1\CashManagementController::class, 'transactions']);
            Route::post('/cash/transactions', [App\Http\Controllers\Api\v1\CashManagementController::class, 'storeTransaction']);
            Route::get('/bank/statements', [App\Http\Controllers\Api\v1\CashManagementController::class, 'bankStatements']);
            Route::post('/bank/statements/import', [App\Http\Controllers\Api\v1\CashManagementController::class, 'importBankStatements']);
            Route::post('/bank/statements/{id}/reconcile', [App\Http\Controllers\Api\v1\CashManagementController::class, 'reconcileBankStatement']);
        });

        // Products
        Route::get('/products', [App\Http\Controllers\Api\v1\ProductsController::class, 'index']);
        Route::get('/products/{id}', [App\Http\Controllers\Api\v1\ProductsController::class, 'show']);
        Route::get('/products/search', [App\Http\Controllers\Api\v1\ProductsController::class, 'search']);
        Route::middleware('permission:manage_products')->post('/products', [App\Http\Controllers\Api\v1\ProductsController::class, 'store']);
        Route::middleware('permission:manage_products')->put('/products/{id}', [App\Http\Controllers\Api\v1\ProductsController::class, 'update']);
        Route::middleware('permission:manage_products')->delete('/products/{id}', [App\Http\Controllers\Api\v1\ProductsController::class, 'destroy']);

        // Customers
        Route::get('/customers', [App\Http\Controllers\Api\v1\CustomersController::class, 'index']);
        Route::get('/customers/{id}', [App\Http\Controllers\Api\v1\CustomersController::class, 'show']);
        Route::middleware('permission:manage_customers')->post('/customers', [App\Http\Controllers\Api\v1\CustomersController::class, 'store']);
        Route::middleware('permission:manage_customers')->put('/customers/{id}', [App\Http\Controllers\Api\v1\CustomersController::class, 'update']);
        Route::middleware('permission:manage_customers')->delete('/customers/{id}', [App\Http\Controllers\Api\v1\CustomersController::class, 'destroy']);

        // Inventory
        Route::get('/stock', [App\Http\Controllers\Api\v1\InventoryController::class, 'index']);
        Route::get('/stock/{product_id}', [App\Http\Controllers\Api\v1\InventoryController::class, 'show']);
        Route::middleware('permission:stock_adjustment')->post('/stock/adjustments', [App\Http\Controllers\Api\v1\InventoryController::class, 'adjustment']);
        Route::middleware('permission:approve_adjustment')->post('/stock/adjustments/{id}/approve', [App\Http\Controllers\Api\v1\InventoryController::class, 'approveAdjustment']);
        Route::middleware('permission:stock_adjustment')->post('/stock/opnames', [App\Http\Controllers\Api\v1\InventoryController::class, 'opname']);
        Route::middleware('permission:approve_adjustment')->post('/stock/opnames/{id}/approve', [App\Http\Controllers\Api\v1\InventoryController::class, 'approveOpname']);

        // Suppliers
        Route::get('/suppliers', [App\Http\Controllers\Api\v1\SuppliersController::class, 'index']);
        Route::get('/suppliers/{id}', [App\Http\Controllers\Api\v1\SuppliersController::class, 'show']);
        Route::middleware('permission:manage_suppliers')->post('/suppliers', [App\Http\Controllers\Api\v1\SuppliersController::class, 'store']);
        Route::middleware('permission:manage_suppliers')->put('/suppliers/{id}', [App\Http\Controllers\Api\v1\SuppliersController::class, 'update']);
        Route::middleware('permission:manage_suppliers')->delete('/suppliers/{id}', [App\Http\Controllers\Api\v1\SuppliersController::class, 'destroy']);

        // Purchase Orders
        Route::get('/purchase-orders', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'index']);
        Route::get('/purchase-orders/{id}', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'show']);
        Route::middleware('permission:manage_suppliers')->post('/purchase-orders', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'store']);
        Route::middleware('permission:manage_suppliers')->post('/purchase-orders/{id}/receive', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'receive']);
        Route::middleware('permission:record_payment')->post('/purchase-orders/{id}/payment', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'payment']);
        Route::middleware('permission:manage_suppliers')->delete('/purchase-orders/{id}', [App\Http\Controllers\Api\v1\PurchaseOrdersController::class, 'destroy']);

        // Categories
        Route::get('/categories', [App\Http\Controllers\Api\v1\CategoriesController::class, 'index']);
        Route::get('/categories/{id}', [App\Http\Controllers\Api\v1\CategoriesController::class, 'show']);
        Route::middleware('permission:manage_products')->post('/categories', [App\Http\Controllers\Api\v1\CategoriesController::class, 'store']);
        Route::middleware('permission:manage_products')->put('/categories/{id}', [App\Http\Controllers\Api\v1\CategoriesController::class, 'update']);
        Route::middleware('permission:manage_products')->delete('/categories/{id}', [App\Http\Controllers\Api\v1\CategoriesController::class, 'destroy']);

        // Customer Groups
        Route::get('/customer-groups', [App\Http\Controllers\Api\v1\CustomerGroupsController::class, 'index']);
        Route::get('/customer-groups/{id}', [App\Http\Controllers\Api\v1\CustomerGroupsController::class, 'show']);
        Route::middleware('permission:manage_customers')->post('/customer-groups', [App\Http\Controllers\Api\v1\CustomerGroupsController::class, 'store']);
        Route::middleware('permission:manage_customers')->put('/customer-groups/{id}', [App\Http\Controllers\Api\v1\CustomerGroupsController::class, 'update']);
        Route::middleware('permission:manage_customers')->delete('/customer-groups/{id}', [App\Http\Controllers\Api\v1\CustomerGroupsController::class, 'destroy']);

        // Reports
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('/reports/sales/daily', [App\Http\Controllers\Api\v1\ReportsController::class, 'dailySales']);
            Route::get('/reports/sales/monthly', [App\Http\Controllers\Api\v1\ReportsController::class, 'monthlySales']);
            Route::get('/reports/sales/by-product', [App\Http\Controllers\Api\v1\ReportsController::class, 'salesByProduct']);
            Route::get('/reports/sales/by-customer', [App\Http\Controllers\Api\v1\ReportsController::class, 'salesByCustomer']);
            Route::get('/reports/inventory/low-stock', [App\Http\Controllers\Api\v1\ReportsController::class, 'lowStock']);
            Route::get('/reports/inventory/stock-movement', [App\Http\Controllers\Api\v1\ReportsController::class, 'stockMovement']);
            Route::get('/reports/inventory/dead-stock', [App\Http\Controllers\Api\v1\ReportsController::class, 'deadStock']);
            Route::get('/reports/accounts/receivable/aging', [App\Http\Controllers\Api\v1\ReportsController::class, 'arAging']);
            Route::get('/reports/accounts/payable/aging', [App\Http\Controllers\Api\v1\ReportsController::class, 'apAging']);
            Route::get('/reports/profit-loss', [App\Http\Controllers\Api\v1\ReportsController::class, 'profitLoss']);
            Route::get('/reports/inventory/stock-valuation', [App\Http\Controllers\Api\v1\ReportsController::class, 'stockValuation']);
            Route::get('/reports/custom', [App\Http\Controllers\Api\v1\ReportsController::class, 'customReport']);
        });

        // Accounting (Phase 2)
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('/accounting/chart-of-accounts', [App\Http\Controllers\Api\v1\AccountingController::class, 'chartOfAccounts']);
            Route::get('/accounting/journal-entries', [App\Http\Controllers\Api\v1\AccountingController::class, 'journalEntries']);
            Route::get('/accounting/trial-balance', [App\Http\Controllers\Api\v1\AccountingController::class, 'trialBalance']);
            Route::get('/accounting/balance-sheet', [App\Http\Controllers\Api\v1\AccountingController::class, 'balanceSheet']);
            Route::get('/accounting/income-statement', [App\Http\Controllers\Api\v1\AccountingController::class, 'incomeStatement']);
            Route::get('/accounting/general-ledger', [App\Http\Controllers\Api\v1\AccountingController::class, 'generalLedger']);
            Route::post('/accounting/journal-entry', [App\Http\Controllers\Api\v1\AccountingController::class, 'postManualJournal']);
        });

        // Warehouses (Phase 2)
        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/warehouses', [App\Http\Controllers\Api\v1\WarehousesController::class, 'index']);
            Route::post('/warehouses', [App\Http\Controllers\Api\v1\WarehousesController::class, 'store']);
            Route::get('/warehouses/{id}/stock', [App\Http\Controllers\Api\v1\WarehousesController::class, 'stockByWarehouse']);
            Route::post('/warehouses/transfer', [App\Http\Controllers\Api\v1\WarehousesController::class, 'createTransfer']);
            Route::get('/warehouses/transfers', [App\Http\Controllers\Api\v1\WarehousesController::class, 'transfers']);
        });

        // Reorder suggestions (Phase 2 - AI Basic)
        Route::middleware('permission:view_reports')->group(function () {
            Route::get('/reorder/suggestions', [App\Http\Controllers\Api\v1\ReorderController::class, 'suggestions']);
        });

        // Barcode lookup (Phase 2)
        Route::get('/barcode/lookup', [App\Http\Controllers\Api\v1\BarcodeController::class, 'lookup']);

        // Notifications (Phase 2)
        Route::middleware('permission:view_reports')->group(function () {
            Route::post('/notifications/invoice/{saleId}', [App\Http\Controllers\Api\v1\NotificationsController::class, 'sendInvoice']);
            Route::post('/notifications/payment-receipt/{saleId}', [App\Http\Controllers\Api\v1\NotificationsController::class, 'sendPaymentReceipt']);
            Route::post('/notifications/ar-due-reminders', [App\Http\Controllers\Api\v1\NotificationsController::class, 'sendARDueReminders']);
            Route::post('/notifications/ap-due-reminders', [App\Http\Controllers\Api\v1\NotificationsController::class, 'sendAPDueReminders']);
        });

        // Bank integration (Phase 2)
        Route::middleware('permission:view_reports')->group(function () {
            Route::post('/bank/verify-payment', [App\Http\Controllers\Api\v1\BankController::class, 'verifyPayment']);
            Route::get('/bank/statements', [App\Http\Controllers\Api\v1\BankController::class, 'statements']);
        });

        // Tenants & SaaS Billing (Phase 3)
        Route::middleware('permission:manage_users')->group(function () {
            Route::get('/tenants', [App\Http\Controllers\Api\v1\TenantsController::class, 'index']);
            Route::get('/tenants/{id}', [App\Http\Controllers\Api\v1\TenantsController::class, 'show']);
            Route::post('/tenants', [App\Http\Controllers\Api\v1\TenantsController::class, 'store']);
            Route::put('/tenants/{id}', [App\Http\Controllers\Api\v1\TenantsController::class, 'update']);
            Route::post('/tenants/{tenantId}/subscribe', [App\Http\Controllers\Api\v1\TenantsController::class, 'subscribe']);
            Route::get('/tenants/{tenantId}/invoices', [App\Http\Controllers\Api\v1\TenantsController::class, 'invoices']);
            Route::post('/tenants/invoices/{invoiceId}/pay', [App\Http\Controllers\Api\v1\TenantsController::class, 'payInvoice']);
        });
        Route::get('/subscription-plans', [App\Http\Controllers\Api\v1\TenantsController::class, 'plans']);

        // Offline-first Sync (Phase 3)
        Route::post('/sync/push', [App\Http\Controllers\Api\v1\SyncController::class, 'push']);
        Route::get('/sync/pull', [App\Http\Controllers\Api\v1\SyncController::class, 'pull']);
        Route::get('/sync/status', [App\Http\Controllers\Api\v1\SyncController::class, 'status']);

        // AI Advanced (Phase 4)
        Route::middleware('permission:view_reports')->group(function () {
            Route::post('/ai/demand-forecast', [App\Http\Controllers\Api\v1\AIController::class, 'demandForecast']);
            Route::get('/ai/demand-forecast/batch', [App\Http\Controllers\Api\v1\AIController::class, 'batchForecasts']);
            Route::post('/ai/price-optimization', [App\Http\Controllers\Api\v1\AIController::class, 'priceOptimization']);
            Route::get('/ai/price-optimization/batch', [App\Http\Controllers\Api\v1\AIController::class, 'batchPriceOptimization']);
            Route::get('/ai/forecast-history/{productId}', [App\Http\Controllers\Api\v1\AIController::class, 'forecastHistory']);
        });

        // Marketplace Integration (Phase 4)
        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/marketplace', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'index']);
            Route::post('/marketplace/connect', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'connect']);
            Route::post('/marketplace/{id}/sync-stock', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'syncStock']);
            Route::post('/marketplace/{id}/sync-products', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'syncProducts']);
            Route::post('/marketplace/{id}/map-product', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'mapProduct']);
            Route::post('/marketplace/{id}/disconnect', [App\Http\Controllers\Api\v1\MarketplaceController::class, 'disconnect']);
        });

        // IoT Integration (Phase 4)
        Route::middleware('permission:manage_products')->group(function () {
            Route::get('/iot/sensors', [App\Http\Controllers\Api\v1\IoTController::class, 'sensors']);
            Route::post('/iot/sensors', [App\Http\Controllers\Api\v1\IoTController::class, 'registerSensor']);
            Route::post('/iot/readings', [App\Http\Controllers\Api\v1\IoTController::class, 'recordReading']);
            Route::get('/iot/sensors/{id}/readings', [App\Http\Controllers\Api\v1\IoTController::class, 'sensorReadings']);
            Route::get('/iot/alerts', [App\Http\Controllers\Api\v1\IoTController::class, 'alerts']);
        });
    });
});
