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
        Schema::create('accounts_payable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_id')->notNullable();
            $table->unsignedBigInteger('po_id')->notNullable();
            $table->decimal('amount', 15, 2)->notNullable();
            $table->decimal('balance', 15, 2)->notNullable();
            $table->date('due_date')->notNullable();
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->timestamps();
            
            $table->index('supplier_id');
            $table->index('po_id');
            $table->index('due_date');
            $table->index('status');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->foreign('po_id')->references('id')->on('purchase_orders')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_payable');
    }
};
