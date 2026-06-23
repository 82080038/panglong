<?php
namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Sale;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function postSaleJournal(Sale $sale): JournalEntry
    {
        return DB::transaction(function () use ($sale) {
            $journalNo = 'JE-SALE-' . $sale->id . '-' . date('Ymd');
            $existing = JournalEntry::where('reference_type', 'sale')->where('reference_id', $sale->id)->first();
            if ($existing) return $existing;

            $cashAccount = ChartOfAccount::where('code', '1010')->first();
            $arAccount = ChartOfAccount::where('code', '1100')->first();
            $salesAccount = ChartOfAccount::where('code', '4000')->first();
            $vatAccount = ChartOfAccount::where('code', '2100')->first();
            $inventoryAccount = ChartOfAccount::where('code', '1200')->first();
            $cogsAccount = ChartOfAccount::where('code', '5000')->first();

            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'entry_date' => $sale->sale_date,
                'description' => 'Sale ' . $sale->invoice_no,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'status' => 'posted',
                'created_by' => $sale->created_by,
            ]);

            // Debit: Cash or AR (full amount)
            $debitAccount = ($sale->payment_method === 'cash') ? $cashAccount : $arAccount;
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'debit' => $sale->total,
                'credit' => 0,
                'description' => 'Sale ' . $sale->invoice_no,
            ]);

            // Credit: Sales Revenue (subtotal - discount)
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $salesAccount->id,
                'debit' => 0,
                'credit' => $sale->subtotal - $sale->discount,
                'description' => 'Sales revenue',
            ]);

            // Credit: VAT Payable (tax)
            if ($sale->tax > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $vatAccount->id,
                    'debit' => 0,
                    'credit' => $sale->tax,
                    'description' => 'VAT (PPN)',
                ]);
            }

            // Debit: COGS, Credit: Inventory
            $cogs = 0;
            foreach ($sale->items as $item) {
                $cogs += $item->quantity * ($item->product->buy_price ?? 0);
            }
            if ($cogs > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $cogsAccount->id,
                    'debit' => $cogs,
                    'credit' => 0,
                    'description' => 'COGS for ' . $sale->invoice_no,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $inventoryAccount->id,
                    'debit' => 0,
                    'credit' => $cogs,
                    'description' => 'Inventory released',
                ]);
            }

            return $journal;
        });
    }

    public function postPurchaseJournal(PurchaseOrder $po): JournalEntry
    {
        return DB::transaction(function () use ($po) {
            $journalNo = 'JE-PO-' . $po->id . '-' . date('Ymd');
            $existing = JournalEntry::where('reference_type', 'purchase_order')->where('reference_id', $po->id)->first();
            if ($existing) return $existing;

            $apAccount = ChartOfAccount::where('code', '2000')->first();
            $cashAccount = ChartOfAccount::where('code', '1010')->first();
            $inventoryAccount = ChartOfAccount::where('code', '1200')->first();
            $vatAccount = ChartOfAccount::where('code', '2100')->first();

            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'entry_date' => $po->po_date,
                'description' => 'Purchase Order ' . $po->po_number,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'status' => 'posted',
                'created_by' => $po->created_by,
            ]);

            // Debit: Inventory
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $inventoryAccount->id,
                'debit' => $po->subtotal - $po->discount,
                'credit' => 0,
                'description' => 'Inventory from PO ' . $po->po_number,
            ]);

            // Debit: VAT (if any)
            if ($po->tax > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $vatAccount->id,
                    'debit' => $po->tax,
                    'credit' => 0,
                    'description' => 'VAT Input',
                ]);
            }

            // Credit: AP or Cash
            $creditAccount = ($po->payment_status === 'paid') ? $cashAccount : $apAccount;
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $po->total,
                'description' => 'Payment for PO ' . $po->po_number,
            ]);

            return $journal;
        });
    }

    public function postPaymentJournal(string $type, int $referenceId, float $amount, string $method, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($type, $referenceId, $amount, $method, $userId) {
            $cashAccount = ChartOfAccount::where('code', $method === 'cash' ? '1010' : '1020')->first();
            $arAccount = ChartOfAccount::where('code', '1100')->first();
            $apAccount = ChartOfAccount::where('code', '2000')->first();

            $journalNo = 'JE-PAY-' . $type . '-' . $referenceId . '-' . date('YmdHis');
            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'entry_date' => date('Y-m-d'),
                'description' => ucfirst($type) . ' payment #' . $referenceId,
                'reference_type' => $type . '_payment',
                'reference_id' => $referenceId,
                'status' => 'posted',
                'created_by' => $userId,
            ]);

            if ($type === 'sale') {
                // Debit Cash, Credit AR
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $cashAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Payment received']);
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $arAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'AR settled']);
            } else {
                // Debit AP, Credit Cash
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $apAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'AP settled']);
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Payment made']);
            }

            return $journal;
        });
    }

    public function getTrialBalance(string $dateFrom, string $dateTo): array
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $balances = [];

        foreach ($accounts as $account) {
            $debit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('debit');
            $credit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('credit');

            $netDebit = $debit - $credit;
            $netCredit = $credit - $debit;

            if ($debit != 0 || $credit != 0) {
                $balances[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'debit' => $netDebit > 0 ? abs($netDebit) : 0,
                    'credit' => $netCredit > 0 ? abs($netCredit) : 0,
                ];
            }
        }

        $totalDebit = array_sum(array_column($balances, 'debit'));
        $totalCredit = array_sum(array_column($balances, 'credit'));

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'accounts' => $balances,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    public function getBalanceSheet(string $asOfDate): array
    {
        $accounts = ChartOfAccount::where('is_active', true)->get();
        $assets = ['current' => [], 'fixed' => [], 'total' => 0];
        $liabilities = ['current' => [], 'long_term' => [], 'total' => 0];
        $equity = ['items' => [], 'total' => 0];

        foreach ($accounts as $account) {
            $debit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('status', 'posted')->where('entry_date', '<=', $asOfDate);
                })->sum('debit');
            $credit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate) {
                    $q->where('status', 'posted')->where('entry_date', '<=', $asOfDate);
                })->sum('credit');

            $balance = $debit - $credit;

            if ($account->type === 'asset') {
                if ($account->subtype === 'fixed_asset') {
                    $assets['fixed'][] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                } else {
                    $assets['current'][] = ['code' => $account->code, 'name' => $account->name, 'balance' => $balance];
                }
                $assets['total'] += $balance;
            } elseif ($account->type === 'liability') {
                if ($account->subtype === 'long_term_liability') {
                    $liabilities['long_term'][] = ['code' => $account->code, 'name' => $account->name, 'balance' => $credit - $debit];
                } else {
                    $liabilities['current'][] = ['code' => $account->code, 'name' => $account->name, 'balance' => $credit - $debit];
                }
                $liabilities['total'] += ($credit - $debit);
            } elseif ($account->type === 'equity') {
                $equity['items'][] = ['code' => $account->code, 'name' => $account->name, 'balance' => $credit - $debit];
                $equity['total'] += ($credit - $debit);
            }
        }

        return [
            'as_of_date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_liabilities_equity' => $liabilities['total'] + $equity['total'],
            'is_balanced' => abs($assets['total'] - ($liabilities['total'] + $equity['total'])) < 0.01,
        ];
    }

    public function getIncomeStatement(string $dateFrom, string $dateTo): array
    {
        $revenueAccounts = ChartOfAccount::where('type', 'revenue')->where('is_active', true)->get();
        $expenseAccounts = ChartOfAccount::where('type', 'expense')->where('is_active', true)->get();

        $revenues = [];
        $totalRevenue = 0;
        foreach ($revenueAccounts as $account) {
            $credit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('credit');
            $debit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('debit');
            $balance = $credit - $debit;
            if ($balance != 0) {
                $revenues[] = ['code' => $account->code, 'name' => $account->name, 'amount' => $balance];
                $totalRevenue += $balance;
            }
        }

        $expenses = [];
        $totalExpense = 0;
        foreach ($expenseAccounts as $account) {
            $debit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('debit');
            $credit = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                    $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
                })->sum('credit');
            $balance = $debit - $credit;
            if ($balance != 0) {
                $expenses[] = ['code' => $account->code, 'name' => $account->name, 'amount' => $balance];
                $totalExpense += $balance;
            }
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'revenues' => $revenues,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expense' => $totalExpense,
            'net_income' => $totalRevenue - $totalExpense,
        ];
    }

    public function getGeneralLedger(string $dateFrom, string $dateTo, ?int $accountId = null): array
    {
        $query = JournalEntryLine::with(['journalEntry', 'account'])
            ->whereHas('journalEntry', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted')->whereBetween('entry_date', [$dateFrom, $dateTo]);
            });
        if ($accountId) $query->where('account_id', $accountId);

        $lines = $query->orderBy('journal_entry_id')->get();
        $ledger = [];
        $runningBalance = 0;

        foreach ($lines as $line) {
            $runningBalance += $line->debit - $line->credit;
            $ledger[] = [
                'date' => $line->journalEntry->entry_date,
                'journal_no' => $line->journalEntry->journal_no,
                'description' => $line->journalEntry->description,
                'account_code' => $line->account->code,
                'account_name' => $line->account->name,
                'debit' => (float)$line->debit,
                'credit' => (float)$line->credit,
                'balance' => $runningBalance,
            ];
        }

        return $ledger;
    }
}
