<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function __construct(private AuditLogService $auditLogs) {}
    public function start(Request $request, User $staff): RedirectResponse
    {
        $owner = $request->user();

        if (! $owner->isOwner() || User::isImpersonating()) {
            abort(403, 'Only the owner can impersonate staff.');
        }

        if ($staff->role !== User::ROLE_STAFF) {
            abort(403, 'You can only impersonate staff members.');
        }

        if (! $staff->is_active) {
            return redirect()
                ->back()
                ->with('error', 'Cannot impersonate an inactive staff account.');
        }

        $ownerId = $owner->id;

        Auth::login($staff);
        $request->session()->put(User::IMPERSONATOR_SESSION_KEY, $ownerId);

        $this->auditLogs->log(
            AuditLog::ACTION_IMPERSONATION_STARTED,
            "Started impersonating {$staff->name}.",
            $staff,
            user: $owner,
            request: $request,
        );

        return redirect()
            ->route('dashboard')
            ->with('success', "You are now viewing the app as {$staff->name}.");
    }

    public function stop(Request $request): RedirectResponse
    {
        $ownerId = $request->session()->pull(User::IMPERSONATOR_SESSION_KEY);

        if (! $ownerId) {
            return redirect()->route('dashboard');
        }

        $owner = User::query()->find($ownerId);

        if (! $owner || ! $owner->isOwner() || ! $owner->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Impersonation session ended. Please sign in again.');
        }

        Auth::login($owner);
        $request->session()->forget(User::IMPERSONATOR_SESSION_KEY);

        $this->auditLogs->log(
            AuditLog::ACTION_IMPERSONATION_STOPPED,
            'Stopped impersonating staff.',
            user: $owner,
            request: $request,
        );

        return redirect()
            ->route('staff.index')
            ->with('success', 'Returned to your owner account.');
    }
}
