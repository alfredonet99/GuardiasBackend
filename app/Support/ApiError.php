<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiError
{
    public static function respond(string $code, int $status = 400, array $meta = [], ?string $message = null): JsonResponse
    {
        return response()->json([
            'ok'      => false,
            'code'    => $code,
            'message' => $message,
            'meta'    => (object) $meta,
        ], $status);
    }
}
