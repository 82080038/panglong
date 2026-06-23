<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique()->notNullable();
            $table->unsignedBigInteger('sale_id')->notNullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->date('return_date')->notNullable();
            $table->decimal('total_refund', 15, 2)->notNullable();
            $table->enum('refund_method', ['cash', 'credit', 'transfer'])->default('cash');
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('reason')->notNullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('return_no');
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('status');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_return_id')->notNullable();
            $table->unsignedBigInteger('sale_item_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->decimal('quantity', 10, 3)->notNullable();
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->decimal('refund_amount', 15, 2)->notNullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('sales_return_id');
            $table->index('product_id');
            $table->foreign('sales_return_id')->references('id')->on('sales_returns')->onDelete('cascade');
            $table->foreign('sale_item_id')->references('id')->on('sale_items')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
