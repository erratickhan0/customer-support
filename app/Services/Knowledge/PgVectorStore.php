<?php

namespace App\Services\Knowledge;

use Illuminate\Support\Facades\DB;

class PgVectorStore
{
    /**
     * @param  array<int, float>  $embedding
     * @param  array<string, mixed>|null  $metadata
     */
    public function storeChunk(
        int $agencyId,
        int $documentId,
        int $chunkIndex,
        string $content,
        array $embedding,
        ?array $metadata = null,
    ): void {
        DB::connection('pgsql_vector')->table('knowledge_chunks')->updateOrInsert(
            [
                'agency_id' => $agencyId,
                'document_id' => $documentId,
                'chunk_index' => $chunkIndex,
            ],
            [
                'content' => $content,
                'embedding' => '['.implode(',', $embedding).']',
                'metadata' => $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            ],
        );
    }

    public function deleteDocumentChunks(int $agencyId, int $documentId): void
    {
        DB::connection('pgsql_vector')
            ->table('knowledge_chunks')
            ->where('agency_id', $agencyId)
            ->where('document_id', $documentId)
            ->delete();
    }

    /**
     * @param  array<int, float>  $queryEmbedding
     * @return array<int, array<string, mixed>>
     */
    public function search(int $agencyId, array $queryEmbedding, int $limit = 5): array
    {
        $vectorLiteral = '['.implode(',', $queryEmbedding).']';

        return DB::connection('pgsql_vector')
            ->select(
                '
                SELECT document_id, chunk_index, content, metadata,
                    (embedding <=> ?::vector) AS distance
                FROM knowledge_chunks
                WHERE agency_id = ?
                ORDER BY embedding <=> ?::vector
                LIMIT ?
                ',
                [$vectorLiteral, $agencyId, $vectorLiteral, $limit],
            );
    }
}
