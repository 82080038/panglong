<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('subtype', ['current_asset', 'fixed_asset', 'current_liability', 'long_term_liability', 'capital', 'retained_earnings', 'sales_revenue', 'other_revenue', 'cogs', 'operating_expense', 'other_expense'])->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });

        // Journal Entries (header)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no', 50)->unique();
            $table->date('entry_date');
            $table->string('description');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('posted');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index(['reference_type', 'reference_id']);
        });

        // Journal Entry Lines (debit/credit per account)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('chart_of_accounts');
        });

        // Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Add warehouse_id to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_id')->nullable()->after('product_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->index('warehouse_id');
        });

        // Stock transfers
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_no', 50)->unique();
            $table->date('transfer_date');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');
            $table->enum('status', ['pending', 'in_transit', 'received', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 3);
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();

            $table->foreign('transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });

        // Reorder suggestions (AI basic)
        Schema::create('reorder_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('current_stock', 15, 3);
            $table->decimal('avg_daily_usage', 15, 3);
            $table->integer('days_of_supply');
            $table->decimal('suggested_order_qty', 15, 3);
            $table->enum('priority', ['critical', 'high', 'medium', 'low']);
            $table->text('reason')->nullable();
            $table->date('generated_date');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
        Schema::dropIfExists('reorder_suggestions');
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
