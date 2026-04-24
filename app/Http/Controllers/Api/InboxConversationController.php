<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ListInboxConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\Inbox\InboxConversationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InboxConversationController extends Controller
{
    public function index(
        ListInboxConversationsRequest $request,
        InboxConversationService $inboxConversationService,
    ): AnonymousResourceCollection {
        $conversations = $inboxConversationService->listForUser(
            $request->user(),
            $request->validated(),
        );

        return ConversationResource::collection($conversations);
    }

    public function show(ListInboxConversationsRequest $request, Conversation $conversation): ConversationResource
    {
        if ($conversation->agency_id !== $request->user()->agency_id) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }

        $conversation->load([
            'messages' => fn ($query) => $query->orderBy('created_at'),
            'latestMessage',
        ]);

        return new ConversationResource($conversation);
    }
}
