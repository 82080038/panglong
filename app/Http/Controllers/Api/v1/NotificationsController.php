<?php
namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Sale;
use App\Models\AccountReceivable;
use App\Models\AccountPayable;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function sendInvoice(Request $request, $saleId)
    {
        $sale = Sale::with(['customer', 'items.product'])->findOrFail($saleId);
        $this->notificationService->sendInvoiceEmail($sale);
        return response()->json(['success' => true, 'message' => 'Invoice email sent']);
    }

    public function sendPaymentReceipt(Request $request, $saleId)
    {
        $sale = Sale::with('customer')->findOrFail($saleId);
        $amount = (float)$request->input('amount', $sale->total);
        $this->notificationService->sendPaymentReceiptEmail($sale, $amount);
        return response()->json(['success' => true, 'message' => 'Payment receipt email sent']);
    }

    public function sendARDueReminders()
    {
        $overdue = AccountReceivable::where('balance', '>', 0)
            ->where('due_date', '<', now())
            ->with('customer')
            ->get();

        $sent = 0;
        foreach ($overdue as $ar) {
            $this->notificationService->sendARDueReminder($ar);
            $this->notificationService->sendARDueSMS($ar);
            $sent++;
        }

        return response()->json(['success' => true, 'message' => "Sent {$sent} AR due reminders"]);
    }

    public function sendAPDueReminders()
    {
        $overdue = AccountPayable::where('balance', '>', 0)
            ->where('due_date', '<', now())
            ->with('supplier')
            ->get();

        $sent = 0;
        foreach ($overdue as $ap) {
            $this->notificationService->sendAPDueReminder($ap);
            $sent++;
        }

        return response()->json(['success' => true, 'message' => "Sent {$sent} AP due reminders"]);
    }
}
