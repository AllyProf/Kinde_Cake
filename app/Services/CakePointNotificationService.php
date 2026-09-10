<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CakePointNotificationService
{
    public function unreadCount(User $user): int
    {
        if (! $user->hasPermission('orders.view') || $user->isOwner()) {
            return 0;
        }

        return $this->baseQuery($user)->count();
    }

    /** @return Collection<int, Sale> */
    public function recent(User $user, int $limit = 5): Collection
    {
        if (! $user->hasPermission('orders.view')) {
            return new Collection;
        }

        if ($user->isOwner()) {
            return Sale::query()
                ->whereNotNull('assigned_to_user_id')
                ->whereNotNull('cake_point_status')
                ->where('cake_point_status', Sale::CAKE_POINT_SENT)
                ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
                ->with(['items.item'])
                ->latest('assigned_at')
                ->limit($limit)
                ->get();
        }

        return $this->baseQuery($user)
            ->with(['items.item'])
            ->limit($limit)
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Sale> */
    private function baseQuery(User $user)
    {
        return Sale::query()
            ->where('assigned_to_user_id', $user->id)
            ->where('cake_point_status', Sale::CAKE_POINT_SENT)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->latest('assigned_at')
            ->latest('id');
    }
}
