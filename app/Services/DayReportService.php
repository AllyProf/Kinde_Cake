<?php

namespace App\Services;

use App\Models\DayClose;
use App\Models\DayExpense;
use App\Models\IngredientReceiving;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StaffDayClose;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DayReportService
{
    public const PERIOD_DAY = 'day';

    public const PERIOD_WEEK = 'week';

    public const PERIOD_MONTH = 'month';

    public const TYPE_SUMMARY = 'summary';

    public const TYPE_COLLECTIONS = 'collections';

    public const TYPE_SALES = 'sales';

    public const TYPE_EXPENSES = 'expenses';

    public const TYPE_PURCHASES = 'purchases';

    public const TYPE_STAFF = 'staff';

    public function __construct(private DayCloseService $dayClose) {}

    /** @return array<string, string> */
    public static function reportTypeOptions(): array
    {
        return [
            self::TYPE_SUMMARY => 'Financial summary',
            self::TYPE_COLLECTIONS => 'Collections & payments',
            self::TYPE_SALES => 'Sales report',
            self::TYPE_EXPENSES => 'Expenses report',
            self::TYPE_PURCHASES => 'Ingredient purchases',
            self::TYPE_STAFF => 'Staff day closes',
        ];
    }

    /** @return array{start: Carbon, end: Carbon, label: string} */
    public function resolvePeriodRange(string $period, Carbon $anchor): array
    {
        $anchor = $anchor->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($period === self::PERIOD_WEEK) {
            $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $end = $anchor->copy()->endOfWeek(Carbon::MONDAY)->startOfDay();
            $label = $start->format('d M').' – '.$end->format('d M Y');

            return [
                'start' => $start,
                'end' => $end->gt($today) ? $today : $end,
                'label' => $label,
            ];
        }

        if ($period === self::PERIOD_MONTH) {
            $start = $anchor->copy()->startOfMonth();
            $end = $anchor->copy()->endOfMonth()->startOfDay();
            $label = $anchor->format('F Y');

            return [
                'start' => $start,
                'end' => $end->gt($today) ? $today : $end,
                'label' => $label,
            ];
        }

        return [
            'start' => $anchor,
            'end' => $anchor,
            'label' => $anchor->format('d M Y'),
        ];
    }

    /** @return array<string, mixed> */
    public function buildReport(string $period, Carbon $anchor): array
    {
        if ($period === self::PERIOD_DAY) {
            return $this->buildDayReport($anchor);
        }

        return $this->buildRangeReport($period, $anchor);
    }

    /** @return array<string, mixed> */
    public function buildDayReport(Carbon $businessDate): array
    {
        $date = $businessDate->toDateString();

        $dayClose = DayClose::query()
            ->with('closedBy')
            ->whereDate('business_date', $date)
            ->first();

        if ($dayClose) {
            $summary = $dayClose->summary ?? [];
            $dataSource = 'closed';
        } else {
            $staffCloses = StaffDayClose::query()
                ->whereDate('business_date', $date)
                ->get();

            $summary = $staffCloses->isNotEmpty()
                ? $this->dayClose->aggregateStaffCloseSummaries($staffCloses)
                : $this->dayClose->buildSummary($businessDate);

            $dataSource = $staffCloses->isNotEmpty() ? 'submissions' : 'live';
        }

        $receivings = $this->receivingsForRange($businessDate, $businessDate);

        $receivingsTotal = round((float) $receivings->sum('purchase_cost'), 2);
        $netInHand = (float) ($summary['net_in_hand'] ?? (($summary['total_collected'] ?? 0) - ($summary['expenses_total'] ?? 0)));
        $balanceAfterPurchases = round($netInHand - $receivingsTotal, 2);

        $staffCloses = $dayClose
            ? collect($summary['staff_closes'] ?? [])
            : StaffDayClose::query()
                ->with('user:id,name')
                ->whereDate('business_date', $date)
                ->orderBy('closed_at')
                ->get();

        $report = [
            'period' => self::PERIOD_DAY,
            'period_label' => $businessDate->format('d M Y'),
            'range_start' => $date,
            'range_end' => $date,
            'business_date' => $date,
            'is_closed' => (bool) $dayClose,
            'data_source' => $dataSource,
            'day_close' => $dayClose,
            'summary' => $summary,
            'expenses' => collect($summary['expenses'] ?? []),
            'receivings' => $receivings,
            'receivings_count' => $receivings->count(),
            'receivings_total' => $receivingsTotal,
            'net_in_hand' => round($netInHand, 2),
            'balance_after_purchases' => $balanceAfterPurchases,
            'staff_closes' => $staffCloses,
            'daily_breakdown' => collect(),
        ];

        $report['has_data'] = $this->hasReportData($report);

        return $report;
    }

    /** @return array<string, mixed> */
    public function buildRangeReport(string $period, Carbon $anchor): array
    {
        $range = $this->resolvePeriodRange($period, $anchor);
        $start = $range['start'];
        $end = $range['end'];

        $summary = $this->buildRangeSummary($start, $end);
        $receivings = $this->receivingsForRange($start, $end);
        $expenses = DayExpense::query()
            ->with('recordedBy:id,name')
            ->whereDate('business_date', '>=', $start->toDateString())
            ->whereDate('business_date', '<=', $end->toDateString())
            ->orderBy('business_date')
            ->orderBy('id')
            ->get();

        $receivingsTotal = round((float) $receivings->sum('purchase_cost'), 2);
        $netInHand = (float) ($summary['net_in_hand'] ?? 0);
        $balanceAfterPurchases = round($netInHand - $receivingsTotal, 2);

        $dailyBreakdown = $this->buildDailyBreakdown($start, $end);

        $report = [
            'period' => $period,
            'period_label' => $range['label'],
            'range_start' => $start->toDateString(),
            'range_end' => $end->toDateString(),
            'business_date' => $anchor->toDateString(),
            'is_closed' => false,
            'data_source' => 'live',
            'day_close' => null,
            'summary' => $summary,
            'expenses' => $expenses->map(fn (DayExpense $expense) => [
                'id' => $expense->id,
                'category' => $expense->category,
                'label' => $expense->categoryLabel(),
                'description' => $expense->description,
                'amount' => round((float) $expense->amount, 2),
                'recorded_by' => $expense->recordedBy?->name,
                'business_date' => $expense->business_date->format('Y-m-d'),
            ]),
            'receivings' => $receivings,
            'receivings_count' => $receivings->count(),
            'receivings_total' => $receivingsTotal,
            'net_in_hand' => round($netInHand, 2),
            'balance_after_purchases' => $balanceAfterPurchases,
            'staff_closes' => collect(),
            'daily_breakdown' => $dailyBreakdown,
            'closed_days_count' => DayClose::query()
                ->whereDate('business_date', '>=', $start->toDateString())
                ->whereDate('business_date', '<=', $end->toDateString())
                ->count(),
        ];

        $report['has_data'] = $this->hasReportData($report);

        return $report;
    }

    /** @return array<string, mixed> */
    public function buildRangeSummary(Carbon $start, Carbon $end): array
    {
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $sales = Sale::query()
            ->realSales()
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->get(['id', 'total', 'amount_paid', 'status']);

        $payments = SalePayment::query()
            ->with('paymentProvider:id,name,type')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->get(['id', 'amount', 'payment_method', 'payment_provider_id']);

        $cashCollected = 0.0;
        $mobileCollected = 0.0;
        $bankCollected = 0.0;
        $creditRecorded = 0.0;
        $mobileProviders = [];
        $bankProviders = [];

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;

            if ($payment->payment_method === Sale::PAYMENT_CASH) {
                $cashCollected += $amount;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_CREDIT) {
                $creditRecorded += $amount;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_MOBILE) {
                $mobileCollected += $amount;
                $key = $payment->payment_provider_id ?: 'none';
                if (! isset($mobileProviders[$key])) {
                    $mobileProviders[$key] = [
                        'name' => $payment->paymentProvider?->name ?? 'Unspecified mobile',
                        'amount' => 0.0,
                        'count' => 0,
                    ];
                }
                $mobileProviders[$key]['amount'] += $amount;
                $mobileProviders[$key]['count']++;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_BANK) {
                $bankCollected += $amount;
                $key = $payment->payment_provider_id ?: 'none';
                if (! isset($bankProviders[$key])) {
                    $bankProviders[$key] = [
                        'name' => $payment->paymentProvider?->name ?? 'Unspecified bank',
                        'amount' => 0.0,
                        'count' => 0,
                    ];
                }
                $bankProviders[$key]['amount'] += $amount;
                $bankProviders[$key]['count']++;
                continue;
            }

            $cashCollected += $amount;
        }

        $formatProviders = fn (array $providers) => collect($providers)
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'amount' => round($row['amount'], 2),
                'count' => $row['count'],
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $expenses = DayExpense::query()
            ->whereDate('business_date', '>=', $start->toDateString())
            ->whereDate('business_date', '<=', $end->toDateString())
            ->get();

        $expensesTotal = round((float) $expenses->sum('amount'), 2);
        $expensesByCategory = $expenses
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => $category,
                'label' => DayExpense::categoryOptions()[$category] ?? ucfirst($category),
                'amount' => round((float) $rows->sum('amount'), 2),
                'count' => $rows->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $totalCollected = round($cashCollected + $mobileCollected + $bankCollected, 2);
        $pendingSales = $sales->filter(fn (Sale $sale) => $sale->hasOutstandingBalance());
        $paidSales = $sales->filter(fn (Sale $sale) => $sale->isPaid());

        return [
            'sales_count' => $sales->count(),
            'sales_total' => round((float) $sales->sum('total'), 2),
            'payments_count' => $payments->count(),
            'cash_collected' => round($cashCollected, 2),
            'mobile_collected' => round($mobileCollected, 2),
            'mobile_providers' => $formatProviders($mobileProviders),
            'bank_collected' => round($bankCollected, 2),
            'bank_providers' => $formatProviders($bankProviders),
            'credit_recorded' => round($creditRecorded, 2),
            'total_collected' => $totalCollected,
            'expenses_count' => $expenses->count(),
            'expenses_total' => $expensesTotal,
            'expenses_by_category' => $expensesByCategory,
            'net_in_hand' => round($totalCollected - $expensesTotal, 2),
            'pending_sales_count' => $pendingSales->count(),
            'pending_sales_total' => round((float) $pendingSales->sum(fn (Sale $sale) => $sale->balanceDue()), 2),
            'paid_sales_count' => $paidSales->count(),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function buildDailyBreakdown(Carbon $start, Carbon $end): Collection
    {
        $rows = collect();
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $daySummary = $this->dayClose->buildSummary($cursor);
            $dayReceivings = $this->receivingsForRange($cursor, $cursor);
            $receivingsTotal = round((float) $dayReceivings->sum('purchase_cost'), 2);
            $collected = (float) ($daySummary['total_collected'] ?? 0);
            $expensesTotal = (float) ($daySummary['expenses_total'] ?? 0);
            $net = round($collected - $expensesTotal, 2);

            $hasData = $collected > 0
                || $expensesTotal > 0
                || $receivingsTotal > 0
                || ($daySummary['sales_count'] ?? 0) > 0;

            if ($hasData) {
                $rows->push([
                    'date' => $cursor->toDateString(),
                    'label' => $cursor->format('d M Y'),
                    'collected' => $collected,
                    'expenses' => $expensesTotal,
                    'receivings' => $receivingsTotal,
                    'net' => $net,
                    'is_closed' => DayClose::query()->whereDate('business_date', $cursor)->exists(),
                ]);
            }

            $cursor->addDay();
        }

        return $rows;
    }

    /** @return Collection<int, IngredientReceiving> */
    private function receivingsForRange(Carbon $start, Carbon $end): Collection
    {
        return IngredientReceiving::query()
            ->with(['ingredient', 'user:id,name'])
            ->whereDate('received_at', '>=', $start->toDateString())
            ->whereDate('received_at', '<=', $end->toDateString())
            ->where('status', IngredientReceiving::STATUS_ACTIVE)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get();
    }

    /** @param  array<string, mixed>  $report */
    public function hasReportData(array $report): bool
    {
        if ($report['period'] !== self::PERIOD_DAY && ($report['daily_breakdown'] ?? collect())->isNotEmpty()) {
            return true;
        }

        if ($report['is_closed'] ?? false) {
            return true;
        }

        if (($report['staff_closes'] ?? collect())->isNotEmpty()) {
            return true;
        }

        if (($report['receivings_count'] ?? 0) > 0) {
            return true;
        }

        $summary = $report['summary'] ?? [];

        return ($summary['sales_count'] ?? 0) > 0
            || ($summary['payments_count'] ?? 0) > 0
            || ($summary['expenses_count'] ?? 0) > 0
            || ($summary['total_collected'] ?? 0) > 0;
    }

    /** @return Collection<int, DayClose> */
    public function recentClosedDays(int $limit = 10): Collection
    {
        return DayClose::query()
            ->with('closedBy')
            ->latest('business_date')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Sale> */
    public function salesForRange(Carbon $start, Carbon $end): Collection
    {
        return Sale::query()
            ->realSales()
            ->with(['customer:id,name', 'user:id,name'])
            ->whereBetween('sold_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->orderByDesc('sold_at')
            ->get();
    }

    /** @return Collection<int, StaffDayClose> */
    public function staffClosesForRange(Carbon $start, Carbon $end): Collection
    {
        return StaffDayClose::query()
            ->with('user:id,name')
            ->whereDate('business_date', '>=', $start->toDateString())
            ->whereDate('business_date', '<=', $end->toDateString())
            ->orderBy('business_date')
            ->orderBy('closed_at')
            ->get();
    }

    /** @param  array<string, mixed>  $report */
    public function hasDataForType(array $report, string $type): bool
    {
        $summary = $report['summary'] ?? [];

        return match ($type) {
            self::TYPE_COLLECTIONS => ($summary['payments_count'] ?? 0) > 0
                || ($summary['total_collected'] ?? 0) > 0,
            self::TYPE_SALES => ($summary['sales_count'] ?? 0) > 0,
            self::TYPE_EXPENSES => ($report['expenses'] ?? collect())->isNotEmpty()
                || ($summary['expenses_count'] ?? 0) > 0,
            self::TYPE_PURCHASES => ($report['receivings_count'] ?? 0) > 0,
            self::TYPE_STAFF => ($report['staff_closes'] ?? collect())->isNotEmpty(),
            default => $this->hasReportData($report),
        };
    }
}
