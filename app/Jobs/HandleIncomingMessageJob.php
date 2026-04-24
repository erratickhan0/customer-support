<?php

namespace App\Jobs;

use App\Events\ConversationStatusChanged;
use App\Events\MessageCreated;
use App\Models\Message;
use App\Services\AI\RuleBasedResponder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class HandleIncomingMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId) {}

    public function handle(RuleBasedResponder $responder): void
    {
        $userMessage = Message::query()
            ->with('conversation')
            ->find($this->messageId);

        if (! $userMessage || $userMessage->sender_type !== 'user') {
            return;
        }

        $conversation = $userMessage->conversation;
        $response = $responder->generate($userMessage->content);

        $aiMessage = Message::query()->create([
            'agency_id' => $userMessage->agency_id,
            'conversation_id' => $userMessage->conversation_id,
            'sender_type' => 'ai',
            'content' => $response['reply'],
            'confidence' => $response['confidence'],
            'metadata' => ['provider' => 'rule_based'],
        ]);

        $status = $response['confidence'] < 0.50 ? 'human_required' : 'ai_handled';

        $conversation->forceFill([
            'status' => $status,
            'last_message_at' => Carbon::now(),
        ])->save();

        event(new MessageCreated(
            agencyId: $aiMessage->agency_id,
            conversationId: $aiMessage->conversation_id,
            messageId: $aiMessage->id,
            senderType: $aiMessage->sender_type,
        ));

        event(new ConversationStatusChanged(
            agencyId: $conversation->agency_id,
            conversationId: $conversation->id,
            status: $conversation->status,
        ));
    }
}
