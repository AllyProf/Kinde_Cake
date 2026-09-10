<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private RoleService $roles) {}

    public function index(): View
    {
        $this->roles->ensureDefaults();

        $roles = Role::withCount('users')->orderBy('name')->get();
        $permissionGroups = config('permissions.groups', []);

        return view('roles.index', compact('roles', 'permissionGroups'));
    }

    public function create(): View
    {
        return view('roles.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);

        Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $this->roles->sanitizePermissions($validated['permissions'] ?? []),
            'is_system' => false,
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        return view('roles.edit', array_merge($this->formData(), compact('role')));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $this->validateRole($request, $role);

        $role->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => $this->roles->sanitizePermissions($validated['permissions'] ?? []),
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Remove staff assigned to this role before deleting it.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'permissionGroups' => config('permissions.groups', []),
        ];
    }

    /** @return array<string, mixed> */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('roles', 'name')->ignore($role?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'max:120'],
        ]);
    }
}
