<?php

namespace App\Http\Controllers;

use App\Services\BusinessTrendsService;
use App\Services\DayReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BusinessTrendsController extends Controller
{
    public function __construct(
        private BusinessTrendsService $trends,
        private DayReportService $dayReport,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in([
                DayReportService::PERIOD_DAY,
                DayReportService::PERIOD_WEEK,
                DayReportService::PERIOD_MONTH,
            ])],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $period = $validated['period'] ?? DayReportService::PERIOD_WEEK;

        if ($period === DayReportService::PERIOD_MONTH && ! empty($validated['month'])) {
            $selectedDate = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        } elseif (! empty($validated['date'])) {
            $selectedDate = Carbon::parse($validated['date'])->startOfDay();
        } else {
            $selectedDate = now()->startOfDay();
        }

        if ($selectedDate->gt(now())) {
            $selectedDate = now()->startOfDay();
        }

        $payload = $this->trends->buildTrends($period, $selectedDate);
        $range = $this->dayReport->resolvePeriodRange($period, $selectedDate);

        return view('business-trends.index', [
            'selectedDate' => $selectedDate,
            'period' => $period,
            'report' => $payload['report'],
            'charts' => $payload['charts'],
            'range' => $range,
        ]);
    }
}
