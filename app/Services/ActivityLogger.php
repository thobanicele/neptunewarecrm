<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Log an activity against a subject model (Quote, SalesOrder, Invoice, etc.)
     *
     * @param int $tenantId
     * @param string $action
     * @param \Illuminate\Database\Eloquent\Model $subject
     * @param array $meta
     * @param int|null $userId If null, uses auth()->id(), and if still null logs as system (null).
     */
    public function log(int $tenantId, string $action, Model $subject, array $meta = [], ?int $userId = null): void
    {
        $resolvedUserId = $userId ?? auth()->id(); // may be null (queue/console)

        ActivityLog::create([
            'tenant_id'    => $tenantId,
            'user_id'      => $resolvedUserId, // allow null => "system"
            'action'       => $action,
            'subject_type' => $subject::class,
            'subject_id'   => (int) $subject->getKey(),
            'meta'         => $meta, // ensure ActivityLog::$casts has 'meta' => 'array'
        ]);
    }
}