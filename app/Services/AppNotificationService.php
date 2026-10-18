<?php

namespace App\Services;

use App\Models\DayClose;
use App\Models\Ingredient;
use App\Models\Sale;
use App\Models\StaffDayClose;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AppNotificationService
{
    public function __construct(private DayCloseService $dayClose) {}

    /** @return list<array{type: string, message: string, link?: string, label?: string, icon?: string}> */
    public function alerts(User $user): array
    {
        $today = now()->startOfDay();
        $inventory = $this->inventoryAlerts($user);
        $debtQuery = $this->debtQuery($user);
        $overdueDebts = (clone $debtQuery)->get()->filter(fn (Sale $sale) => $sale->isDebtOverdue())->count();
        $activeCakePoint = $this->activeCakePointCount($user);
        $pendingStaff = $user->isOwner() ? $this->dayClose->pendingStaffForDate($today)->count() : 0;

        return $this->buildAlerts($user, $today, [
            'overdue_debts' => $overdueDebts,
            'active_cake_point' => $activeCakePoint,
            'pending_staff_count' => $pendingStaff,
        ], $inventory);
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

    /** @param  array<string, mixed>  $stats
     * @param  array{low_stock: Collection, out_of_stock: Collection}  $inventory
     * @return list<array{type: string, message: string, link?: string, label?: string, icon?: string}>
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
                    'icon' => 'fa-calendar-check-o',
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
                    'icon' => 'fa-users',
                ];
            } elseif (! DayClose::query()->whereDate('business_date', $today)->exists()
                && $this->dayClose->ownerCanCloseBusinessDay($user, $today)) {
                $alerts[] = [
                    'type' => 'success',
                    'message' => 'You can close the business day for today.',
                    'link' => route('day-closes.index', ['date' => $today->format('Y-m-d')]),
                    'label' => 'Close business day',
                    'icon' => 'fa-check-circle',
                ];
            }
        }

        if (($stats['overdue_debts'] ?? 0) > 0 && $user->hasPermission('orders.view')) {
            $alerts[] = [
                'type' => 'danger',
                'message' => $stats['overdue_debts'].' overdue debt(s) need follow-up.',
                'link' => route('debts.index', ['due' => 'overdue']),
                'label' => 'View debts',
                'icon' => 'fa-exclamation-circle',
            ];
        }

        if ($user->hasPermission('inventory.view') && ($inventory['out_of_stock']->isNotEmpty() || $inventory['low_stock']->isNotEmpty())) {
            $alerts[] = [
                'type' => 'warning',
                'message' => $inventory['out_of_stock']->count().' out of stock, '.$inventory['low_stock']->count().' low stock ingredient(s).',
                'link' => route('stock.index'),
                'label' => 'View stock',
                'icon' => 'fa-cubes',
            ];
        }

        if (($stats['active_cake_point'] ?? 0) > 0 && $user->hasPermission('orders.view')) {
            $alerts[] = [
                'type' => 'info',
                'message' => $stats['active_cake_point'].' active order(s).',
                'link' => route('cake-point.index'),
                'label' => 'Orders',
                'icon' => 'fa-birthday-cake',
                'count' => (int) $stats['active_cake_point'],
            ];
        }

        return $alerts;
    }
}
