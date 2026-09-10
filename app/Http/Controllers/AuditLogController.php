<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\IpLookupService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private IpLookupService $ipLookup) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'action' => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when($validated['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($validated['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($validated['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($validated['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', "%{$term}%")
                        ->orWhere('ip_address', 'like', "%{$term}%")
                        ->orWhere('ip_location', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        foreach ($logs as $log) {
            if ($log->ip_address && ! $log->ip_location) {
                $location = $this->ipLookup->lookup($log->ip_address);
                if ($location !== null) {
                    $log->forceFill(['ip_location' => $location])->save();
                }
            }
        }

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => AuditLog::actionOptions(),
            'filters' => $validated,
        ]);
    }
}
