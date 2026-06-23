<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\CashManagementService;
use App\Models\CashTransaction;
use App\Models\BankStatement;
use Illuminate\Http\Request;

class CashManagementController extends Controller
{
    private CashManagementService $cashService;

    public function __construct(CashManagementService $cashService)
    {
        $this->cashService = $cashService;
    }

    public function balances(Request $request)
    {
        $asOf = $request->input('as_of', date('Y-m-d'));
        $balances = $this->cashService->getAllBalances($asOf);

        return response()->json(['success' => true, 'data' => $balances]);
    }

    public function cashFlowSummary(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $summary = $this->cashService->getCashFlowSummary($validated['date_from'], $validated['date_to']);

        return response()->json(['success' => true, 'data' => $summary]);
    }

    public function transactions(Request $request)
    {
        $query = CashTransaction::with(['branch', 'employee', 'journalEntry']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function storeTransaction(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cash_in,cash_out,petty_cash,cash_transfer,setoran,withdrawal',
            'account_type' => 'required|in:kas_tunai,kas_kecil,bank_bca,bank_mandiri,bank_bni',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'category' => 'nullable|in:operasional,gaji,perlengkapan,sewa,listrik,pajak,lainnya,setoran_bank,tarik_tunai',
            'branch_id' => 'nullable|exists:branches,id',
            'employee_id' => 'nullable|exists:employees,id',
            'reference_no' => 'nullable|string',
            'recipient' => 'nullable|string',
        ]);

        $tx = $this->cashService->createTransaction($validated, auth()->id());

        return response()->json(['success' => true, 'data' => $tx->load(['branch', 'employee']), 'message' => 'Cash transaction created'], 201);
    }

    public function bankStatements(Request $request)
    {
        $query = BankStatement::query();

        if ($request->has('bank_account')) {
            $query->where('bank_account', $request->bank_account);
        }
        if ($request->has('reconciliation_status')) {
            $query->where('reconciliation_status', $request->reconciliation_status);
        }
        if ($request->has('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $statements = $query->orderBy('transaction_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $statements->items(),
            'meta' => [
                'current_page' => $statements->currentPage(),
                'per_page' => $statements->perPage(),
                'total' => $statements->total(),
                'last_page' => $statements->lastPage(),
            ],
        ]);
    }

    public function importBankStatements(Request $request)
    {
        $validated = $request->validate([
            'bank_account' => 'required|in:bank_bca,bank_mandiri,bank_bni',
            'statements' => 'required|array|min:1',
            'statements.*.transaction_date' => 'required|date',
            'statements.*.description' => 'required|string',
            'statements.*.debit' => 'nullable|numeric|min:0',
            'statements.*.credit' => 'nullable|numeric|min:0',
            'statements.*.balance' => 'nullable|numeric',
            'statements.*.reference_no' => 'nullable|string',
        ]);

        $imported = 0;
        foreach ($validated['statements'] as $stmt) {
            BankStatement::create([
                'bank_account' => $validated['bank_account'],
                'transaction_date' => $stmt['transaction_date'],
                'description' => $stmt['description'],
                'debit' => $stmt['debit'] ?? 0,
                'credit' => $stmt['credit'] ?? 0,
                'balance' => $stmt['balance'] ?? 0,
                'reference_no' => $stmt['reference_no'] ?? null,
                'reconciliation_status' => 'unreconciled',
            ]);
            $imported++;
        }

        return response()->json(['success' => true, 'message' => "{$imported} statements imported", 'imported' => $imported], 201);
    }

    public function reconcileBankStatement(Request $request, $id)
    {
        $validated = $request->validate([
            'cash_transaction_id' => 'nullable|exists:cash_transactions,id',
            'action' => 'required|in:reconcile,ignore',
        ]);

        $stmt = BankStatement::findOrFail($id);

        if ($validated['action'] === 'reconcile') {
            $stmt->update([
                'reconciliation_status' => 'reconciled',
                'cash_transaction_id' => $validated['cash_transaction_id'] ?? null,
                'reconciled_at' => now()->toDateString(),
                'reconciled_by' => auth()->id(),
            ]);
        } else {
            $stmt->update([
                'reconciliation_status' => 'ignored',
                'reconciled_at' => now()->toDateString(),
                'reconciled_by' => auth()->id(),
            ]);
        }

        return response()->json(['success' => true, 'data' => $stmt, 'message' => 'Bank statement ' . $validated['action'] . 'd']);
    }
}
