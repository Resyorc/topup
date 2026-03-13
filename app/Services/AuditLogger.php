<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        string $event,
        string $description,
        ?string $subjectType = null,
        ?string $subjectId = null,
        ?int $userId = null,
        ?Request $request = null,
    ): void {
        $request ??= app(Request::class);

        AuditLog::create([
            'user_id'      => $userId ?? auth()->id(),
            'event'        => $event,
            'description'  => $description,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);
    }
}
