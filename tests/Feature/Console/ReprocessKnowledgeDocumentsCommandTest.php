<?php

use App\Jobs\ProcessKnowledgeDocumentJob;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('knowledge reprocess command queues all documents by default', function () {
    Bus::fake();

    $creator = User::factory()->create();
    KnowledgeDocument::factory()->count(3)->create([
        'agency_id' => $creator->agency_id,
        'created_by_user_id' => $creator->id,
    ]);

    $this->artisan('knowledge:reprocess')
        ->expectsOutput('Queued 3 knowledge document(s) for reprocessing.')
        ->assertSuccessful();

    Bus::assertDispatched(ProcessKnowledgeDocumentJob::class, 3);
});

test('knowledge reprocess command supports agency and status filters', function () {
    Bus::fake();

    $agencyAUser = User::factory()->create();
    $agencyBUser = User::factory()->create();

    $docAReady = KnowledgeDocument::factory()->create([
        'agency_id' => $agencyAUser->agency_id,
        'created_by_user_id' => $agencyAUser->id,
        'status' => 'ready',
    ]);
    KnowledgeDocument::factory()->create([
        'agency_id' => $agencyAUser->agency_id,
        'created_by_user_id' => $agencyAUser->id,
        'status' => 'failed',
    ]);
    KnowledgeDocument::factory()->create([
        'agency_id' => $agencyBUser->agency_id,
        'created_by_user_id' => $agencyBUser->id,
        'status' => 'ready',
    ]);

    $this->artisan('knowledge:reprocess', [
        '--agency' => (string) $agencyAUser->agency_id,
        '--status' => ['ready'],
    ])
        ->expectsOutput('Queued 1 knowledge document(s) for reprocessing.')
        ->assertSuccessful();

    Bus::assertDispatched(ProcessKnowledgeDocumentJob::class, function ($job) use ($docAReady) {
        return $job->documentId === $docAReady->id;
    });
    Bus::assertDispatchedTimes(ProcessKnowledgeDocumentJob::class, 1);
});
