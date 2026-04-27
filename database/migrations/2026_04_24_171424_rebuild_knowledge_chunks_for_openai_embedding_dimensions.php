<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebuilds `knowledge_chunks` for OpenAI text-embedding-3-small (1536 dimensions).
     * Re-run knowledge document processing after this migration on existing data.
     */
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::connection('pgsql_vector')->statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::connection('pgsql_vector')->statement('DROP TABLE IF EXISTS knowledge_chunks');

        DB::connection('pgsql_vector')->statement('
            CREATE TABLE knowledge_chunks (
                id BIGSERIAL PRIMARY KEY,
                agency_id BIGINT NOT NULL,
                document_id BIGINT NOT NULL,
                chunk_index INTEGER NOT NULL,
                content TEXT NOT NULL,
                embedding vector(1536) NOT NULL,
                metadata JSONB NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
            )
        ');

        DB::connection('pgsql_vector')->statement('
            CREATE INDEX IF NOT EXISTS idx_knowledge_chunks_agency_document
            ON knowledge_chunks (agency_id, document_id)
        ');

        DB::connection('pgsql_vector')->statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_chunks_unique
            ON knowledge_chunks (agency_id, document_id, chunk_index)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        DB::connection('pgsql_vector')->statement('DROP TABLE IF EXISTS knowledge_chunks');

        DB::connection('pgsql_vector')->statement('
            CREATE TABLE knowledge_chunks (
                id BIGSERIAL PRIMARY KEY,
                agency_id BIGINT NOT NULL,
                document_id BIGINT NOT NULL,
                chunk_index INTEGER NOT NULL,
                content TEXT NOT NULL,
                embedding vector(8) NOT NULL,
                metadata JSONB NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
            )
        ');

        DB::connection('pgsql_vector')->statement('
            CREATE INDEX IF NOT EXISTS idx_knowledge_chunks_agency_document
            ON knowledge_chunks (agency_id, document_id)
        ');

        DB::connection('pgsql_vector')->statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_knowledge_chunks_unique
            ON knowledge_chunks (agency_id, document_id, chunk_index)
        ');
    }
};
