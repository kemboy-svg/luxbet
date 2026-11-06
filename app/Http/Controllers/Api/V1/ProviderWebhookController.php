<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\GameProviders\Actions\IngestProviderWebhook;
use App\Domain\GameProviders\Exceptions\InvalidProviderWebhook;
use App\Domain\Games\Models\GameProvider;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/v1/webhooks/providers/{provider}
 *
 * No session or CSRF: authenticity comes only from the provider signature.
 * Client-supplied player IDs and balances are never trusted.
 */
class ProviderWebhookController extends Controller
{
    public function __invoke(Request $request, GameProvider $provider, IngestProviderWebhook $ingest): JsonResponse
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower($name)] = (string) ($values[0] ?? '');
        }

        try {
            $result = $ingest($provider, $request->getContent(), $headers);
        } catch (InvalidProviderWebhook $e) {
            return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status);
        }

        return ApiResponse::success($result->toArray());
    }
}
