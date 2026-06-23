<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // === ASET (ASSETS) ===
            // Aset Lancar
            ['code' => '1000', 'name' => 'Kas & Bank', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1010', 'name' => 'Kas Tunai', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1011', 'name' => 'Kas Kecil (Petty Cash)', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1020', 'name' => 'Bank BCA', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1021', 'name' => 'Bank Mandiri', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1022', 'name' => 'Bank BNI', 'type' => 'asset', 'subtype' => 'current_asset', 'parent_code' => '1000'],
            ['code' => '1100', 'name' => 'Piutang Usaha', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1150', 'name' => 'Uang Muka Pembelian', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1200', 'name' => 'Persediaan Barang', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1300', 'name' => 'PPN Masukan', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '1400', 'name' => 'Aset Pajak Dibayar Dimuka', 'type' => 'asset', 'subtype' => 'current_asset'],
            // Aset Tetap
            ['code' => '1500', 'name' => 'Aset Tetap', 'type' => 'asset', 'subtype' => 'fixed_asset'],
            ['code' => '1510', 'name' => 'Kendaraan', 'type' => 'asset', 'subtype' => 'fixed_asset', 'parent_code' => '1500'],
            ['code' => '1520', 'name' => 'Peralatan Kantor', 'type' => 'asset', 'subtype' => 'fixed_asset', 'parent_code' => '1500'],
            ['code' => '1530', 'name' => 'Bangunan Gudang', 'type' => 'asset', 'subtype' => 'fixed_asset', 'parent_code' => '1500'],
            ['code' => '1590', 'name' => 'Akumulasi Penyusutan', 'type' => 'asset', 'subtype' => 'fixed_asset', 'parent_code' => '1500'],

            // === KEWAJIBAN (LIABILITIES) ===
            // Kewajiban Jangka Pendek
            ['code' => '2000', 'name' => 'Hutang Usaha', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2100', 'name' => 'PPN Keluaran', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2200', 'name' => 'Pinjaman Jangka Pendek', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2300', 'name' => 'Hutang Pajak', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '2400', 'name' => 'Hutang Gaji', 'type' => 'liability', 'subtype' => 'current_liability'],
            // Kewajiban Jangka Panjang
            ['code' => '2500', 'name' => 'Pinjaman Jangka Panjang', 'type' => 'liability', 'subtype' => 'long_term_liability'],

            // === MODAL (EQUITY) ===
            ['code' => '3000', 'name' => 'Modal Pemilik', 'type' => 'equity', 'subtype' => 'capital'],
            ['code' => '3100', 'name' => 'Laba Ditahan', 'type' => 'equity', 'subtype' => 'retained_earnings'],
            ['code' => '3200', 'name' => 'Laba Tahun Berjalan', 'type' => 'equity', 'subtype' => 'retained_earnings'],

            // === PENDAPATAN (REVENUE) ===
            ['code' => '4000', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'subtype' => 'sales_revenue'],
            ['code' => '4010', 'name' => 'Penjualan Semen & Beton', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4020', 'name' => 'Penjualan Besi & Baja', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4030', 'name' => 'Penjualan Cat & Finishing', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4040', 'name' => 'Penjualan Keramik & Granit', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4050', 'name' => 'Penjualan Sanitary & Plumbing', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4090', 'name' => 'Penjualan Lain-lain', 'type' => 'revenue', 'subtype' => 'sales_revenue', 'parent_code' => '4000'],
            ['code' => '4100', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'subtype' => 'other_revenue'],
            ['code' => '4200', 'name' => 'Potongan Penjualan', 'type' => 'revenue', 'subtype' => 'sales_revenue'],

            // === BEBAN (EXPENSES) ===
            ['code' => '5000', 'name' => 'HPP (Cost of Goods Sold)', 'type' => 'expense', 'subtype' => 'cogs'],
            ['code' => '5100', 'name' => 'Ongkos Angkut Pembelian', 'type' => 'expense', 'subtype' => 'cogs'],
            ['code' => '5200', 'name' => 'Kerugian Persediaan', 'type' => 'expense', 'subtype' => 'cogs'],
            ['code' => '6000', 'name' => 'Beban Operasional', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6100', 'name' => 'Beban Gaji & Tunjangan', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6200', 'name' => 'Beban Sewa', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6300', 'name' => 'Beban Listrik & Air', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6400', 'name' => 'Beban Telekomunikasi', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6500', 'name' => 'Beban Ongkos Kirim', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6600', 'name' => 'Beban Perlengkapan Kantor', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6700', 'name' => 'Beban Pemeliharaan Kendaraan', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6800', 'name' => 'Beban Asuransi', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '6900', 'name' => 'Beban Penyusutan', 'type' => 'expense', 'subtype' => 'operating_expense', 'parent_code' => '6000'],
            ['code' => '7000', 'name' => 'Beban Pajak', 'type' => 'expense', 'subtype' => 'other_expense'],
            ['code' => '7100', 'name' => 'Potongan Pembelian', 'type' => 'expense', 'subtype' => 'other_expense'],
        ];

        $codeToId = [];
        foreach ($accounts as $acc) {
            $parentId = null;
            if (isset($acc['parent_code'])) {
                $parentId = $codeToId[$acc['parent_code']] ?? null;
            }
            $record = ChartOfAccount::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'subtype' => $acc['subtype'] ?? null,
                    'parent_id' => $parentId,
                    'is_active' => true,
                ]
            );
            $codeToId[$acc['code']] = $record->id;
        }
    }
}
