<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreWidgetMessageRequest;
use App\Services\Widget\WidgetApiKeyResolver;
use App\Services\Widget\WidgetMessageIngestionService;
use Illuminate\Http\JsonResponse;

class WidgetMessageController extends Controller
{
    public function __invoke(
        StoreWidgetMessageRequest $request,
        WidgetApiKeyResolver $apiKeyResolver,
        WidgetMessageIngestionService $ingestionService,
    ): JsonResponse {
        $agency = $apiKeyResolver->resolveAgency($request->string('api_key')->toString());

        if (! $agency) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        $result = $ingestionService->ingest($agency, $request->validated());

        return response()->json([
            'conversation_id' => $result['conversation']->id,
            'message_id' => $result['message']->id,
            'status' => $result['conversation']->status,
        ], 202);
    }
}
