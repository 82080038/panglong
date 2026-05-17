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

        $report = $this->reportService->getDailySales($date);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function monthlySales(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        $report = $this->reportService->getMonthlySales($year, $month);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function lowStock()
    {
        $report = $this->reportService->getLowStockReport();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function arAging()
    {
        $report = $this->reportService->getReceivableAging();

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
