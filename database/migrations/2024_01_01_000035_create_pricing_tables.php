<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer-specific product pricing
        Schema::create('customer_product_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('custom_price', 15, 2)->notNullable();
            $table->decimal('min_qty', 10, 3)->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'product_id', 'unit_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('cascade');
        });

        // Volume-based tier pricing per product+unit
        Schema::create('product_tier_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('min_qty', 10, 3)->notNullable();
            $table->decimal('max_qty', 10, 3)->nullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'unit_id']);
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('cascade');
        });

        // Supplier price history
        Schema::create('supplier_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->date('effective_date')->notNullable();
            $table->date('end_date')->nullable();
            $table->string('po_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['supplier_id', 'product_id']);
            $table->index('effective_date');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_price_history');
        Schema::dropIfExists('product_tier_prices');
        Schema::dropIfExists('customer_product_prices');
    }
};
