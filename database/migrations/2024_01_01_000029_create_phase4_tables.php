<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI demand forecasts
        Schema::create('demand_forecasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->date('forecast_date');
            $table->integer('horizon_days');
            $table->decimal('predicted_demand', 15, 3);
            $table->decimal('confidence_lower', 15, 3);
            $table->decimal('confidence_upper', 15, 3);
            $table->float('confidence_score');
            $table->string('method');
            $table->json('factors')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // Price optimization suggestions
        Schema::create('price_optimizations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->decimal('current_price', 15, 2);
            $table->decimal('suggested_price', 15, 2);
            $table->decimal('current_margin', 8, 2);
            $table->decimal('suggested_margin', 8, 2);
            $table->decimal('estimated_demand_change', 8, 2);
            $table->decimal('estimated_revenue_change', 15, 2);
            $table->text('reasoning')->nullable();
            $table->date('generated_date');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // Marketplace integrations
        Schema::create('marketplace_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->enum('platform', ['tokopedia', 'shopee', 'bukalapak', 'lazada', 'blibli']);
            $table->string('shop_id');
            $table->string('shop_name');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // Marketplace product mappings
        Schema::create('marketplace_product_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('integration_id');
            $table->unsignedBigInteger('product_id');
            $table->string('marketplace_item_id');
            $table->string('marketplace_url')->nullable();
            $table->decimal('marketplace_price', 15, 2)->nullable();
            $table->integer('marketplace_stock')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('integration_id')->references('id')->on('marketplace_integrations')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // IoT sensor readings
        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('sensor_id', 100)->unique();
            $table->string('name');
            $table->enum('type', ['temperature', 'humidity', 'weight', 'proximity', 'door']);
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });

        Schema::create('iot_sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sensor_id');
            $table->decimal('value', 15, 3);
            $table->string('unit', 20)->nullable();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->foreign('sensor_id')->references('id')->on('iot_sensors')->cascadeOnDelete();
            $table->index(['sensor_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_sensor_readings');
        Schema::dropIfExists('iot_sensors');
        Schema::dropIfExists('marketplace_product_mappings');
        Schema::dropIfExists('marketplace_integrations');
        Schema::dropIfExists('price_optimizations');
        Schema::dropIfExists('demand_forecasts');
    }
};
