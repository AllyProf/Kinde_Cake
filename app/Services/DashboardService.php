<?php

namespace App\Services;

use App\Models\DayClose;
use App\Models\DayExpense;
use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StaffDayClose;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private DayCloseService $dayClose,
        private DayReportService $dayReport,
        private AppSettingsService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function build(User $user): array
    {
        $today = now()->startOfDay();
        $monthStart = now()->copy()->startOfMonth();
        $scopeUser = $user->isOwner() ? null : $user;

        $todaySummary = $this->dayClose->buildSummary($today, $scopeUser);
        $monthSummary = $user->isOwner()
            ? $this->dayReport->buildRangeSummary($monthStart, $today)
            : $this->buildStaffMonthSummary($user, $monthStart, $today);

        $weekTrend = $this->buildWeekTrend($user, $today);
        $paymentMethods = $this->buildPaymentMethodChart($todaySummary);
        $debtRows = $this->debtQuery($user)->get(['id', 'sale_number', 'customer_name', 'total', 'amount_paid', 'payment_method', 'credit_repayment_date', 'status', 'sold_at']);

        $stats = [
            'today_collected' => (float) ($todaySummary['total_collected'] ?? 0),
            'today_sales_count' => (int) ($todaySummary['sales_count'] ?? 0),
            'today_expenses' => (float) ($todaySummary['expenses_total'] ?? 0),
            'today_net' => (float) ($todaySummary['net_in_hand'] ?? 0),
            'month_collected' => (float) ($monthSummary['total_collected'] ?? 0),
            'month_sales_count' => (int) ($monthSummary['sales_count'] ?? 0),
            'month_sales_total' => (float) ($monthSummary['sales_total'] ?? 0),
            'outstanding_debt' => round((float) $debtRows->sum(fn (Sale $sale) => $sale->debtAmount()), 2),
            'overdue_debts' => $debtRows->filter(fn (Sale $sale) => $sale->isDebtOverdue())->count(),
            'open_debts_count' => $debtRows->count(),
            'active_cake_point' => $this->activeCakePointCount($user),
        ];

        $inventory = $this->inventoryAlerts($user);
        $stats['low_stock_count'] = $inventory['low_stock']->count();
        $stats['out_of_stock_count'] = $inventory['out_of_stock']->count();

        return [
            'business_name' => $this->settings->get('business_name', config('app.name', 'Kinde Cake')),
            'is_owner' => $user->isOwner(),
            'today' => $today,
            'today_summary' => $todaySummary,
            'month_summary' => $monthSummary,
            'week_trend' => $weekTrend,
            'payment_methods' => $paymentMethods,
            'stats' => $stats,
            'alerts' => $this->buildAlerts($user, $today, $stats, $inventory),
            'recent_sales' => $this->recentSales($user),
            'top_debts' => $debtRows
                ->sortByDesc(fn (Sale $sale) => $sale->debtAmount())
                ->take(5)
                ->values(),
            'low_stock' => $inventory['low_stock']->take(5)->values(),
            'out_of_stock' => $inventory['out_of_stock']->take(5)->values(),
            'business_day_closed' => DayClose::query()->whereDate('business_date', $today)->exists(),
            'personal_day_closed' => StaffDayClose::query()
                ->where('user_id', $user->id)
                ->whereDate('business_date', $today)
                ->exists(),
            'pending_staff_count' => $user->isOwner()
                ? $this->dayClose->pendingStaffForDate($today)->count()
                : 0,
            'has_sales_access' => $user->hasPermission('orders.view'),
            'has_inventory_access' => $user->hasPermission('inventory.view'),
            'can_create_sale' => $user->hasPermission('orders.create'),
            'can_close_day' => $user->canClosePersonalDay(),
        ];
    }

    /** @return array{labels: list<string>, collected: list<float>, sales_total: list<float>} */
    private function buildWeekTrend(User $user, Carbon $today): array
    {
        $labels = [];
        $collected = [];
        $salesTotal = [];
        $scopeUser = $user->isOwner() ? null : $user;

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $summary = $this->dayClose->buildSummary($date, $scopeUser);

            $labels[] = $date->isSameDay($today) ? 'Today' : $date->format('D d M');
            $collected[] = round((float) ($summary['total_collected'] ?? 0), 2);
            $salesTotal[] = round((float) ($summary['sales_total'] ?? 0), 2);
        }

        return [
            'labels' => $labels,
            'collected' => $collected,
            'sales_total' => $salesTotal,
        ];
    }

    /** @param  array<string, mixed>  $summary
     * @return list<array{label: string, value: float, color: string, highlight: string}>
     */
    private function buildPaymentMethodChart(array $summary): array
    {
        $segments = [
            ['label' => 'Cash', 'value' => (float) ($summary['cash_collected'] ?? 0), 'color' => '#28a745', 'highlight' => '#34ce57'],
            ['label' => 'Mobile money', 'value' => (float) ($summary['mobile_collected'] ?? 0), 'color' => '#007bff', 'highlight' => '#3395ff'],
            ['label' => 'Bank', 'value' => (float) ($summary['bank_collected'] ?? 0), 'color' => '#6f42c1', 'highlight' => '#895bd4'],
            ['label' => 'Credit', 'value' => (float) ($summary['credit_recorded'] ?? 0), 'color' => '#fd7e14', 'highlight' => '#ff922b'],
        ];

        return array_values(array_filter($segments, fn (array $row) => $row['value'] > 0));
    }

    /** @return array<string, mixed> */
    private function buildStaffMonthSummary(User $user, Carbon $start, Carbon $end): array
    {
        $startDate = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        $sales = Sale::query()
            ->realSales()
            ->where('user_id', $user->id)
            ->whereBetween('sold_at', [$startDate, $endDate])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->get(['id', 'total', 'amount_paid', 'status']);

        $payments = SalePayment::query()
            ->where('user_id', $user->id)
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->get(['amount']);

        $expensesTotal = (float) DayExpense::query()
            ->where('recorded_by_user_id', $user->id)
            ->whereDate('business_date', '>=', $start->toDateString())
            ->whereDate('business_date', '<=', $end->toDateString())
            ->sum('amount');

        $totalCollected = round((float) $payments->sum('amount'), 2);

        return [
            'sales_count' => $sales->count(),
            'sales_total' => round((float) $sales->sum('total'), 2),
            'total_collected' => $totalCollected,
            'expenses_total' => round($expensesTotal, 2),
            'net_in_hand' => round($totalCollected - $expensesTotal, 2),
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Sale> */
    private function debtQuery(User $user)
    {
        $query = Sale::query()->withOpenDebt();

        if (! $user->isOwner() && ! $user->canPaySales()) {
            $query->visibleTo($user);
        }

        return $query;
    }

    private function activeCakePointCount(User $user): int
    {
        $query = Sale::query()
            ->whereNotNull('cake_point_status')
            ->whereNotIn('cake_point_status', [Sale::CAKE_POINT_COMPLETED])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]);

        if (! $user->isOwner()) {
            $query->where('assigned_to_user_id', $user->id);
        }

        return $query->count();
    }

    /** @return array{low_stock: Collection<int, Ingredient>, out_of_stock: Collection<int, Ingredient>} */
    private function inventoryAlerts(User $user): array
    {
        if (! $user->isOwner() && ! $user->hasPermission('inventory.view')) {
            return [
                'low_stock' => collect(),
                'out_of_stock' => collect(),
            ];
        }

        $ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'low_stock' => $ingredients->filter(
                fn (Ingredient $ingredient) => $ingredient->isLowStock() && (float) $ingredient->stock_quantity > 0
            )->values(),
            'out_of_stock' => $ingredients->filter(
                fn (Ingredient $ingredient) => (float) $ingredient->stock_quantity <= 0
            )->values(),
        ];
    }

    /** @return Collection<int, Sale> */
    private function recentSales(User $user): Collection
    {
        if (! $user->hasPermission('orders.view')) {
            return collect();
        }

        return Sale::query()
            ->realSales()
            ->visibleTo($user)
            ->with(['user:id,name', 'customer:id,name'])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->latest('sold_at')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    /** @param  array<string, mixed>  $stats
     * @param  array{low_stock: Collection, out_of_stock: Collection}  $inventory
     * @return list<array{type: string, message: string, link?: string, label?: string}>
     */
    private function buildAlerts(User $user, Carbon $today, array $stats, array $inventory): array
    {
        $alerts = [];

        if ($user->canClosePersonalDay() && ! StaffDayClose::query()->where('user_id', $user->id)->whereDate('business_date', $today)->exists()) {
            if ($this->dayClose->userHasActivity($user, $today)) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'You have activity today. Remember to close your personal day.',
                    'link' => route('day-closes.index', ['date' => $today->format('Y-m-d')]),
                    'label' => 'Close day',
                ];
            }
        }

        if ($user->isOwner()) {
            $pendingStaff = (int) ($stats['pending_staff_count'] ?? 0);
            if ($pendingStaff > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => $pendingStaff.' staff member(s) still need to close their personal day.',
                    'link' => route('day-closes.index', ['date' => $today->format('Y-m-d')]),
                    'label' => 'View close day',
                ];
            } elseif (! DayClose::query()->whereDate('business_date', $today)->exists()
                && $this->dayClose->ownerCanCloseBusinessDay($user, $today)) {
                $alerts[] = [
                    'type' => 'success',
                    'message' => 'You can close the business day for today.',
                    'link' => route('day-closes.index', ['date' => $today->format('Y-m-d')]),
                    'label' => 'Close business day',
                ];
            }
        }

        if (($stats['overdue_debts'] ?? 0) > 0 && $user->hasPermission('orders.view')) {
            $alerts[] = [
                'type' => 'danger',
                'message' => $stats['overdue_debts'].' overdue debt(s) need follow-up.',
                'link' => route('debts.index', ['due' => 'overdue']),
                'label' => 'View debts',
            ];
        }

        if ($user->hasPermission('inventory.view') && ($inventory['out_of_stock']->isNotEmpty() || $inventory['low_stock']->isNotEmpty())) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $inventory['out_of_stock']->count().' out of stock, '.$inventory['low_stock']->count().' low stock ingredient(s).',
                'link' => route('stock.index'),
                'label' => 'View stock',
            ];
        }

        if (($stats['active_cake_point'] ?? 0) > 0 && $user->hasPermission('orders.view')) {
            $alerts[] = [
                'type' => 'info',
                'message' => $stats['active_cake_point'].' active order(s).',
                'link' => route('cake-point.index'),
                'label' => 'Orders',
            ];
        }

        return $alerts;
    }
}
