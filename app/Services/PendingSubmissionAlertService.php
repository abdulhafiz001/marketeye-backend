<?php

namespace App\Services;

use App\Mail\PendingSubmissionsAdminMail;
use App\Models\Admin;
use App\Models\PriceSubmission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PendingSubmissionAlertService
{
    public const PENDING_THRESHOLD = 10;

    public function notifyIfNeeded(): void
    {
        $pending = PriceSubmission::query()
            ->where('status', PriceSubmission::STATUS_PENDING)
            ->count();

        if ($pending < self::PENDING_THRESHOLD) {
            return;
        }

        // Avoid spamming: at most once every 6 hours while backlog remains.
        if (Cache::has('admin:pending_submissions_mail')) {
            return;
        }

        $admins = Admin::query()
            ->whereIn('role', [Admin::ROLE_ADMIN, Admin::ROLE_MODERATOR])
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        // Only ping when no admin has logged in since the backlog grew, or never logged in.
        $anyRecentLogin = $admins->contains(function (Admin $admin) {
            return $admin->last_login_at && $admin->last_login_at->gt(now()->subHours(12));
        });

        if ($anyRecentLogin) {
            return;
        }

        foreach ($admins as $admin) {
            if (! $admin->email) {
                continue;
            }
            Mail::to($admin->email)->send(new PendingSubmissionsAdminMail($pending));
        }

        Cache::put('admin:pending_submissions_mail', true, now()->addHours(6));
    }
}
