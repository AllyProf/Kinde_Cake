<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public function __construct(private IpLookupService $ipLookup) {}

    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $request = $request ?? request();
        $user = $user ?? auth()->user();

        $ipAddress = $request?->ip();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => $ipAddress,
            'ip_location' => $this->ipLookup->lookup($ipAddress),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
