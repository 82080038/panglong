<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business branches (cabang usaha)
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->notNullable();
            $table->string('name')->notNullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->enum('type', ['pusat', 'cabang', 'agen'])->default('cabang');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('type');
        });

        // Employees (orang — salesman, driver, gudang, kasir, dll)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique()->notNullable();
            $table->string('nik')->nullable();
            $table->string('full_name')->notNullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->enum('position', ['manager', 'salesman', 'kasir', 'gudang', 'driver', 'accounting', 'supervisor', 'staff'])->notNullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->enum('status', ['active', 'resigned', 'terminated'])->default('active');
            $table->string('vehicle_plate')->nullable();
            $table->string('sim_no')->nullable();
            $table->timestamps();

            $table->index('employee_no');
            $table->index('position');
            $table->index('branch_id');
            $table->index('status');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Add branch_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('role_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        // Enhance warehouses: type, branch_id, manager_employee_id, capacity
        Schema::table('warehouses', function (Blueprint $table) {
            $table->enum('type', ['utama', 'cabang', 'transit', 'display', 'eksternal'])->default('utama')->after('is_active');
            $table->unsignedBigInteger('branch_id')->nullable()->after('type');
            $table->unsignedBigInteger('manager_employee_id')->nullable()->after('branch_id');
            $table->decimal('capacity_m2', 10, 2)->default(0)->after('manager_employee_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('manager_employee_id')->references('id')->on('employees')->onDelete('set null');
        });

        // Warehouse locations (rak/blok/shelf within warehouse)
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_id')->notNullable();
            $table->string('code')->notNullable();
            $table->string('name')->notNullable();
            $table->enum('zone_type', ['rack', 'block', 'shelf', 'pallet', 'floor'])->default('rack');
            $table->string('aisle')->nullable();
            $table->string('level')->nullable();
            $table->decimal('max_weight_kg', 10, 2)->default(0);
            $table->decimal('capacity_m2', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['warehouse_id', 'code']);
            $table->index('warehouse_id');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });

        // Add warehouse_location_id to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_location_id')->nullable()->after('warehouse_id');
            $table->foreign('warehouse_location_id')->references('id')->on('warehouse_locations')->onDelete('set null');
        });

        // Add warehouse_location_id to products (default location)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_location_id')->nullable()->after('location');
            $table->foreign('warehouse_location_id')->references('id')->on('warehouse_locations')->onDelete('set null');
        });

        // Fixed assets (inventaris & penyusutan)
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code')->unique()->notNullable();
            $table->string('name')->notNullable();
            $table->enum('category', ['kendaraan', 'bangunan', 'peralatan', 'inventaris', 'tanah', 'lainnya'])->notNullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('serial_no')->nullable();
            $table->string('plate_no')->nullable();
            $table->date('acquisition_date')->notNullable();
            $table->decimal('acquisition_cost', 15, 2)->notNullable();
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_months')->notNullable();
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->decimal('monthly_depreciation', 15, 2)->default(0);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('book_value', 15, 2)->default(0);
            $table->unsignedBigInteger('account_asset_id')->nullable();
            $table->unsignedBigInteger('account_accum_dep_id')->nullable();
            $table->unsignedBigInteger('account_dep_expense_id')->nullable();
            $table->enum('status', ['active', 'disposed', 'fully_depreciated'])->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('asset_code');
            $table->index('category');
            $table->index('status');
            $table->index('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('account_asset_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('account_accum_dep_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('account_dep_expense_id')->references('id')->on('chart_of_accounts')->onDelete('set null');
        });

        // Depreciation runs (history of depreciation postings)
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixed_asset_id')->notNullable();
            $table->date('depreciation_date')->notNullable();
            $table->decimal('amount', 15, 2)->notNullable();
            $table->decimal('accumulated_after', 15, 2)->notNullable();
            $table->decimal('book_value_after', 15, 2)->notNullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('fixed_asset_id');
            $table->index('depreciation_date');
            $table->foreign('fixed_asset_id')->references('id')->on('fixed_assets')->onDelete('cascade');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Cash transactions (kas masuk/keluar, petty cash)
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_no')->unique()->notNullable();
            $table->enum('type', ['cash_in', 'cash_out', 'petty_cash', 'cash_transfer', 'setoran', 'withdrawal'])->notNullable();
            $table->enum('account_type', ['kas_tunai', 'kas_kecil', 'bank_bca', 'bank_mandiri', 'bank_bni'])->default('kas_tunai');
            $table->date('transaction_date')->notNullable();
            $table->decimal('amount', 15, 2)->notNullable();
            $table->string('description')->notNullable();
            $table->enum('category', ['operasional', 'gaji', 'perlengkapan', 'sewa', 'listrik', 'pajak', 'lainnya', 'setoran_bank', 'tarik_tunai'])->default('operasional');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->string('recipient')->nullable();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('transaction_no');
            $table->index('type');
            $table->index('account_type');
            $table->index('transaction_date');
            $table->index('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Bank statements (imported bank mutation lines for reconciliation)
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->enum('bank_account', ['bank_bca', 'bank_mandiri', 'bank_bni'])->notNullable();
            $table->date('transaction_date')->notNullable();
            $table->string('description')->notNullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('reference_no')->nullable();
            $table->enum('reconciliation_status', ['unreconciled', 'reconciled', 'ignored'])->default('unreconciled');
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->unsignedBigInteger('cash_transaction_id')->nullable();
            $table->date('reconciled_at')->nullable();
            $table->unsignedBigInteger('reconciled_by')->nullable();
            $table->timestamps();

            $table->index('bank_account');
            $table->index('transaction_date');
            $table->index('reconciliation_status');
            $table->foreign('journal_entry_id')->references('id')->on('journal_entries')->onDelete('set null');
            $table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->onDelete('set null');
            $table->foreign('reconciled_by')->references('id')->on('users')->onDelete('set null');
        });

        // Add branch_id to sales and purchase_orders
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('created_by');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('created_by');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::dropIfExists('bank_statements');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['warehouse_location_id']);
            $table->dropColumn('warehouse_location_id');
        });
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_location_id']);
            $table->dropColumn('warehouse_location_id');
        });
        Schema::dropIfExists('warehouse_locations');
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['manager_employee_id']);
            $table->dropColumn(['type', 'branch_id', 'manager_employee_id', 'capacity_m2']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
        });
        Schema::dropIfExists('employees');
        Schema::dropIfExists('branches');
    }
};
