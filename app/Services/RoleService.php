<?php

namespace App\Services;

use App\Models\Role;

class RoleService
{
    /** @return list<string> */
    public function allPermissionKeys(): array
    {
        $keys = [];
        foreach (config('permissions.groups', []) as $permissions) {
            foreach ($permissions as $key => $label) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function ensureDefaults(): void
    {
        if (Role::exists()) {
            return;
        }

        $defaults = [
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Full operational access except role management.',
                'permissions' => array_values(array_diff($this->allPermissionKeys(), ['roles.manage'])),
                'is_system' => true,
            ],
            [
                'name' => 'Baker',
                'slug' => 'baker',
                'description' => 'View and process orders, manage ingredients stock.',
                'permissions' => [
                    'dashboard.view',
                    'orders.view',
                    'orders.manage',
                    'inventory.view',
                    'inventory.manage',
                ],
                'is_system' => true,
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Take orders and manage customers.',
                'permissions' => [
                    'dashboard.view',
                    'orders.view',
                    'orders.create',
                    'orders.manage',
                    'customers.view',
                    'customers.manage',
                ],
                'is_system' => true,
            ],
        ];

        foreach ($defaults as $role) {
            Role::create($role);
        }
    }

    /** @param list<string> $submitted */
    public function sanitizePermissions(array $submitted): array
    {
        $valid = array_flip($this->allPermissionKeys());

        return array_values(array_filter($submitted, fn ($key) => isset($valid[$key])));
    }
}
