<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard opens newest conversation with full message history by default', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $user->agency_id,
        'session_id' => 'visitor_history',
        'last_message_at' => now(),
    ]);

    Message::factory()->create([
        'agency_id' => $user->agency_id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'First message',
        'created_at' => now()->subMinute(),
    ]);

    Message::factory()->create([
        'agency_id' => $user->agency_id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'Second message',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('selectedConversation.id', $conversation->id)
            ->where('selectedConversation.messages.0.content', 'First message')
            ->where('selectedConversation.messages.1.content', 'Second message'),
        );
});
