<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\AccountingService;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(private AccountingService $accountingService)
    {
    }

    public function chartOfAccounts()
    {
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function journalEntries(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'creator']);
        if ($request->has('date_from')) $query->where('entry_date', '>=', $request->date_from);
        if ($request->has('date_to')) $query->where('entry_date', '<=', $request->date_to);
        $entries = $query->orderBy('entry_date', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $entries]);
    }

    public function trialBalance(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->accountingService->getTrialBalance($dateFrom, $dateTo)]);
    }

    public function balanceSheet(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->accountingService->getBalanceSheet($asOfDate)]);
    }

    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->accountingService->getIncomeStatement($dateFrom, $dateTo)]);
    }

    public function generalLedger(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        $accountId = $request->input('account_id');
        return response()->json(['success' => true, 'data' => $this->accountingService->getGeneralLedger($dateFrom, $dateTo, $accountId)]);
    }

    public function postManualJournal(Request $request)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:chart_of_accounts,id',
            'lines.*.debit' => 'numeric|min:0',
            'lines.*.credit' => 'numeric|min:0',
        ]);

        $totalDebit = array_sum(array_column($validated['lines'], 'debit'));
        $totalCredit = array_sum(array_column($validated['lines'], 'credit'));
        if (abs($totalDebit - $totalCredit) > 0.01) {
            return response()->json(['success' => false, 'message' => 'Debit and Credit must balance'], 422);
        }

        $journal = JournalEntry::create([
            'journal_no' => 'JE-MAN-' . date('YmdHis'),
            'entry_date' => $validated['entry_date'],
            'description' => $validated['description'],
            'reference_type' => 'manual',
            'status' => 'posted',
            'created_by' => $request->user()->id,
        ]);

        foreach ($validated['lines'] as $line) {
            \App\Models\JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'description' => $line['description'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'data' => $journal->load('lines.account')], 201);
    }
}
