<?php

use App\Services\Knowledge\EmbeddingService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

test('fallback embedding is normalized and 1536 dimensions', function () {
    Config::set('services.openai.api_key', '');

    $service = new EmbeddingService;
    $a = $service->embed('Hello world');
    $b = $service->embed('Hello world');

    expect($a)->toHaveCount(EmbeddingService::EMBEDDING_DIMENSIONS);
    $sum = 0.0;
    foreach ($a as $v) {
        $sum += $v * $v;
    }
    expect($sum)->toBeBetween(0.99, 1.01);
    expect($a)->toBe($b);
});

test('uses OpenAI when api key and successful response are present', function () {
    Config::set('services.openai.api_key', 'sk-test');
    Config::set('services.openai.base_url', 'https://api.openai.com/v1');
    Config::set('services.openai.embedding_model', 'text-embedding-3-small');

    $vector = array_fill(0, EmbeddingService::EMBEDDING_DIMENSIONS, 0.01);
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'data' => [
                ['embedding' => $vector],
            ],
        ], 200),
    ]);

    $service = new EmbeddingService;
    $out = $service->embed('query');

    expect($out)->toBe($vector);
});
