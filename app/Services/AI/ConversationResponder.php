<?php

namespace App\Services\AI;

use App\Services\Knowledge\RetrievalService;
use Illuminate\Support\Facades\Http;
use stdClass;
use Throwable;

class ConversationResponder
{
    public function __construct(
        protected RetrievalService $retrieval,
        protected RuleBasedResponder $ruleBased,
    ) {}

    /**
     * @return array{reply: string, confidence: float, metadata: array<string, mixed>}
     */
    public function generateForAgency(int $agencyId, string $userMessage, string $preferredProvider = 'openai'): array
    {
        if ($preferredProvider === 'rule_based') {
            $rule = $this->ruleBased->generate($userMessage);

            return [
                'reply' => $rule['reply'],
                'confidence' => $rule['confidence'],
                'metadata' => [
                    'mode' => 'rule_based_provider',
                    'provider' => 'rule_based',
                ],
            ];
        }

        try {
            $contextRows = $this->retrieval->retrieveContext($agencyId, $userMessage, 5);
        } catch (Throwable $e) {
            report($e);
            $rule = $this->ruleBased->generate($userMessage);

            return [
                'reply' => $rule['reply'],
                'confidence' => $rule['confidence'],
                'metadata' => [
                    'mode' => 'retrieval_unavailable',
                    'provider' => 'rule_based_fallback',
                ],
            ];
        }

        if ($contextRows === []) {
            $rule = $this->ruleBased->generate($userMessage);

            return [
                'reply' => $rule['reply'],
                'confidence' => $rule['confidence'],
                'metadata' => [
                    'mode' => 'rule_based',
                    'provider' => 'rule_based',
                ],
            ];
        }

        $apiKey = (string) config('services.openai.api_key', '');

        if ($apiKey === '') {
            $reply = $this->formatOfflineRag($contextRows, $userMessage);
            $confidence = $this->confidenceFromDistances($contextRows, 0.5);

            return [
                'reply' => $reply,
                'confidence' => $confidence,
                'metadata' => [
                    'mode' => 'offline_rag',
                    'provider' => 'deterministic',
                    'citations' => $this->citations($contextRows),
                ],
            ];
        }

        try {
            $context = $this->formatContext($contextRows);
            $baseUrl = (string) config('services.openai.base_url', 'https://api.openai.com/v1');
            $model = (string) config('services.openai.chat_model', 'gpt-4o-mini');

            $org = (string) config('services.openai.organization', '');

            $request = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout(60)
                ->connectTimeout(10)
                ->when($org !== '', fn ($http) => $http->withHeader('OpenAI-Organization', $org))
                ->post("{$baseUrl}/chat/completions", [
                    'model' => $model,
                    'temperature' => 0.2,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a customer support assistant. Answer only using the provided context chunks. If the context does not contain enough information, say you are not sure and offer to connect the user with a human agent. Be concise, friendly, and avoid inventing product facts.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Context from knowledge base:\n\n{$context}\n\n---\n\nUser message:\n{$userMessage}",
                        ],
                    ],
                ]);

            if (! $request->successful()) {
                throw new \RuntimeException('OpenAI chat request failed: '.$request->body());
            }

            $text = (string) $request->json('choices.0.message.content', '');
            $text = trim($text);
            if ($text === '') {
                throw new \RuntimeException('OpenAI returned empty content.');
            }

            $baseConfidence = $this->confidenceFromDistances($contextRows, 0.25);
            $lowSignal = $this->replyImpliesNoAnswer($text);
            $confidence = $lowSignal ? max(0.2, $baseConfidence * 0.5) : $baseConfidence;

            return [
                'reply' => $text,
                'confidence' => $confidence,
                'metadata' => [
                    'mode' => 'openai_rag',
                    'provider' => 'openai',
                    'model' => $model,
                    'citations' => $this->citations($contextRows),
                ],
            ];
        } catch (Throwable $e) {
            report($e);
            $rule = $this->ruleBased->generate($userMessage);

            return [
                'reply' => $rule['reply'],
                'confidence' => $rule['confidence'],
                'metadata' => [
                    'mode' => 'openai_rag_error',
                    'provider' => 'rule_based_fallback',
                    'error' => 'openai_unavailable',
                ],
            ];
        }
    }

    /**
     * @param  array<int, stdClass>  $rows
     */
    protected function formatContext(array $rows): string
    {
        $parts = [];
        foreach ($rows as $i => $row) {
            $n = (int) $i + 1;
            $c = (string) ($row->content ?? '');

            $parts[] = "Chunk {$n}:\n".trim($c);
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * @param  array<int, stdClass>  $rows
     * @return array<int, array{document_id: int, chunk_index: int}>
     */
    protected function citations(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'document_id' => (int) ($row->document_id ?? 0),
                'chunk_index' => (int) ($row->chunk_index ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, stdClass>  $rows
     */
    protected function formatOfflineRag(array $rows, string $userMessage): string
    {
        $summaries = [];
        foreach (array_slice($rows, 0, 2) as $r) {
            $summaries[] = trim((string) ($r->content ?? ''));
        }
        $block = implode("\n\n", array_filter($summaries));

        return "I found the following in our knowledge base (set OPENAI_API_KEY for a fuller AI answer):\n\n".$block;
    }

    /**
     * @param  array<int, stdClass>  $rows
     */
    protected function confidenceFromDistances(array $rows, float $minFloor): float
    {
        $dists = [];
        foreach ($rows as $row) {
            if (property_exists($row, 'distance') && is_numeric($row->distance)) {
                $dists[] = (float) $row->distance;
            }
        }

        if ($dists === []) {
            return 0.65;
        }

        $mean = array_sum($dists) / count($dists);
        $score = 1.0 - min(1.0, $mean * 0.4);

        return max($minFloor, min(0.9, $score));
    }

    protected function replyImpliesNoAnswer(string $text): bool
    {
        $l = mb_strtolower($text);

        return str_contains($l, "don't have") ||
            str_contains($l, 'do not have') ||
            str_contains($l, "don't know") ||
            str_contains($l, 'not sure') ||
            str_contains($l, 'cannot find') ||
            str_contains($l, "can't find");
    }
}
