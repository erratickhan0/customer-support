<?php

use App\Jobs\HandleIncomingMessageJob;
use App\Models\Agency;
use App\Models\AgencyApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('widget message is ingested for a valid api key', function () {
    Bus::fake();

    $agency = Agency::factory()->create();
    $rawKey = 'wk_live_valid_example_key_1234567890';

    AgencyApiKey::query()->create([
        'agency_id' => $agency->id,
        'name' => 'Website Key',
        'key_hash' => hash('sha256', $rawKey),
        'is_active' => true,
    ]);

    $response = $this->postJson(route('api.widget.messages.store'), [
        'api_key' => $rawKey,
        'session_id' => 'session_abc123',
        'message' => 'Hello support, I need billing help.',
        'metadata' => [
            'source' => 'widget',
            'locale' => 'en',
        ],
    ]);

    $response->assertStatus(202)
        ->assertJsonStructure(['conversation_id', 'message_id', 'status']);

    $this->assertDatabaseHas('conversations', [
        'agency_id' => $agency->id,
        'session_id' => 'session_abc123',
    ]);

    $this->assertDatabaseHas('messages', [
        'agency_id' => $agency->id,
        'sender_type' => 'user',
        'content' => 'Hello support, I need billing help.',
    ]);

    Bus::assertDispatched(HandleIncomingMessageJob::class);
});

test('widget message returns unauthorized for invalid api key', function () {
    $response = $this->postJson(route('api.widget.messages.store'), [
        'api_key' => 'wk_invalid_key_123456789012345',
        'session_id' => 'session_unauthorized',
        'message' => 'Test message',
    ]);

    $response->assertUnauthorized();
});
