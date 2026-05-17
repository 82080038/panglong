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
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->notNullable();
            $table->string('unit_name')->notNullable();
            $table->decimal('conversion_factor', 10, 3)->notNullable();
            $table->boolean('is_base_unit')->default(false);
            $table->decimal('price_per_unit', 15, 2)->default(0);
            $table->timestamps();
            
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
