<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $q = User::query()->orderByDesc('id');

        if ($request->query('role')) {
            $q->where('role', $request->query('role'));
        }

        $rows = $q->limit(500)->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'points' => (int) $u->points,
            'banned_at' => $u->banned_at?->toIso8601String(),
        ]);

        return $this->success(['users' => $rows]);
    }

    public function updateRole(int $id, Request $request): JsonResponse
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->failure('Not found.', [], 404);
        }

        $data = $request->validate([
            'role' => ['required', 'in:user,moderator,admin'],
        ]);

        $user->update(['role' => $data['role']]);

        return $this->success(['user' => ['id' => $user->id, 'role' => $user->role]]);
    }

    public function ban(int $id): JsonResponse
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->failure('Not found.', [], 404);
        }

        $user->update(['banned_at' => now()]);

        return $this->success(['user' => ['id' => $user->id, 'banned' => true]]);
    }

    public function unban(int $id): JsonResponse
    {
        $user = User::query()->find($id);
        if (! $user) {
            return $this->failure('Not found.', [], 404);
        }

        $user->update(['banned_at' => null]);

        return $this->success(['user' => ['id' => $user->id, 'banned' => false]]);
    }

    public function submissions(int $id): JsonResponse
    {
        $rows = PriceSubmission::query()
            ->where('user_id', $id)
            ->with(['product', 'market'])
            ->orderByDesc('submitted_at')
            ->limit(200)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'status' => $s->status,
                'price' => (float) $s->price,
                'submitted_at' => $s->submitted_at?->toIso8601String(),
                'product' => ['id' => $s->product?->id, 'name' => $s->product?->name],
                'market' => ['id' => $s->market?->id, 'name' => $s->market?->name],
            ]);

        return $this->success(['submissions' => $rows]);
    }
}
