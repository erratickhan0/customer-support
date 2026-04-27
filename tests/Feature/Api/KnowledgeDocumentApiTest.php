<?php

use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('authenticated user can create a knowledge document and dispatch processing', function () {
    Bus::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.knowledge.documents.store'), [
        'title' => 'Billing Policy',
        'content' => str_repeat('Our billing cycle is monthly. ', 8),
        'metadata' => ['source' => 'faq'],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.title', 'Billing Policy')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('knowledge_documents', [
        'agency_id' => $user->agency_id,
        'created_by_user_id' => $user->id,
        'title' => 'Billing Policy',
    ]);

    Bus::assertDispatched(ProcessKnowledgeDocumentJob::class);
});

test('authenticated user can list only their agency knowledge documents', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    KnowledgeDocument::factory()->create([
        'agency_id' => $user->agency_id,
        'created_by_user_id' => $user->id,
        'title' => 'Visible Document',
    ]);

    KnowledgeDocument::factory()->create([
        'agency_id' => $otherUser->agency_id,
        'created_by_user_id' => $otherUser->id,
        'title' => 'Hidden Document',
    ]);

    $response = $this->actingAs($user)->getJson(route('api.knowledge.documents.index'));

    $response->assertSuccessful()
        ->assertJsonFragment(['title' => 'Visible Document'])
        ->assertJsonMissing(['title' => 'Hidden Document']);
});
