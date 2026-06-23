<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class CashManagementService
{
    private const ACCOUNT_MAP = [
        'kas_tunai' => '1010',
        'kas_kecil' => '1015',
        'bank_bca' => '1020',
        'bank_mandiri' => '1025',
        'bank_bni' => '1030',
    ];

    private const CATEGORY_EXPENSE_MAP = [
        'gaji' => '6200',
        'perlengkapan' => '6400',
        'sewa' => '6300',
        'listrik' => '6310',
        'pajak' => '6500',
        'lainnya' => '6900',
        'operasional' => '6900',
    ];

    public function createTransaction(array $data, int $userId): CashTransaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $txNo = 'CT' . date('Ymd') . str_pad(CashTransaction::count() + 1, 4, '0', STR_PAD_LEFT);

            $tx = CashTransaction::create([
                'transaction_no' => $txNo,
                'type' => $data['type'],
                'account_type' => $data['account_type'],
                'transaction_date' => $data['transaction_date'],
                'amount' => $data['amount'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'operasional',
                'branch_id' => $data['branch_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'recipient' => $data['recipient'] ?? null,
                'created_by' => $userId,
            ]);

            $journal = $this->postCashJournal($tx, $userId);
            if ($journal) {
                $tx->update(['journal_entry_id' => $journal->id]);
            }

            return $tx;
        });
    }

    public function getCashBalance(string $accountType, ?string $asOfDate = null): float
    {
        $code = self::ACCOUNT_MAP[$accountType] ?? null;
        if (!$code) return 0;

        $account = ChartOfAccount::where('code', $code)->first();
        if (!$account) return 0;

        $query = JournalEntryLine::where('account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                $q->where('status', 'posted');
                if ($asOfDate) {
                    $q->where('entry_date', '<=', $asOfDate);
                }
            });

        $debit = (clone $query)->sum('debit');
        $credit = (clone $query)->sum('credit');

        return round($debit - $credit, 2);
    }

    public function getAllBalances(?string $asOfDate = null): array
    {
        $balances = [];
        foreach (array_keys(self::ACCOUNT_MAP) as $type) {
            $balances[$type] = $this->getCashBalance($type, $asOfDate);
        }
        $balances['total'] = array_sum($balances);
        return $balances;
    }

    public function getCashFlowSummary(string $dateFrom, string $dateTo): array
    {
        $transactions = CashTransaction::whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->orderBy('transaction_date')
            ->get();

        $summary = [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'cash_in' => 0,
            'cash_out' => 0,
            'net' => 0,
            'by_category' => [],
            'by_account' => [],
            'transactions' => $transactions->count(),
        ];

        foreach ($transactions as $tx) {
            $amount = (float) $tx->amount;
            if (in_array($tx->type, ['cash_in', 'setoran'])) {
                $summary['cash_in'] += $amount;
            } else {
                $summary['cash_out'] += $amount;
            }

            $cat = $tx->category;
            if (!isset($summary['by_category'][$cat])) {
                $summary['by_category'][$cat] = 0;
            }
            $summary['by_category'][$cat] += $amount;

            $acc = $tx->account_type;
            if (!isset($summary['by_account'][$acc])) {
                $summary['by_account'][$acc] = ['in' => 0, 'out' => 0];
            }
            if (in_array($tx->type, ['cash_in', 'setoran'])) {
                $summary['by_account'][$acc]['in'] += $amount;
            } else {
                $summary['by_account'][$acc]['out'] += $amount;
            }
        }

        $summary['net'] = $summary['cash_in'] - $summary['cash_out'];

        return $summary;
    }

    private function postCashJournal(CashTransaction $tx, int $userId): ?JournalEntry
    {
        $cashCode = self::ACCOUNT_MAP[$tx->account_type] ?? null;
        if (!$cashCode) return null;

        $cashAccount = ChartOfAccount::where('code', $cashCode)->first();
        if (!$cashAccount) return null;

        $journal = JournalEntry::create([
            'journal_no' => 'JE-CT-' . $tx->id . '-' . date('Ymd'),
            'entry_date' => $tx->transaction_date,
            'description' => $tx->description . ' (' . $tx->transaction_no . ')',
            'reference_type' => 'cash_transaction',
            'reference_id' => $tx->id,
            'status' => 'posted',
            'created_by' => $userId,
        ]);

        if (in_array($tx->type, ['cash_in', 'setoran'])) {
            // Debit cash/bank
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $cashAccount->id,
                'debit' => $tx->amount,
                'credit' => 0,
                'description' => $tx->description,
            ]);

            // Credit counterpart (default: pendapatan lain-lain or based on category)
            $contraCode = $this->getContraAccountCode($tx->type, $tx->category, 'in');
            $contraAccount = $contraCode ? ChartOfAccount::where('code', $contraCode)->first() : null;
            if ($contraAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $contraAccount->id,
                    'debit' => 0,
                    'credit' => $tx->amount,
                    'description' => $tx->description,
                ]);
            }
        } elseif ($tx->type === 'cash_transfer') {
            // Transfer between accounts: need target account
            // For now, just debit the target and credit the source
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $tx->amount,
                'description' => 'Transfer keluar ' . $tx->description,
            ]);
        } else {
            // cash_out, petty_cash, withdrawal
            $expenseCode = self::CATEGORY_EXPENSE_MAP[$tx->category] ?? '6900';
            $expenseAccount = ChartOfAccount::where('code', $expenseCode)->first();

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $expenseAccount?->id ?? $cashAccount->id,
                'debit' => $tx->amount,
                'credit' => 0,
                'description' => $tx->description,
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $cashAccount->id,
                'debit' => 0,
                'credit' => $tx->amount,
                'description' => $tx->description,
            ]);
        }

        return $journal;
    }

    private function getContraAccountCode(string $type, string $category, string $direction): ?string
    {
        if ($direction === 'in') {
            if ($type === 'setoran') return null; // Setoran = transfer from another account
            return '4900'; // Pendapatan lain-lain
        }
        return self::CATEGORY_EXPENSE_MAP[$category] ?? '6900';
    }
}
