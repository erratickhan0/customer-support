<?php

use App\Jobs\HandleIncomingMessageJob;
use App\Models\Agency;
use App\Models\AgencyApiKey;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('widget conversation can be viewed with matching api key and session', function () {
    $agency = Agency::factory()->create();
    $rawKey = 'wk_live_valid_polling_key_1234567890';

    AgencyApiKey::query()->create([
        'agency_id' => $agency->id,
        'name' => 'Website Key',
        'key_hash' => hash('sha256', $rawKey),
        'is_active' => true,
    ]);

    $conversation = Conversation::factory()->create([
        'agency_id' => $agency->id,
        'session_id' => 'session_poll_123',
        'status' => 'ai_handled',
    ]);

    Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'What are your shipping options?',
        'created_at' => now()->subMinute(),
    ]);

    Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'ai',
        'content' => 'We offer standard and express shipping.',
        'confidence' => 0.84,
        'created_at' => now(),
    ]);

    $this->getJson(route('api.widget.conversations.show', [
        'conversation' => $conversation->id,
        'api_key' => $rawKey,
        'session_id' => 'session_poll_123',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('conversation.id', $conversation->id)
        ->assertJsonPath('conversation.status', 'ai_handled')
        ->assertJsonPath('messages.0.sender_type', 'user')
        ->assertJsonPath('messages.1.sender_type', 'ai')
        ->assertJsonPath('messages.1.confidence', 0.84);
});

test('widget conversation is hidden from wrong api key or session', function () {
    $agency = Agency::factory()->create();
    $rawKey = 'wk_live_valid_polling_key_1234567890';

    AgencyApiKey::query()->create([
        'agency_id' => $agency->id,
        'name' => 'Website Key',
        'key_hash' => hash('sha256', $rawKey),
        'is_active' => true,
    ]);

    $conversation = Conversation::factory()->create([
        'agency_id' => $agency->id,
        'session_id' => 'session_poll_123',
    ]);

    $this->getJson(route('api.widget.conversations.show', [
        'conversation' => $conversation->id,
        'api_key' => $rawKey,
        'session_id' => 'wrong_session',
    ]))->assertNotFound();

    $this->getJson(route('api.widget.conversations.show', [
        'conversation' => $conversation->id,
        'api_key' => 'wk_live_wrong_polling_key_1234567890',
        'session_id' => 'session_poll_123',
    ]))->assertNotFound();
});

test('ingested widget conversation can be polled', function () {
    Bus::fake();

    $agency = Agency::factory()->create();
    $rawKey = 'wk_live_valid_ingest_poll_key_1234567890';

    AgencyApiKey::query()->create([
        'agency_id' => $agency->id,
        'name' => 'Website Key',
        'key_hash' => hash('sha256', $rawKey),
        'is_active' => true,
    ]);

    $ingestResponse = $this->postJson(route('api.widget.messages.store'), [
        'api_key' => $rawKey,
        'session_id' => 'session_ingest_poll_123',
        'message' => 'Hello from the embedded widget.',
        'metadata' => [
            'source' => 'widget',
        ],
    ]);

    $ingestResponse->assertStatus(202);
    Bus::assertDispatched(HandleIncomingMessageJob::class);

    $this->getJson(route('api.widget.conversations.show', [
        'conversation' => $ingestResponse->json('conversation_id'),
        'api_key' => $rawKey,
        'session_id' => 'session_ingest_poll_123',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('messages.0.sender_type', 'user')
        ->assertJsonPath('messages.0.content', 'Hello from the embedded widget.');
});
