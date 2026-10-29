<?php

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $roles = app(RoleService::class);

        $roles->syncSystemRolePermissions();

        foreach (Role::query()->where('is_system', false)->get() as $role) {
            $role->update([
                'permissions' => $roles->sanitizePermissions($role->permissionList()),
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
