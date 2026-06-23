<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function dailySales(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->reportService->getDailySales($date)]);
    }

    public function monthlySales(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        return response()->json(['success' => true, 'data' => $this->reportService->getMonthlySales($year, $month)]);
    }

    public function lowStock()
    {
        return response()->json(['success' => true, 'data' => $this->reportService->getLowStockReport()]);
    }

    public function arAging()
    {
        return response()->json(['success' => true, 'data' => $this->reportService->getReceivableAging()]);
    }

    public function apAging()
    {
        return response()->json(['success' => true, 'data' => $this->reportService->getPayableAging()]);
    }

    public function salesByProduct(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->reportService->getSalesByProduct($dateFrom, $dateTo)]);
    }

    public function salesByCustomer(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->reportService->getSalesByCustomer($dateFrom, $dateTo)]);
    }

    public function profitLoss(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->reportService->getProfitLoss($dateFrom, $dateTo)]);
    }

    public function stockMovement(Request $request)
    {
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));
        return response()->json(['success' => true, 'data' => $this->reportService->getStockMovementReport($dateFrom, $dateTo)]);
    }

    public function deadStock(Request $request)
    {
        $days = (int) $request->input('days', 90);
        return response()->json(['success' => true, 'data' => $this->reportService->getDeadStockReport($days)]);
    }
}
