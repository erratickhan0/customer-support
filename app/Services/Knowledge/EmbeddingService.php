<?php

namespace App\Services\Knowledge;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class EmbeddingService
{
    public const EMBEDDING_DIMENSIONS = 1536;

    /**
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $key = (string) config('services.openai.api_key', '');

        if ($key !== '') {
            try {
                $embedding = $this->embedWithOpenAI($key, $text);
                if (count($embedding) === self::EMBEDDING_DIMENSIONS) {
                    return $embedding;
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $this->fallbackEmbedding($text);
    }

    /**
     * @return array<int, float>
     */
    protected function embedWithOpenAI(string $apiKey, string $text): array
    {
        $baseUrl = (string) config('services.openai.base_url', 'https://api.openai.com/v1');
        $model = (string) config('services.openai.embedding_model', 'text-embedding-3-small');

        $org = (string) config('services.openai.organization', '');

        $request = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(45)
            ->connectTimeout(10)
            ->when($org !== '', fn ($http) => $http->withHeader('OpenAI-Organization', $org))
            ->post("{$baseUrl}/embeddings", [
                'model' => $model,
                'input' => $text,
            ]);

        if (! $request->successful()) {
            throw new RuntimeException('OpenAI embeddings request failed: '.$request->body());
        }

        /** @var list<float>|null $raw */
        $raw = $request->json('data.0.embedding');
        if (! is_array($raw) || $raw === []) {
            throw new RuntimeException('OpenAI embeddings response missing embedding array.');
        }

        return array_map('floatval', $raw);
    }

    /**
     * Deterministic ~1536-dim vector for local / CI when no API key or HTTP fails.
     *
     * @return array<int, float>
     */
    protected function fallbackEmbedding(string $text): array
    {
        $hash = hash('sha512', $text, true);
        if ($hash === false) {
            $hash = str_repeat('x', 64);
        }

        $vector = [];
        $len = strlen($hash);

        for ($i = 0; $i < self::EMBEDDING_DIMENSIONS; $i++) {
            $a = ord($hash[$i % $len]);
            $b = ord($hash[($i + 17) % $len]);
            $t = (sin(($i + 1) * 0.1) * 0.1) + (($a * 256 + $b) / 65535.0);
            $vector[] = (float) ($t * 2 - 1);
        }

        $norm = 0.0;
        foreach ($vector as $v) {
            $norm += $v * $v;
        }
        $norm = sqrt($norm) ?: 1.0;
        $vector = array_map(fn (float $v): float => $v / $norm, $vector);

        return $vector;
    }
}
