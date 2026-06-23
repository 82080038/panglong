<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\StockAdjustment;
use App\Observers\AuditLogObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Product::observe(AuditLogObserver::class);
        Customer::observe(AuditLogObserver::class);
        Sale::observe(AuditLogObserver::class);
        Supplier::observe(AuditLogObserver::class);
        StockAdjustment::observe(AuditLogObserver::class);
    }
}
