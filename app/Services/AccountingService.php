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
            $bankAccount = ChartOfAccount::where('code', '1020')->first();
            $arAccount = ChartOfAccount::where('code', '1100')->first();
            $salesAccount = ChartOfAccount::where('code', '4000')->first();
            $vatOutAccount = ChartOfAccount::where('code', '2100')->first(); // PPN Keluaran
            $salesDiscountAccount = ChartOfAccount::where('code', '4200')->first(); // Potongan Penjualan
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

            // Debit: Cash/Bank or AR (full amount)
            $debitAccount = $arAccount;
            if ($sale->payment_method === 'cash') {
                $debitAccount = $cashAccount;
            } elseif ($sale->payment_method === 'transfer') {
                $debitAccount = $bankAccount;
            }
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $debitAccount->id,
                'debit' => $sale->total,
                'credit' => 0,
                'description' => 'Penjualan ' . $sale->invoice_no,
            ]);

            // Credit: Sales Revenue (subtotal)
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $salesAccount->id,
                'debit' => 0,
                'credit' => $sale->subtotal,
                'description' => 'Pendapatan Penjualan',
            ]);

            // Debit: Potongan Penjualan (if discount > 0)
            if ($sale->discount > 0 && $salesDiscountAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $salesDiscountAccount->id,
                    'debit' => $sale->discount,
                    'credit' => 0,
                    'description' => 'Potongan Penjualan',
                ]);
            }

            // Credit: PPN Keluaran (tax)
            if ($sale->tax > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $vatOutAccount->id,
                    'debit' => 0,
                    'credit' => $sale->tax,
                    'description' => 'PPN Keluaran',
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
                    'description' => 'HPP untuk ' . $sale->invoice_no,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $inventoryAccount->id,
                    'debit' => 0,
                    'credit' => $cogs,
                    'description' => 'Persediaan dikeluarkan',
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

            $apAccount = ChartOfAccount::where('code', '2000')->first(); // Hutang Usaha
            $cashAccount = ChartOfAccount::where('code', '1010')->first(); // Kas Tunai
            $bankAccount = ChartOfAccount::where('code', '1020')->first(); // Bank BCA
            $inventoryAccount = ChartOfAccount::where('code', '1200')->first(); // Persediaan
            $vatInAccount = ChartOfAccount::where('code', '1300')->first(); // PPN Masukan

            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'entry_date' => $po->po_date,
                'description' => 'Purchase Order ' . $po->po_number,
                'reference_type' => 'purchase_order',
                'reference_id' => $po->id,
                'status' => 'posted',
                'created_by' => $po->created_by,
            ]);

            // Debit: Persediaan
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $inventoryAccount->id,
                'debit' => $po->subtotal - $po->discount,
                'credit' => 0,
                'description' => 'Persediaan dari PO ' . $po->po_number,
            ]);

            // Debit: PPN Masukan (if any)
            if ($po->tax > 0 && $vatInAccount) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $vatInAccount->id,
                    'debit' => $po->tax,
                    'credit' => 0,
                    'description' => 'PPN Masukan',
                ]);
            }

            // Credit: Hutang Usaha or Kas/Bank
            $creditAccount = $apAccount;
            if ($po->payment_status === 'paid') {
                $creditAccount = $cashAccount;
            }
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $creditAccount->id,
                'debit' => 0,
                'credit' => $po->total,
                'description' => 'Pembayaran PO ' . $po->po_number,
            ]);

            return $journal;
        });
    }

    public function postPaymentJournal(string $type, int $referenceId, float $amount, string $method, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($type, $referenceId, $amount, $method, $userId) {
            $cashAccount = ChartOfAccount::where('code', '1010')->first(); // Kas Tunai
            $bankAccount = ChartOfAccount::where('code', '1020')->first(); // Bank BCA
            $arAccount = ChartOfAccount::where('code', '1100')->first(); // Piutang Usaha
            $apAccount = ChartOfAccount::where('code', '2000')->first(); // Hutang Usaha
            $paymentAccount = ($method === 'cash') ? $cashAccount : $bankAccount;

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
                // Debit Kas/Bank, Credit Piutang Usaha
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $paymentAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Penerimaan piutang']);
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $arAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Pelunasan piutang']);
            } else {
                // Debit Hutang Usaha, Credit Kas/Bank
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $apAccount->id, 'debit' => $amount, 'credit' => 0, 'description' => 'Pelunasan hutang']);
                JournalEntryLine::create(['journal_entry_id' => $journal->id, 'account_id' => $paymentAccount->id, 'debit' => 0, 'credit' => $amount, 'description' => 'Pembayaran hutang']);
            }

            return $journal;
        });
    }

    public function voidSaleJournal(Sale $sale, string $reason, int $userId): ?JournalEntry
    {
        return DB::transaction(function () use ($sale, $reason, $userId) {
            $original = JournalEntry::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->where('status', 'posted')
                ->first();

            if (!$original) return null;

            $original->update(['status' => 'reversed']);

            $reversal = JournalEntry::create([
                'journal_no' => 'JE-VOID-SALE-' . $sale->id . '-' . date('YmdHis'),
                'entry_date' => date('Y-m-d'),
                'description' => 'Pembatalan penjualan ' . $sale->invoice_no . ' (' . $reason . ')',
                'reference_type' => 'sale_void',
                'reference_id' => $sale->id,
                'status' => 'posted',
                'created_by' => $userId,
            ]);

            foreach ($original->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'description' => 'Reversal: ' . ($line->description ?? ''),
                ]);
            }

            return $reversal;
        });
    }

    public function postSalesReturnJournal($salesReturn, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($salesReturn, $userId) {
            $cashAccount = ChartOfAccount::where('code', '1010')->first();
            $bankAccount = ChartOfAccount::where('code', '1020')->first();
            $arAccount = ChartOfAccount::where('code', '1100')->first();
            $salesAccount = ChartOfAccount::where('code', '4000')->first();
            $vatOutAccount = ChartOfAccount::where('code', '2100')->first();
            $inventoryAccount = ChartOfAccount::where('code', '1200')->first();
            $cogsAccount = ChartOfAccount::where('code', '5000')->first();

            $sale = $salesReturn->sale;

            $journal = JournalEntry::create([
                'journal_no' => 'JE-SR-' . $salesReturn->id . '-' . date('Ymd'),
                'entry_date' => $salesReturn->return_date,
                'description' => 'Retur penjualan ' . $salesReturn->return_no,
                'reference_type' => 'sales_return',
                'reference_id' => $salesReturn->id,
                'status' => 'posted',
                'created_by' => $userId,
            ]);

            $refundAccount = $arAccount;
            if ($salesReturn->refund_method === 'cash') {
                $refundAccount = $cashAccount;
            } elseif ($salesReturn->refund_method === 'transfer') {
                $refundAccount = $bankAccount;
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $salesAccount->id,
                'debit' => $salesReturn->total_refund / (1 + ($sale->tax / max(1, $sale->subtotal - $sale->discount))),
                'credit' => 0,
                'description' => 'Pengembalian pendapatan',
            ]);

            if ($sale->tax > 0 && $vatOutAccount) {
                $taxPortion = $salesReturn->total_refund - ($salesReturn->total_refund / (1 + ($sale->tax / max(1, $sale->subtotal - $sale->discount))));
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'account_id' => $vatOutAccount->id,
                    'debit' => $taxPortion,
                    'credit' => 0,
                    'description' => 'Pengembalian PPN Keluaran',
                ]);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $refundAccount->id,
                'debit' => 0,
                'credit' => $salesReturn->total_refund,
                'description' => 'Pengembalian ke customer',
            ]);

            if ($inventoryAccount && $cogsAccount) {
                $cogs = 0;
                foreach ($salesReturn->items as $item) {
                    $product = $item->product;
                    if ($product) {
                        $cogs += $item->quantity * $product->buy_price;
                    }
                }
                if ($cogs > 0) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $inventoryAccount->id,
                        'debit' => $cogs,
                        'credit' => 0,
                        'description' => 'Persediaan dikembalikan',
                    ]);
                    JournalEntryLine::create([
                        'journal_entry_id' => $journal->id,
                        'account_id' => $cogsAccount->id,
                        'debit' => 0,
                        'credit' => $cogs,
                        'description' => 'Reversal HPP',
                    ]);
                }
            }

            return $journal;
        });
    }

    public function postPurchaseReturnJournal($purchaseReturn, int $userId): JournalEntry
    {
        return DB::transaction(function () use ($purchaseReturn, $userId) {
            $apAccount = ChartOfAccount::where('code', '2000')->first();
            $cashAccount = ChartOfAccount::where('code', '1010')->first();
            $bankAccount = ChartOfAccount::where('code', '1020')->first();
            $inventoryAccount = ChartOfAccount::where('code', '1200')->first();
            $vatInAccount = ChartOfAccount::where('code', '1300')->first();

            $po = $purchaseReturn->purchaseOrder;

            $journal = JournalEntry::create([
                'journal_no' => 'JE-PR-' . $purchaseReturn->id . '-' . date('Ymd'),
                'entry_date' => $purchaseReturn->return_date,
                'description' => 'Retur pembelian ' . $purchaseReturn->return_no,
                'reference_type' => 'purchase_return',
                'reference_id' => $purchaseReturn->id,
                'status' => 'posted',
                'created_by' => $userId,
            ]);

            $refundAccount = $apAccount;
            if ($purchaseReturn->refund_method === 'cash') {
                $refundAccount = $cashAccount;
            } elseif ($purchaseReturn->refund_method === 'transfer') {
                $refundAccount = $bankAccount;
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $refundAccount->id,
                'debit' => $purchaseReturn->total_refund,
                'credit' => 0,
                'description' => 'Pengembalian dari supplier',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $inventoryAccount->id,
                'debit' => 0,
                'credit' => $purchaseReturn->total_refund,
                'description' => 'Persediaan dikembalikan ke supplier',
            ]);

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
