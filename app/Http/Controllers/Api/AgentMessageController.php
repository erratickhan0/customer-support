<?php

namespace App\Http\Controllers\Api;

use App\Events\ConversationStatusChanged;
use App\Events\MessageCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAgentMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AgentMessageController extends Controller
{
    public function store(StoreAgentMessageRequest $request, Conversation $conversation): MessageResource
    {
        if ($conversation->agency_id !== $request->user()->agency_id) {
            throw new HttpResponseException(response()->json(['message' => 'Forbidden'], 403));
        }

        $message = DB::transaction(function () use ($request, $conversation): Message {
            $agentMessage = Message::query()->create([
                'agency_id' => $conversation->agency_id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'agent',
                'content' => $request->string('content')->toString(),
                'metadata' => [
                    'agent_id' => $request->user()->id,
                ],
            ]);

            $conversation->forceFill([
                'status' => $request->input('status', 'human_active'),
                'assigned_user_id' => $request->user()->id,
                'last_message_at' => Carbon::now(),
            ])->save();

            return $agentMessage;
        });

        event(new MessageCreated(
            agencyId: $message->agency_id,
            conversationId: $message->conversation_id,
            messageId: $message->id,
            senderType: $message->sender_type,
        ));

        event(new ConversationStatusChanged(
            agencyId: $conversation->agency_id,
            conversationId: $conversation->id,
            status: $conversation->status,
        ));

        return new MessageResource($message);
    }
}
