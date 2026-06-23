<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('app_settings')->insertOrIgnore([
            ['key' => 'tax_rate', 'value' => '0.11', 'type' => 'float', 'description' => 'PPN rate (0.11 = 11%)'],
            ['key' => 'tax_enabled', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable PPN tax calculation'],
            ['key' => 'company_name', 'value' => 'Panglong ERP', 'type' => 'string', 'description' => 'Company name for print'],
            ['key' => 'company_address', 'value' => 'Jl. Contoh No. 123', 'type' => 'string', 'description' => 'Company address'],
            ['key' => 'company_phone', 'value' => '021-1234567', 'type' => 'string', 'description' => 'Company phone'],
            ['key' => 'currency', 'value' => 'IDR', 'type' => 'string', 'description' => 'Currency code'],
            ['key' => 'session_timeout_minutes', 'value' => '30', 'type' => 'integer', 'description' => 'Session timeout in minutes'],
            ['key' => 'low_stock_threshold_days', 'value' => '7', 'type' => 'integer', 'description' => 'Days of stock before low alert'],
        ]);
    }
}
