<?php

return [
    /*
    | Path to the Firebase service account JSON (FCM HTTP v1).
    | Prefer an absolute path, or a path relative to base_path().
    */
    'credentials' => env('FIREBASE_CREDENTIALS') ?: storage_path('app/firebase/service-account.json'),

    'project_id' => env('FIREBASE_PROJECT_ID', 'marketeye-f8498'),

    'android_channel_id' => env('FIREBASE_ANDROID_CHANNEL_ID', 'price-alerts'),
];
