<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Models\FixedAsset;
use App\Models\User;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // Branches
        $branches = [
            ['code' => 'BR-PST', 'name' => 'Kantor Pusat', 'address' => 'Jl. Raya Bangunan No. 1, Jakarta Timur', 'phone' => '021-555-1001', 'email' => 'pusat@panglongjaya.co.id', 'manager_name' => 'Budi Santoso', 'type' => 'pusat'],
            ['code' => 'BR-CBG1', 'name' => 'Cabang Bekasi', 'address' => 'Jl. Industri Raya No. 15, Bekasi', 'phone' => '021-555-2001', 'email' => 'bekasi@panglongjaya.co.id', 'manager_name' => 'Andi Wijaya', 'type' => 'cabang'],
            ['code' => 'BR-CBG2', 'name' => 'Cabang Tangerang', 'address' => 'Jl. Raya Serpong No. 88, Tangerang', 'phone' => '021-555-3001', 'email' => 'tangerang@panglongjaya.co.id', 'manager_name' => 'Dedi Kurniawan', 'type' => 'cabang'],
            ['code' => 'BR-AGN1', 'name' => 'Agen Depok', 'address' => 'Jl. Margonda Raya No. 50, Depok', 'phone' => '021-555-4001', 'email' => 'depok@panglongjaya.co.id', 'manager_name' => 'Rudi Hartono', 'type' => 'agen'],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(['code' => $branch['code']], $branch);
        }

        $pusat = Branch::where('code', 'BR-PST')->first();
        $bekasi = Branch::where('code', 'BR-CBG1')->first();
        $tangerang = Branch::where('code', 'BR-CBG2')->first();

        // Update existing warehouses with branch + type
        $gudangUtama = Warehouse::where('code', 'WH-001')->first();
        if ($gudangUtama) {
            $gudangUtama->update([
                'type' => 'utama',
                'branch_id' => $pusat->id,
                'capacity_m2' => 500.00,
            ]);
        } else {
            $gudangUtama = Warehouse::create([
                'code' => 'WH-001', 'name' => 'Gudang Pusat Jakarta', 'type' => 'utama',
                'branch_id' => $pusat->id, 'capacity_m2' => 500.00, 'is_active' => true,
                'address' => 'Jl. Raya Bangunan No. 1, Jakarta Timur',
            ]);
        }

        $gudangBekasi = Warehouse::create([
            'code' => 'WH-BKS', 'name' => 'Gudang Bekasi', 'type' => 'cabang',
            'branch_id' => $bekasi->id, 'capacity_m2' => 300.00, 'is_active' => true,
            'address' => 'Jl. Industri Raya No. 15, Bekasi',
        ]);

        $gudangTangerang = Warehouse::create([
            'code' => 'WH-TGR', 'name' => 'Gudang Tangerang', 'type' => 'cabang',
            'branch_id' => $tangerang->id, 'capacity_m2' => 250.00, 'is_active' => true,
            'address' => 'Jl. Raya Serpong No. 88, Tangerang',
        ]);

        // Warehouse Locations (rak/blok) for gudang utama
        $locations = [
            ['warehouse_id' => $gudangUtama->id, 'code' => 'A-01', 'name' => 'Rak A-01 Semen', 'zone_type' => 'rack', 'aisle' => 'A', 'level' => '1'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'A-02', 'name' => 'Rak A-02 Semen', 'zone_type' => 'rack', 'aisle' => 'A', 'level' => '2'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'B-01', 'name' => 'Blok B-01 Besi', 'zone_type' => 'block', 'aisle' => 'B', 'level' => '1'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'B-02', 'name' => 'Blok B-02 Besi', 'zone_type' => 'block', 'aisle' => 'B', 'level' => '2'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'C-01', 'name' => 'Rak C-01 Cat', 'zone_type' => 'rack', 'aisle' => 'C', 'level' => '1'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'D-FLOOR', 'name' => 'Lantai D Keramik', 'zone_type' => 'floor', 'aisle' => 'D', 'level' => 'GF'],
            ['warehouse_id' => $gudangUtama->id, 'code' => 'E-01', 'name' => 'Pallet E-01 Sanitary', 'zone_type' => 'pallet', 'aisle' => 'E', 'level' => '1'],
            ['warehouse_id' => $gudangBekasi->id, 'code' => 'A-01', 'name' => 'Rak A-01 Semen', 'zone_type' => 'rack', 'aisle' => 'A', 'level' => '1'],
            ['warehouse_id' => $gudangBekasi->id, 'code' => 'B-01', 'name' => 'Blok B-01 Besi', 'zone_type' => 'block', 'aisle' => 'B', 'level' => '1'],
        ];

        foreach ($locations as $loc) {
            WarehouseLocation::updateOrCreate(
                ['warehouse_id' => $loc['warehouse_id'], 'code' => $loc['code']],
                $loc + ['is_active' => true]
            );
        }

        // Employees
        $employees = [
            ['employee_no' => 'EMP-001', 'nik' => '3171010101900001', 'full_name' => 'Budi Santoso', 'position' => 'manager', 'branch_id' => $pusat->id, 'warehouse_id' => $gudangUtama->id, 'base_salary' => 15000000, 'commission_pct' => 0, 'hire_date' => '2020-01-15'],
            ['employee_no' => 'EMP-002', 'nik' => '3171020202900002', 'full_name' => 'Andi Wijaya', 'position' => 'manager', 'branch_id' => $bekasi->id, 'warehouse_id' => $gudangBekasi->id, 'base_salary' => 12000000, 'commission_pct' => 0, 'hire_date' => '2020-03-01'],
            ['employee_no' => 'EMP-003', 'nik' => '3171030303900003', 'full_name' => 'Slamet Riyadi', 'position' => 'salesman', 'branch_id' => $pusat->id, 'base_salary' => 5000000, 'commission_pct' => 2.00, 'hire_date' => '2021-06-01'],
            ['employee_no' => 'EMP-004', 'nik' => '3171040404900004', 'full_name' => 'Joko Susilo', 'position' => 'salesman', 'branch_id' => $bekasi->id, 'base_salary' => 5000000, 'commission_pct' => 2.00, 'hire_date' => '2021-07-01'],
            ['employee_no' => 'EMP-005', 'nik' => '3171050505900005', 'full_name' => 'Ahmad Fauzi', 'position' => 'driver', 'branch_id' => $pusat->id, 'base_salary' => 4500000, 'commission_pct' => 0, 'hire_date' => '2021-01-10', 'vehicle_plate' => 'B 1234 ABC', 'sim_no' => 'SIM-B12345'],
            ['employee_no' => 'EMP-006', 'nik' => '3171060606900006', 'full_name' => 'Dedi Kurniawan', 'position' => 'driver', 'branch_id' => $pusat->id, 'base_salary' => 4500000, 'commission_pct' => 0, 'hire_date' => '2021-02-15', 'vehicle_plate' => 'B 5678 DEF', 'sim_no' => 'SIM-B56789'],
            ['employee_no' => 'EMP-007', 'nik' => '3171070707900007', 'full_name' => 'Siti Aminah', 'position' => 'kasir', 'branch_id' => $pusat->id, 'base_salary' => 4000000, 'commission_pct' => 0, 'hire_date' => '2022-01-01'],
            ['employee_no' => 'EMP-008', 'nik' => '3171080808900008', 'full_name' => 'Hendra Gunawan', 'position' => 'gudang', 'branch_id' => $pusat->id, 'warehouse_id' => $gudangUtama->id, 'base_salary' => 4000000, 'commission_pct' => 0, 'hire_date' => '2022-03-01'],
            ['employee_no' => 'EMP-009', 'nik' => '3171090909900009', 'full_name' => 'Rina Marlina', 'position' => 'accounting', 'branch_id' => $pusat->id, 'base_salary' => 8000000, 'commission_pct' => 0, 'hire_date' => '2021-01-15'],
            ['employee_no' => 'EMP-010', 'nik' => '3171101010900010', 'full_name' => 'Rudi Hartono', 'position' => 'supervisor', 'branch_id' => $tangerang->id, 'base_salary' => 7000000, 'commission_pct' => 0, 'hire_date' => '2022-06-01'],
        ];

        // Link users to employees
        $userMap = [
            'admin' => 1, 'manager1' => 1, 'kasir1' => 7, 'gudang1' => 8,
        ];

        foreach ($employees as $i => $emp) {
            $empData = $emp + ['status' => 'active'];
            $employee = Employee::updateOrCreate(['employee_no' => $empData['employee_no']], $empData);

            // Link to user if applicable
            $userIndex = $i + 1;
            if (isset($userMap[array_keys($userMap)[$i] ?? ''])) {
                $user = User::where('username', array_keys($userMap)[$i])->first();
                if ($user) {
                    $employee->update(['user_id' => $user->id]);
                    $user->update(['branch_id' => $emp['branch_id']]);
                }
            }
        }

        // Assign manager_employee_id to warehouses
        $managerEmp = Employee::where('employee_no', 'EMP-001')->first();
        if ($managerEmp && $gudangUtama) {
            $gudangUtama->update(['manager_employee_id' => $managerEmp->id]);
        }
        $bekasiManager = Employee::where('employee_no', 'EMP-002')->first();
        if ($bekasiManager && $gudangBekasi) {
            $gudangBekasi->update(['manager_employee_id' => $bekasiManager->id]);
        }

        // Fixed Assets
        $assets = [
            ['asset_code' => 'FA-001', 'name' => 'Truk Colt Diesel Engkel', 'category' => 'kendaraan', 'branch_id' => $pusat->id, 'plate_no' => 'B 1234 ABC', 'acquisition_date' => '2020-01-20', 'acquisition_cost' => 250000000, 'salvage_value' => 25000000, 'useful_life_months' => 60],
            ['asset_code' => 'FA-002', 'name' => 'Truk Engkel Bekasi', 'category' => 'kendaraan', 'branch_id' => $bekasi->id, 'plate_no' => 'B 5678 DEF', 'acquisition_date' => '2020-03-15', 'acquisition_cost' => 180000000, 'salvage_value' => 18000000, 'useful_life_months' => 60],
            ['asset_code' => 'FA-003', 'name' => 'Gudang Pusat Jakarta', 'category' => 'bangunan', 'branch_id' => $pusat->id, 'acquisition_date' => '2019-06-01', 'acquisition_cost' => 1500000000, 'salvage_value' => 150000000, 'useful_life_months' => 300],
            ['asset_code' => 'FA-004', 'name' => 'Gudang Bekasi', 'category' => 'bangunan', 'branch_id' => $bekasi->id, 'acquisition_date' => '2020-03-01', 'acquisition_cost' => 800000000, 'salvage_value' => 80000000, 'useful_life_months' => 300],
            ['asset_code' => 'FA-005', 'name' => 'Forklift Toyota 2.5T', 'category' => 'peralatan', 'branch_id' => $pusat->id, 'serial_no' => 'TY-250T-001', 'acquisition_date' => '2021-01-10', 'acquisition_cost' => 75000000, 'salvage_value' => 7500000, 'useful_life_months' => 60],
            ['asset_code' => 'FA-006', 'name' => 'Komputer & Printer Kasir', 'category' => 'inventaris', 'branch_id' => $pusat->id, 'acquisition_date' => '2022-01-01', 'acquisition_cost' => 15000000, 'salvage_value' => 1500000, 'useful_life_months' => 36],
            ['asset_code' => 'FA-007', 'name' => 'Rak Besi Gudang Pusat', 'category' => 'inventaris', 'branch_id' => $pusat->id, 'acquisition_date' => '2020-01-15', 'acquisition_cost' => 35000000, 'salvage_value' => 3500000, 'useful_life_months' => 120],
            ['asset_code' => 'FA-008', 'name' => 'Sepeda Motor Sales', 'category' => 'kendaraan', 'branch_id' => $pusat->id, 'plate_no' => 'B 9999 XYZ', 'acquisition_date' => '2021-06-01', 'acquisition_cost' => 25000000, 'salvage_value' => 2500000, 'useful_life_months' => 60],
        ];

        foreach ($assets as $asset) {
            $existing = FixedAsset::where('asset_code', $asset['asset_code'])->first();
            if (!$existing) {
                $cost = $asset['acquisition_cost'];
                $salvage = $asset['salvage_value'];
                $life = $asset['useful_life_months'];
                $monthlyDep = round(($cost - $salvage) / $life, 2);

                FixedAsset::create($asset + [
                    'depreciation_method' => 'straight_line',
                    'monthly_depreciation' => $monthlyDep,
                    'accumulated_depreciation' => 0,
                    'book_value' => $cost,
                    'status' => 'active',
                ]);
            }
        }
    }
}
