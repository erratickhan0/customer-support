<?php

namespace App\Http\Controllers;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\Inbox\InboxConversationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, InboxConversationService $inboxConversationService): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:ai_handled,human_required,human_active,closed'],
            'q' => ['nullable', 'string', 'max:120'],
            'conversation' => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $conversations = $inboxConversationService->listForUser($request->user(), $filters);

        $selectedConversation = null;

        $selectedConversationId = $filters['conversation']
            ?? $conversations->first()?->getKey();

        if ($selectedConversationId && $request->user()->agency_id) {
            $selectedConversation = Conversation::query()
                ->where('agency_id', $request->user()->agency_id)
                ->whereKey($selectedConversationId)
                ->with(['messages' => fn ($query) => $query->orderBy('created_at'), 'latestMessage'])
                ->first();
        }

        return Inertia::render('Dashboard', [
            'filters' => [
                'status' => $filters['status'] ?? null,
                'q' => $filters['q'] ?? null,
            ],
            'conversations' => ConversationResource::collection($conversations),
            'selectedConversation' => $selectedConversation
                ? (new ConversationResource($selectedConversation))->resolve($request)
                : null,
        ]);
    }
}
