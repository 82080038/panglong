<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\BankService;
use Illuminate\Http\Request;

class BankController extends Controller
{
    public function __construct(private BankService $bankService)
    {
    }

    public function verifyPayment(Request $request)
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|string|in:sale,purchase',
        ]);

        $result = $this->bankService->reconcilePayment(
            $validated['transaction_id'],
            $validated['amount'],
            $validated['type']
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function statements(Request $request)
    {
        $fromDate = $request->input('from_date', date('Y-m-01'));
        $toDate = $request->input('to_date', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->bankService->getBankStatements($fromDate, $toDate)]);
    }
}
