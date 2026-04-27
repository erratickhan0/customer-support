<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\Knowledge\DocumentChunker;
use App\Services\Knowledge\EmbeddingService;
use App\Services\Knowledge\PgVectorStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessKnowledgeDocumentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $documentId) {}

    public function handle(
        DocumentChunker $documentChunker,
        EmbeddingService $embeddingService,
        PgVectorStore $pgVectorStore,
    ): void {
        $document = KnowledgeDocument::query()->find($this->documentId);

        if (! $document) {
            return;
        }

        $document->forceFill(['status' => 'processing'])->save();

        try {
            $chunks = $documentChunker->chunk($document->content);

            $pgVectorStore->deleteDocumentChunks($document->agency_id, $document->id);

            foreach ($chunks as $index => $chunk) {
                $embedding = $embeddingService->embed($chunk);
                $pgVectorStore->storeChunk(
                    agencyId: $document->agency_id,
                    documentId: $document->id,
                    chunkIndex: $index,
                    content: $chunk,
                    embedding: $embedding,
                    metadata: ['title' => $document->title],
                );
            }

            $document->forceFill([
                'status' => 'ready',
                'chunk_count' => count($chunks),
            ])->save();
        } catch (Throwable $exception) {
            $document->forceFill(['status' => 'failed'])->save();
            report($exception);
        }
    }

    public function failed(): void
    {
        KnowledgeDocument::query()
            ->whereKey($this->documentId)
            ->update(['status' => 'failed']);
    }
}
