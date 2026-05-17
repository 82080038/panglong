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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payable_id')->notNullable();
            $table->enum('payable_type', ['receivable', 'payable'])->notNullable();
            $table->decimal('amount', 15, 2)->notNullable();
            $table->date('payment_date')->notNullable();
            $table->enum('payment_method', ['cash', 'transfer', 'check'])->notNullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->index(['payable_type', 'payable_id']);
            $table->index('payment_date');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
