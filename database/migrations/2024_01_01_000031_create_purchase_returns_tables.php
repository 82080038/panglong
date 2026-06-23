<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique()->notNullable();
            $table->unsignedBigInteger('po_id')->notNullable();
            $table->unsignedBigInteger('supplier_id')->notNullable();
            $table->date('return_date')->notNullable();
            $table->decimal('total_refund', 15, 2)->notNullable();
            $table->enum('refund_method', ['cash', 'credit', 'transfer'])->default('credit');
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('reason')->notNullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('return_no');
            $table->index('po_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id')->notNullable();
            $table->unsignedBigInteger('purchase_item_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->decimal('quantity', 10, 3)->notNullable();
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->decimal('refund_amount', 15, 2)->notNullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('purchase_return_id');
            $table->index('product_id');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('purchase_item_id')->references('id')->on('purchase_items')->onDelete('restrict');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
