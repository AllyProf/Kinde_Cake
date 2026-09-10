<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private RoleService $roles,
        private AuditLogService $auditLogs,
    ) {}

    public function index(): View
    {
        $this->roles->ensureDefaults();

        $staff = User::query()
            ->where('role', User::ROLE_STAFF)
            ->with('staffRole')
            ->latest()
            ->paginate(10);

        return view('staff.index', compact('staff'));
    }

    public function create(): View
    {
        return view('staff.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStaff($request);

        $staff = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'role' => User::ROLE_STAFF,
            'staff_role_id' => $validated['staff_role_id'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogs->log(
            AuditLog::ACTION_STAFF_CREATED,
            "Created staff member {$staff->name}.",
            $staff,
            ['email' => $staff->email],
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $staff): View
    {
        $this->authorizeStaff($staff);

        return view('staff.edit', array_merge($this->formData(), compact('staff')));
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);
        $validated = $this->validateStaff($request, $staff);

        $staff->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'staff_role_id' => $validated['staff_role_id'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $passwordChanged = ! empty($validated['password']);

        if ($passwordChanged) {
            $staff->update(['password' => $validated['password']]);
        }

        $this->auditLogs->log(
            AuditLog::ACTION_STAFF_UPDATED,
            "Updated staff member {$staff->name}.",
            $staff,
            array_filter([
                'password_changed' => $passwordChanged ?: null,
            ]),
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member updated successfully.');
    }

    public function resetPassword(Request $request, User $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $staff->update(['password' => $validated['password']]);

        $this->auditLogs->log(
            AuditLog::ACTION_STAFF_PASSWORD_RESET,
            "Reset password for {$staff->name}.",
            $staff,
        );

        return redirect()
            ->route('staff.index')
            ->with('success', "Password reset for {$staff->name}.");
    }

    public function destroy(User $staff): RedirectResponse
    {
        $this->authorizeStaff($staff);

        $name = $staff->name;
        $staff->delete();

        $this->auditLogs->log(
            AuditLog::ACTION_STAFF_DELETED,
            "Removed staff member {$name}.",
            properties: ['name' => $name],
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff member removed successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $this->roles->ensureDefaults();

        return [
            'roles' => \App\Models\Role::orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validateStaff(Request $request, ?User $staff = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($staff?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'staff_role_id' => [
                'required',
                Rule::exists('roles', 'id'),
            ],
        ]);
    }

    private function authorizeStaff(User $staff): void
    {
        if ($staff->role !== User::ROLE_STAFF) {
            abort(403);
        }
    }
}
