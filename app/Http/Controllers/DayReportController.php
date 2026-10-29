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
                DayReportService::PERIOD_CUSTOM,
            ])],
            'type' => ['nullable', Rule::in(array_keys(DayReportService::reportTypeOptions()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $period = $validated['period'] ?? DayReportService::PERIOD_DAY;
        $reportType = $validated['type'] ?? DayReportService::TYPE_SUMMARY;

        $from = null;
        $to = null;

        if (! empty($validated['from'])) {
            $from = Carbon::parse($validated['from'])->startOfDay();
        } elseif ($period === DayReportService::PERIOD_MONTH && ! empty($validated['month'])) {
            $from = Carbon::createFromFormat('Y-m', $validated['month'])->startOfMonth();
        } elseif (! empty($validated['date'])) {
            $from = Carbon::parse($validated['date'])->startOfDay();
        }

        if (! empty($validated['to'])) {
            $to = Carbon::parse($validated['to'])->startOfDay();
        }

        $selectedDate = ($from ?? now())->copy()->startOfDay();
        if ($selectedDate->gt(now())) {
            $selectedDate = now()->startOfDay();
            $from = $selectedDate->copy();
        }

        if ($period !== DayReportService::PERIOD_CUSTOM) {
            $preset = $this->dayReport->resolvePeriodRange($period, $selectedDate);
            $from = $preset['start']->copy();
            $to = $preset['end']->copy();
        } else {
            $from ??= $selectedDate->copy();
            $to ??= $from->copy();
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $today = now()->startOfDay();
        if ($from->gt($today)) {
            $from = $today->copy();
        }
        if ($to->gt($today)) {
            $to = $today->copy();
        }

        $rangePeriod = $period === DayReportService::PERIOD_CUSTOM
            ? DayReportService::PERIOD_CUSTOM
            : $period;

        $report = $this->dayReport->buildReport($rangePeriod, $from, $from, $to);
        $recentDays = $this->dayReport->recentClosedDays();
        $range = $this->dayReport->resolvePeriodRange($rangePeriod, $from, $from, $to);

        if ($reportType === DayReportService::TYPE_SALES) {
            $sales = $this->dayReport->salesForRange($range['start'], $range['end']);
        } else {
            $sales = collect();
        }

        if ($reportType === DayReportService::TYPE_STAFF && ! $range['start']->equalTo($range['end'])) {
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
            'from',
            'to',
        ));
    }
}
