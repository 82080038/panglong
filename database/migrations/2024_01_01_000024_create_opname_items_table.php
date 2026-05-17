<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opname_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opname_id')->notNullable();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->decimal('system_qty', 10, 3)->notNullable();
            $table->decimal('physical_qty', 10, 3)->notNullable();
            $table->decimal('difference', 10, 3)->notNullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index('opname_id');
            $table->index('product_id');
            $table->foreign('opname_id')->references('id')->on('stock_opnames')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opname_items');
    }
};
