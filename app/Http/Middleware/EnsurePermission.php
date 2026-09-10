<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You do not have permission to access this area.');
        }

        foreach (explode('|', $permission) as $key) {
            if ($user->hasPermission(trim($key))) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to access this area.');
    }
}
