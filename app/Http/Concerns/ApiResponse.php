<?php

namespace App\Http\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function failure(string $message, array $errors = [], int $code = 422): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
            'errors' => empty($errors) ? new \stdClass : $errors,
        ];

        return response()->json($payload, $code);
    }
}
