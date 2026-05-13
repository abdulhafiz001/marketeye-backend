<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;

class AdminActivityLogger
{
    public function log(
        User $admin,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $meta = null
    ): AdminActivityLog {
        return AdminActivityLog::query()->create([
            'admin_id' => $admin->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
