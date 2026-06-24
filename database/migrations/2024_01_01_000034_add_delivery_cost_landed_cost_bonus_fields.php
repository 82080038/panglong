<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add delivery_cost to sales
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('delivery_cost', 15, 2)->default(0)->after('total');
        });

        // Add delivery_cost to deliveries
        Schema::table('deliveries', function (Blueprint $table) {
            $table->decimal('delivery_cost', 15, 2)->default(0)->after('vehicle_plate');
            $table->string('origin_address')->nullable()->after('delivery_address');
        });

        // Add bonus_qty to sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('bonus_qty', 10, 3)->default(0)->after('quantity');
        });

        // Add bonus_qty to purchase_items
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->decimal('bonus_qty', 10, 3)->default(0)->after('quantity');
        });

        // Add weight & dimension to products
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('weight_kg', 10, 3)->default(0)->after('sell_price');
            $table->decimal('length_cm', 10, 2)->default(0)->after('weight_kg');
            $table->decimal('width_cm', 10, 2)->default(0)->after('length_cm');
            $table->decimal('height_cm', 10, 2)->default(0)->after('width_cm');
        });

        // Add landed_cost fields to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('freight_cost', 15, 2)->default(0)->after('total');
            $table->decimal('insurance_cost', 15, 2)->default(0)->after('freight_cost');
            $table->decimal('handling_cost', 15, 2)->default(0)->after('insurance_cost');
            $table->decimal('landed_total', 15, 2)->default(0)->after('handling_cost');
        });

        // Add delivered_qty to delivery_items for partial delivery tracking
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->enum('delivery_status', ['pending', 'delivered', 'failed'])->default('pending')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_items', function (Blueprint $table) {
            $table->dropColumn('delivery_status');
        });
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['freight_cost', 'insurance_cost', 'handling_cost', 'landed_total']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'length_cm', 'width_cm', 'height_cm']);
        });
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('bonus_qty');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('bonus_qty');
        });
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropColumn(['delivery_cost', 'origin_address']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('delivery_cost');
        });
    }
};
