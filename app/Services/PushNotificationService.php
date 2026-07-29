<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    public function __construct(
        private readonly FcmPushService $fcm,
        private readonly ExpoPushService $expo,
    ) {}

    /**
     * Prefer direct FCM (service account). Fall back to Expo push token.
     */
    public function notifyUser(User $user, string $title, string $body, array $data = []): bool
    {
        $fcmToken = trim((string) ($user->fcm_device_token ?? ''));
        if ($fcmToken !== '' && $this->fcm->isConfigured()) {
            $ok = $this->fcm->notify($fcmToken, $title, $body, $data);
            if ($ok) {
                return true;
            }
            Log::info('FCM failed for user, trying Expo', ['user_id' => $user->id]);
        }

        $expoToken = trim((string) ($user->expo_push_token ?? ''));
        if ($expoToken !== '' && $this->expo->isValidToken($expoToken)) {
            $this->expo->notify($expoToken, $title, $body, $data);

            return true;
        }

        return false;
    }
}
