<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $cashier = Role::query()->where('slug', 'cashier')->first();

        if (! $cashier) {
            return;
        }

        $permissions = array_values(array_unique(array_merge(
            $cashier->permissionList(),
            ['orders.manage'],
        )));

        $cashier->update(['permissions' => $permissions]);
    }

    public function down(): void
    {
        $cashier = Role::query()->where('slug', 'cashier')->first();

        if (! $cashier) {
            return;
        }

        $cashier->update([
            'permissions' => array_values(array_filter(
                $cashier->permissionList(),
                fn (string $permission) => $permission !== 'orders.manage',
            )),
        ]);
    }
};
