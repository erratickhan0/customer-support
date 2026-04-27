<?php

namespace App\Services\Knowledge;

class RetrievalService
{
    public function __construct(
        protected EmbeddingService $embeddingService,
        protected PgVectorStore $pgVectorStore,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function retrieveContext(int $agencyId, string $query, int $limit = 5): array
    {
        $embedding = $this->embeddingService->embed($query);

        return $this->pgVectorStore->search($agencyId, $embedding, $limit);
    }
}
