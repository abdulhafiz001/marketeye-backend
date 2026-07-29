<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendTestPushCommand extends Command
{
    protected $signature = 'push:test {user : User id or email}';

    protected $description = 'Send a test FCM/Expo push notification to a user';

    public function handle(PushNotificationService $push): int
    {
        $lookup = (string) $this->argument('user');
        $user = User::query()
            ->when(
                ctype_digit($lookup),
                fn ($q) => $q->where('id', (int) $lookup),
                fn ($q) => $q->where('email', $lookup)
            )
            ->first();

        if (! $user) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $this->info("User #{$user->id} {$user->email}");
        $this->line('fcm_device_token: '.($user->fcm_device_token ? 'set' : 'missing'));
        $this->line('expo_push_token: '.($user->expo_push_token ? 'set' : 'missing'));

        $ok = $push->notifyUser(
            $user,
            'Market Eye test',
            'If you see this, push notifications are working.',
            ['type' => 'test']
        );

        if ($ok) {
            $this->info('Push dispatched (FCM and/or Expo). Check the device.');

            return self::SUCCESS;
        }

        $this->error('No usable device token, or FCM credentials failed. Sign in on a dev build and open Notification Settings.');

        return self::FAILURE;
    }
}
