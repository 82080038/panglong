<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number')->unique()->notNullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->default('Walk-in Customer');
            $table->date('order_date')->notNullable();
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 15, 2)->notNullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->notNullable();
            $table->enum('payment_method', ['cash', 'credit', 'transfer'])->default('cash');
            $table->enum('status', ['draft', 'confirmed', 'processing', 'delivered', 'invoiced', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('so_number');
            $table->index('customer_id');
            $table->index('order_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('set null');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->decimal('quantity', 10, 3)->notNullable();
            $table->decimal('bonus_qty', 10, 3)->default(0);
            $table->decimal('delivered_qty', 10, 3)->default(0);
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->notNullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sales_order_id');
            $table->index('product_id');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
