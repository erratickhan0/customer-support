<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list inbox conversations for their agency', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $user->agency_id,
    ]);

    Message::factory()->create([
        'agency_id' => $user->agency_id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
    ]);

    $response = $this->actingAs($user)->getJson(route('api.inbox.conversations.index'));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.id', $conversation->id);
});

test('agent can reply to conversation in same agency', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $user->agency_id,
        'status' => 'human_required',
    ]);

    $response = $this->actingAs($user)->postJson(
        route('api.inbox.conversations.messages.store', $conversation),
        ['content' => 'I am taking over this case.', 'status' => 'human_active'],
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.sender_type', 'agent');

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'sender_type' => 'agent',
    ]);

    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'status' => 'human_active',
        'assigned_user_id' => $user->id,
    ]);
});
