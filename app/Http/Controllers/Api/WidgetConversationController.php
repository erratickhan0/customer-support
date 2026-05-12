<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Widget\WidgetApiKeyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetConversationController extends Controller
{
    public function __invoke(
        Request $request,
        Conversation $conversation,
        WidgetApiKeyResolver $apiKeyResolver,
    ): JsonResponse {
        $validated = $request->validate([
            'api_key' => ['required', 'string', 'min:20', 'max:255'],
            'session_id' => ['required', 'string', 'max:120'],
        ]);

        $agency = $apiKeyResolver->resolveAgency($validated['api_key']);

        if (
            ! $agency
            || $conversation->agency_id !== $agency->id
            || $conversation->session_id !== $validated['session_id']
        ) {
            return response()->json([
                'message' => 'Conversation not found.',
            ], 404);
        }

        $conversation->load(['messages' => fn ($query) => $query->orderBy('created_at')]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'status' => $conversation->status,
                'session_id' => $conversation->session_id,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
            ],
            'messages' => $conversation->messages->map(fn ($message): array => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'content' => $message->content,
                'confidence' => $message->confidence,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }
}
