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
        Schema::create('accounts_receivable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->notNullable();
            $table->unsignedBigInteger('sale_id')->notNullable();
            $table->decimal('amount', 15, 2)->notNullable();
            $table->decimal('balance', 15, 2)->notNullable();
            $table->date('due_date')->notNullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->timestamps();
            
            $table->index('customer_id');
            $table->index('sale_id');
            $table->index('due_date');
            $table->index('status');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
    }
};
