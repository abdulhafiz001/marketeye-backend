<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\PriceSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    use ApiResponse;

    public function __invoke(Request $request): JsonResponse
    {
        $range = $request->query('range', 'all');
        $currentId = $request->user()->id;

        $query = User::query()
            ->whereNull('banned_at')
            ->where('role', User::ROLE_USER);

        if ($range === 'week') {
            $ids = PriceSubmission::query()
                ->where('submitted_at', '>=', now()->subDays(7))
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');
            $query->whereIn('id', $ids);
        } elseif ($range === 'month') {
            $ids = PriceSubmission::query()
                ->where('submitted_at', '>=', now()->subDays(30))
                ->whereNotNull('user_id')
                ->distinct()
                ->pluck('user_id');
            $query->whereIn('id', $ids);
        }

        $top = (clone $query)
            ->orderByDesc('points')
            ->orderBy('name')
            ->limit(10)
            ->get();

        $ranked = $top->values()->map(function (User $u, int $idx) use ($range) {
            $subCount = $this->submissionCountForRange($u->id, $range);

            return [
                'rank' => $idx + 1,
                'user' => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'avatar' => $u->avatar,
                    'points' => (int) $u->points,
                ],
                'submission_count' => $subCount,
            ];
        });

        $me = User::query()->find($currentId);
        $myRank = 1 + (int) User::query()
            ->whereNull('banned_at')
            ->where('points', '>', $me?->points ?? 0)
            ->count();

        return $this->success([
            'range' => $range,
            'leaderboard' => $ranked,
            'you' => [
                'rank' => $myRank,
                'points' => (int) ($me?->points ?? 0),
            ],
        ]);
    }

    private function submissionCountForRange(int $userId, string $range): int
    {
        $q = PriceSubmission::query()->where('user_id', $userId);
        if ($range === 'week') {
            $q->where('submitted_at', '>=', now()->subDays(7));
        } elseif ($range === 'month') {
            $q->where('submitted_at', '>=', now()->subDays(30));
        }

        return (int) $q->count();
    }
}
