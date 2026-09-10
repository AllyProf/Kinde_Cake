<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private AuditLogService $auditLogs) {}
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            $this->auditLogs->log(
                AuditLog::ACTION_LOGIN_FAILED,
                'Failed login attempt for '.$credentials['email'].'.',
                properties: ['email' => $credentials['email']],
                request: $request,
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Invalid email or password.');
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->with('error', 'Your account has been deactivated.');
        }

        $request->session()->regenerate();

        $this->auditLogs->log(
            AuditLog::ACTION_LOGIN,
            "{$user->name} signed in.",
            $user,
            request: $request,
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->auditLogs->log(
                AuditLog::ACTION_LOGOUT,
                "{$user->name} signed out.",
                $user,
                request: $request,
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
