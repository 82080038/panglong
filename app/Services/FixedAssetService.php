<?php

namespace App\Services;

use App\Models\FixedAsset;
use App\Models\AssetDepreciation;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class FixedAssetService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function createAsset(array $data, int $userId): FixedAsset
    {
        $depreciation = $this->calculateMonthlyDepreciation($data);

        $asset = FixedAsset::create(array_merge($data, [
            'monthly_depreciation' => $depreciation,
            'accumulated_depreciation' => 0,
            'book_value' => $data['acquisition_cost'],
            'status' => 'active',
        ]));

        $this->assignDefaultAccounts($asset);

        return $asset;
    }

    public function calculateMonthlyDepreciation(array $data): float
    {
        $cost = (float) $data['acquisition_cost'];
        $salvage = (float) ($data['salvage_value'] ?? 0);
        $lifeMonths = (int) $data['useful_life_months'];

        if ($lifeMonths <= 0) return 0;

        return round(($cost - $salvage) / $lifeMonths, 2);
    }

    public function runDepreciation(int $assetId, string $date, int $userId): AssetDepreciation
    {
        return DB::transaction(function () use ($assetId, $date, $userId) {
            $asset = FixedAsset::findOrFail($assetId);

            if ($asset->status !== 'active') {
                throw new \Exception('Asset is not active');
            }

            if ($asset->book_value <= $asset->salvage_value) {
                $asset->update(['status' => 'fully_depreciated']);
                throw new \Exception('Asset is fully depreciated');
            }

            $amount = min($asset->monthly_depreciation, $asset->book_value - $asset->salvage_value);
            $newAccumulated = $asset->accumulated_depreciation + $amount;
            $newBookValue = $asset->book_value - $amount;

            $journal = $this->postDepreciationJournal($asset, $amount, $date, $userId);

            $dep = AssetDepreciation::create([
                'fixed_asset_id' => $asset->id,
                'depreciation_date' => $date,
                'amount' => $amount,
                'accumulated_after' => $newAccumulated,
                'book_value_after' => $newBookValue,
                'journal_entry_id' => $journal?->id,
                'created_by' => $userId,
            ]);

            $asset->update([
                'accumulated_depreciation' => $newAccumulated,
                'book_value' => $newBookValue,
            ]);

            if ($newBookValue <= $asset->salvage_value) {
                $asset->update(['status' => 'fully_depreciated']);
            }

            return $dep;
        });
    }

    public function runMonthlyDepreciationAll(string $date, int $userId): array
    {
        $assets = FixedAsset::where('status', 'active')->get();
        $results = [];

        foreach ($assets as $asset) {
            try {
                $dep = $this->runDepreciation($asset->id, $date, $userId);
                $results[] = ['asset_id' => $asset->id, 'asset_code' => $asset->asset_code, 'amount' => $dep->amount, 'status' => 'ok'];
            } catch (\Exception $e) {
                $results[] = ['asset_id' => $asset->id, 'asset_code' => $asset->asset_code, 'status' => 'skipped', 'message' => $e->getMessage()];
            }
        }

        return $results;
    }

    public function disposeAsset(int $assetId, string $date, float $disposalValue, int $userId): FixedAsset
    {
        return DB::transaction(function () use ($assetId, $date, $disposalValue, $userId) {
            $asset = FixedAsset::findOrFail($assetId);
            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $date,
                'disposal_value' => $disposalValue,
            ]);

            return $asset;
        });
    }

    private function postDepreciationJournal(FixedAsset $asset, float $amount, string $date, int $userId): ?JournalEntry
    {
        $depExpenseAccount = $asset->account_dep_expense_id
            ? ChartOfAccount::find($asset->account_dep_expense_id)
            : ChartOfAccount::where('code', '6100')->first();

        $accumDepAccount = $asset->account_accum_dep_id
            ? ChartOfAccount::find($asset->account_accum_dep_id)
            : ChartOfAccount::where('code', '1510')->first();

        if (!$depExpenseAccount || !$accumDepAccount) return null;

        $journal = JournalEntry::create([
            'journal_no' => 'JE-DEP-' . $asset->id . '-' . date('Ymd', strtotime($date)),
            'entry_date' => $date,
            'description' => 'Penyusutan aset ' . $asset->asset_code . ' - ' . $asset->name,
            'reference_type' => 'asset_depreciation',
            'reference_id' => $asset->id,
            'status' => 'posted',
            'created_by' => $userId,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $depExpenseAccount->id,
            'debit' => $amount,
            'credit' => 0,
            'description' => 'Beban penyusutan ' . $asset->name,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $accumDepAccount->id,
            'debit' => 0,
            'credit' => $amount,
            'description' => 'Akumulasi penyusutan ' . $asset->name,
        ]);

        return $journal;
    }

    private function assignDefaultAccounts(FixedAsset $asset): void
    {
        $accountMap = [
            'kendaraan' => ['asset' => '1500', 'accum' => '1510', 'expense' => '6100'],
            'bangunan' => ['asset' => '1520', 'accum' => '1530', 'expense' => '6110'],
            'peralatan' => ['asset' => '1540', 'accum' => '1550', 'expense' => '6120'],
            'inventaris' => ['asset' => '1540', 'accum' => '1550', 'expense' => '6120'],
            'lainnya' => ['asset' => '1540', 'accum' => '1550', 'expense' => '6120'],
        ];

        $codes = $accountMap[$asset->category] ?? $accountMap['lainnya'];

        if (!$asset->account_asset_id) {
            $acc = ChartOfAccount::where('code', $codes['asset'])->first();
            if ($acc) $asset->update(['account_asset_id' => $acc->id]);
        }
        if (!$asset->account_accum_dep_id) {
            $acc = ChartOfAccount::where('code', $codes['accum'])->first();
            if ($acc) $asset->update(['account_accum_dep_id' => $acc->id]);
        }
        if (!$asset->account_dep_expense_id) {
            $acc = ChartOfAccount::where('code', $codes['expense'])->first();
            if ($acc) $asset->update(['account_dep_expense_id' => $acc->id]);
        }
    }
}
