<?php

namespace App\Http\Controllers;

use App\Services\DayReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DayReportController extends Controller
{
    public function __construct(private DayReportService $dayReport) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in([
                DayReportService::PERIOD_DAY,
                DayReportService::PERIOD_WEEK,
                DayReportService::PERIOD_MONTH,
            ])],
            'type' => ['nullable', Rule::in(array_keys(DayReportService::reportTypeOptions()))],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $period = $validated['period'] ?? DayReportService::PERIOD_DAY;
        $reportType = $validated['type'] ?? DayReportService::TYPE_SUMMARY;

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

        $report = $this->dayReport->buildReport($period, $selectedDate);
        $recentDays = $this->dayReport->recentClosedDays();
        $range = $this->dayReport->resolvePeriodRange($period, $selectedDate);

        if ($reportType === DayReportService::TYPE_SALES) {
            $sales = $this->dayReport->salesForRange($range['start'], $range['end']);
        } else {
            $sales = collect();
        }

        if ($reportType === DayReportService::TYPE_STAFF && $period !== DayReportService::PERIOD_DAY) {
            $report['staff_closes'] = $this->dayReport->staffClosesForRange($range['start'], $range['end']);
        }

        $hasData = $this->dayReport->hasDataForType($report, $reportType);
        $reportTypes = DayReportService::reportTypeOptions();

        return view('day-reports.index', compact(
            'selectedDate',
            'period',
            'reportType',
            'reportTypes',
            'report',
            'recentDays',
            'range',
            'sales',
            'hasData',
        ));
    }
}
