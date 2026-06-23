<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_no')->unique()->notNullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name')->default('Walk-in Customer');
            $table->date('quote_date')->notNullable();
            $table->date('valid_until')->notNullable();
            $table->decimal('subtotal', 15, 2)->notNullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->notNullable();
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('delivery_address')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('quote_no');
            $table->index('customer_id');
            $table->index('quote_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->decimal('quantity', 10, 3)->notNullable();
            $table->decimal('bonus_qty', 10, 3)->default(0);
            $table->unsignedBigInteger('unit_id')->notNullable();
            $table->decimal('unit_price', 15, 2)->notNullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->notNullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('quotation_id');
            $table->index('product_id');
            $table->foreign('quotation_id')->references('id')->on('quotations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
            $table->foreign('unit_id')->references('id')->on('product_units')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
