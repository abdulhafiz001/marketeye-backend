<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use Illuminate\Http\JsonResponse;

class ActivityLogController extends Controller
{
    use ApiResponse;

    public function __invoke(): JsonResponse
    {
        $rows = AdminActivityLog::query()
            ->with('admin')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn (AdminActivityLog $l) => [
                'id' => $l->id,
                'admin' => $l->admin ? ['id' => $l->admin->id, 'name' => $l->admin->name] : null,
                'action' => $l->action,
                'entity_type' => $l->entity_type,
                'entity_id' => $l->entity_id,
                'meta' => $l->meta,
                'created_at' => $l->created_at?->toIso8601String(),
            ]);

        return $this->success(['activity' => $rows]);
    }
}
