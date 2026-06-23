<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deliveries table (surat jalan)
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no')->unique();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->string('customer_name');
            $table->text('delivery_address')->nullable();
            $table->string('phone')->nullable();
            $table->date('delivery_date');
            $table->time('delivery_time')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->enum('status', ['pending', 'loaded', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_proof')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('delivery_no');
            $table->index('sale_id');
            $table->index('delivery_date');
            $table->index('status');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Delivery items
        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id')->nullable();
            $table->unsignedBigInteger('sale_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 10, 3)->notNullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('delivery_id');
            $table->index('sale_item_id');
            $table->foreign('delivery_id')->references('id')->on('deliveries')->onDelete('cascade');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('restrict');
        });

        // Add delivery_address to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('notes');
            $table->string('customer_name_snapshot')->nullable()->after('customer_id');
        });

        // Add received_quantity to purchase_items for partial receive
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('received_quantity', 10, 3)->default(0)->after('quantity');
        });

        // App settings table for configurable tax, etc.
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['delivery_address', 'customer_name_snapshot']);
        });
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('app_settings');
    }
};
