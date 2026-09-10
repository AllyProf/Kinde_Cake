<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BusinessTrendsService
{
    public function __construct(
        private DayReportService $dayReport,
        private DayCloseService $dayClose,
    ) {}

    /** @return array{report: array<string, mixed>, charts: array<string, mixed>} */
    public function buildTrends(string $period, Carbon $anchor): array
    {
        $report = $this->dayReport->buildReport($period, $anchor);
        $range = $this->dayReport->resolvePeriodRange($period, $anchor);
        $summary = $report['summary'] ?? [];

        $charts = [
            'daily_trend' => $this->buildDailyTrendSeries($period, $range['start'], $range['end']),
            'payment_methods' => $this->buildPaymentMethodChart($summary),
            'expense_categories' => $this->buildExpenseCategoryChart($summary),
            'top_items' => $this->buildTopItemsChart($range['start'], $range['end']),
            'sales_status' => $this->buildSalesStatusChart($summary),
        ];

        return [
            'report' => $report,
            'charts' => $charts,
        ];
    }

    /** @return array{labels: list<string>, collected: list<float>, expenses: list<float>, net: list<float>, sales_total: list<float>} */
    private function buildDailyTrendSeries(string $period, Carbon $start, Carbon $end): array
    {
        $labels = [];
        $collected = [];
        $expenses = [];
        $net = [];
        $salesTotal = [];

        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $daySummary = $this->dayClose->buildSummary($cursor);
            $collectedAmount = (float) ($daySummary['total_collected'] ?? 0);
            $expensesAmount = (float) ($daySummary['expenses_total'] ?? 0);

            $labels[] = $period === DayReportService::PERIOD_MONTH
                ? $cursor->format('d M')
                : $cursor->format('D d M');

            $collected[] = round($collectedAmount, 2);
            $expenses[] = round($expensesAmount, 2);
            $net[] = round($collectedAmount - $expensesAmount, 2);
            $salesTotal[] = round((float) ($daySummary['sales_total'] ?? 0), 2);

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'collected' => $collected,
            'expenses' => $expenses,
            'net' => $net,
            'sales_total' => $salesTotal,
        ];
    }

    /** @param  array<string, mixed>  $summary
     * @return list<array{label: string, value: float, color: string, highlight: string}>
     */
    private function buildPaymentMethodChart(array $summary): array
    {
        $segments = [
            [
                'label' => 'Cash',
                'value' => (float) ($summary['cash_collected'] ?? 0),
                'color' => '#28a745',
                'highlight' => '#34ce57',
            ],
            [
                'label' => 'Mobile money',
                'value' => (float) ($summary['mobile_collected'] ?? 0),
                'color' => '#007bff',
                'highlight' => '#3395ff',
            ],
            [
                'label' => 'Bank',
                'value' => (float) ($summary['bank_collected'] ?? 0),
                'color' => '#6f42c1',
                'highlight' => '#895bd4',
            ],
            [
                'label' => 'Credit',
                'value' => (float) ($summary['credit_recorded'] ?? 0),
                'color' => '#fd7e14',
                'highlight' => '#ff922b',
            ],
        ];

        return array_values(array_filter(
            $segments,
            fn (array $segment) => $segment['value'] > 0,
        ));
    }

    /** @param  array<string, mixed>  $summary
     * @return list<array{label: string, value: float, color: string, highlight: string}>
     */
    private function buildExpenseCategoryChart(array $summary): array
    {
        $palette = [
            '#dc3545',
            '#fd7e14',
            '#ffc107',
            '#20c997',
            '#6c757d',
        ];

        $categories = collect($summary['expenses_by_category'] ?? []);

        return $categories
            ->values()
            ->map(function (array $row, int $index) use ($palette) {
                $color = $palette[$index % count($palette)];

                return [
                    'label' => $row['label'] ?? ucfirst($row['category'] ?? 'Other'),
                    'value' => (float) ($row['amount'] ?? 0),
                    'color' => $color,
                    'highlight' => $color,
                ];
            })
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values()
            ->all();
    }

    /** @return array{labels: list<string>, revenue: list<float>, quantity: list<float>} */
    private function buildTopItemsChart(Carbon $start, Carbon $end): array
    {
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $rows = SaleItem::query()
            ->selectRaw('item_id, SUM(quantity) as quantity_sold, SUM(line_total) as revenue')
            ->whereHas('sale', fn ($query) => $query
                ->realSales()
                ->whereBetween('sold_at', [$startDate, $endDate])
                ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->groupBy('item_id')
            ->with('item:id,name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->map(fn ($row) => $row->item?->name ?? 'Unknown item')->all(),
            'revenue' => $rows->map(fn ($row) => round((float) $row->revenue, 2))->all(),
            'quantity' => $rows->map(fn ($row) => round((float) $row->quantity_sold, 2))->all(),
        ];
    }

    /** @param  array<string, mixed>  $summary
     * @return list<array{label: string, value: float, color: string, highlight: string}>
     */
    private function buildSalesStatusChart(array $summary): array
    {
        $paid = (int) ($summary['paid_sales_count'] ?? 0);
        $pending = (int) ($summary['pending_sales_count'] ?? 0);

        $segments = [];

        if ($paid > 0) {
            $segments[] = [
                'label' => 'Paid sales',
                'value' => $paid,
                'color' => '#28a745',
                'highlight' => '#34ce57',
            ];
        }

        if ($pending > 0) {
            $segments[] = [
                'label' => 'Unpaid / partial',
                'value' => $pending,
                'color' => '#ffc107',
                'highlight' => '#ffcd39',
            ];
        }

        return $segments;
    }

    /** @return Collection<int, array{label: string, amount: float}> */
    public function providerBreakdown(array $summary, string $type): Collection
    {
        $key = $type === 'mobile' ? 'mobile_providers' : 'bank_providers';

        return collect($summary[$key] ?? [])
            ->map(fn (array $row) => [
                'label' => $row['name'],
                'amount' => (float) ($row['amount'] ?? 0),
            ]);
    }
}
