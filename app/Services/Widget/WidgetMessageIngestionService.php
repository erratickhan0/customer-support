<?php

namespace App\Services\Widget;

use App\Events\MessageCreated;
use App\Jobs\HandleIncomingMessageJob;
use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WidgetMessageIngestionService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{conversation: Conversation, message: Message}
     */
    public function ingest(Agency $agency, array $payload): array
    {
        return DB::transaction(function () use ($agency, $payload): array {
            $conversation = Conversation::query()->firstOrCreate(
                [
                    'agency_id' => $agency->id,
                    'session_id' => $payload['session_id'],
                ],
                [
                    'status' => 'ai_handled',
                ],
            );

            $message = Message::query()->create([
                'agency_id' => $agency->id,
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'content' => $payload['message'],
                'metadata' => $payload['metadata'] ?? null,
            ]);

            $conversation->forceFill([
                'last_message_at' => Carbon::now(),
            ])->save();

            event(new MessageCreated(
                agencyId: $message->agency_id,
                conversationId: $message->conversation_id,
                messageId: $message->id,
                senderType: $message->sender_type,
            ));

            HandleIncomingMessageJob::dispatch($message->id)->onQueue('ai');

            return [
                'conversation' => $conversation,
                'message' => $message,
            ];
        });
    }
}
