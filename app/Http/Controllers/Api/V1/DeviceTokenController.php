<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'expo_push_token' => ['nullable', 'string', 'max:255'],
            'fcm_token' => ['nullable', 'string', 'max:4096'],
            'fcm_device_token' => ['nullable', 'string', 'max:4096'],
        ]);

        $expo = isset($data['expo_push_token']) ? trim((string) $data['expo_push_token']) : null;
        $fcm = trim((string) ($data['fcm_token'] ?? $data['fcm_device_token'] ?? ''));

        if (($expo === null || $expo === '') && $fcm === '') {
            return $this->failure('Provide expo_push_token and/or fcm_token.', [], 422);
        }

        $updates = [];
        if ($expo !== null && $expo !== '') {
            $updates['expo_push_token'] = $expo;
        }
        if ($fcm !== '') {
            $updates['fcm_device_token'] = $fcm;
        }

        $request->user()->update($updates);

        return $this->success([
            'expo_push_token' => $request->user()->expo_push_token,
            'fcm_device_token' => $request->user()->fcm_device_token ? 'saved' : null,
        ], 'Push tokens saved.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->update([
            'expo_push_token' => null,
            'fcm_device_token' => null,
        ]);

        return $this->success([], 'Push tokens cleared.');
    }
}
