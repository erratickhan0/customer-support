<?php

namespace App\Jobs;

use App\Events\ConversationStatusChanged;
use App\Events\MessageCreated;
use App\Models\Agency;
use App\Models\Message;
use App\Services\AI\ConversationResponder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class HandleIncomingMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId) {}

    public function handle(ConversationResponder $responder): void
    {
        $userMessage = Message::query()
            ->with('conversation')
            ->find($this->messageId);

        if (! $userMessage || $userMessage->sender_type !== 'user') {
            return;
        }

        $conversation = $userMessage->conversation;
        $agency = Agency::query()->find($userMessage->agency_id);
        $provider = $agency?->ai_provider ?: 'openai';
        $response = $responder->generateForAgency(
            (int) $userMessage->agency_id,
            $userMessage->content,
            $provider,
        );

        $aiMessage = Message::query()->create([
            'agency_id' => $userMessage->agency_id,
            'conversation_id' => $userMessage->conversation_id,
            'sender_type' => 'ai',
            'content' => $response['reply'],
            'confidence' => $response['confidence'],
            'metadata' => $response['metadata'] ?? ['provider' => 'unknown'],
        ]);

        $threshold = (float) ($agency?->ai_confidence_threshold ?? 0.50);
        $autoHandoff = (bool) ($agency?->ai_auto_handoff ?? true);
        $needsHuman = $response['confidence'] < $threshold;
        $status = ($autoHandoff && $needsHuman) ? 'human_required' : 'ai_handled';

        $metadata = (array) ($aiMessage->metadata ?? []);
        $aiMessage->forceFill([
            'metadata' => array_merge($metadata, [
                'confidence_threshold' => $threshold,
                'auto_handoff' => $autoHandoff,
            ]),
        ])->save();

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
