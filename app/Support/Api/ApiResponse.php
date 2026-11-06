<?php

namespace App\Support\Api;

use App\Http\Middleware\AssignRequestId;
use Illuminate\Http\JsonResponse;

/**
 * Builds the standard /api/v1 response envelopes:
 *
 *   success: { "data": ..., "meta": { "request_id": "uuid" } }
 *   error:   { "error": { "code", "message", "details" }, "meta": { "request_id": "uuid" } }
 *
 * Never put stack traces, SQL, secrets or provider credentials in these.
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data, int $status = 200, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => [...$meta, 'request_id' => self::requestId()],
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  array<string, mixed>  $headers
     */
    public static function error(string $code, string $message, int $status, array $details = [], array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
            'meta' => ['request_id' => self::requestId()],
        ], $status, $headers);
    }

    public static function requestId(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $id = request()->attributes->get(AssignRequestId::ATTRIBUTE);

        return is_string($id) ? $id : null;
    }
}
