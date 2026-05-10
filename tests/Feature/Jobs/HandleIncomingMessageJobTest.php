<?php

use App\Jobs\HandleIncomingMessageJob;
use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Knowledge\RetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('job uses openai rag response when api key is configured', function () {
    Config::set('services.openai.api_key', 'sk-test');
    Config::set('services.openai.base_url', 'https://api.openai.com/v1');
    Config::set('services.openai.chat_model', 'gpt-4o-mini');

    app()->bind(RetrievalService::class, function () {
        return new class extends RetrievalService
        {
            public function __construct() {}

            public function retrieveContext(int $agencyId, string $query, int $limit = 5): array
            {
                $row = new stdClass;
                $row->document_id = 10;
                $row->chunk_index = 1;
                $row->content = 'Customers can request cancellation within 14 days.';
                $row->distance = 0.2;

                return [$row];
            }
        };
    });

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'You can request cancellation within 14 days of purchase.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $agency = Agency::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $agency->id,
        'status' => 'human_required',
    ]);
    $message = Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'How do I cancel?',
    ]);

    app()->call([new HandleIncomingMessageJob($message->id), 'handle']);

    $aiMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', 'ai')
        ->latest('id')
        ->first();

    expect($aiMessage)->not->toBeNull();
    expect($aiMessage?->content)->toContain('14 days');
    expect($aiMessage?->metadata['provider'])->toBe('openai');
    expect($aiMessage?->metadata['mode'])->toBe('openai_rag');

    $conversation->refresh();
    expect($conversation->status)->toBe('ai_handled');
});

test('job falls back to rule based response when openai chat fails', function () {
    Config::set('services.openai.api_key', 'sk-test');
    Config::set('services.openai.base_url', 'https://api.openai.com/v1');

    app()->bind(RetrievalService::class, function () {
        return new class extends RetrievalService
        {
            public function __construct() {}

            public function retrieveContext(int $agencyId, string $query, int $limit = 5): array
            {
                $row = new stdClass;
                $row->document_id = 22;
                $row->chunk_index = 0;
                $row->content = 'Refund policy exists in billing docs.';
                $row->distance = 0.1;

                return [$row];
            }
        };
    });

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'upstream down'], 500),
    ]);

    $agency = Agency::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $agency->id,
        'status' => 'ai_handled',
    ]);
    $message = Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'I need a refund now.',
    ]);

    app()->call([new HandleIncomingMessageJob($message->id), 'handle']);

    $aiMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', 'ai')
        ->latest('id')
        ->first();

    expect($aiMessage)->not->toBeNull();
    expect($aiMessage?->metadata['provider'])->toBe('rule_based_fallback');
    expect($aiMessage?->metadata['mode'])->toBe('openai_rag_error');
    expect($aiMessage?->confidence)->toBeLessThan(0.5);

    $conversation->refresh();
    expect($conversation->status)->toBe('human_required');
});

test('job uses offline rag path when openai api key is missing', function () {
    Config::set('services.openai.api_key', '');

    app()->bind(RetrievalService::class, function () {
        return new class extends RetrievalService
        {
            public function __construct() {}

            public function retrieveContext(int $agencyId, string $query, int $limit = 5): array
            {
                $row = new stdClass;
                $row->document_id = 8;
                $row->chunk_index = 2;
                $row->content = 'Support hours are Monday-Friday 9am-6pm.';
                $row->distance = 0.4;

                return [$row];
            }
        };
    });

    Http::fake();

    $agency = Agency::factory()->create();
    $conversation = Conversation::factory()->create([
        'agency_id' => $agency->id,
        'status' => 'human_required',
    ]);
    $message = Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'What are support hours?',
    ]);

    app()->call([new HandleIncomingMessageJob($message->id), 'handle']);

    $aiMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', 'ai')
        ->latest('id')
        ->first();

    expect($aiMessage)->not->toBeNull();
    expect($aiMessage?->content)->toContain('knowledge base');
    expect($aiMessage?->metadata['provider'])->toBe('deterministic');
    expect($aiMessage?->metadata['mode'])->toBe('offline_rag');

    Http::assertNothingSent();
});

test('job respects tenant confidence threshold and auto handoff toggle', function () {
    Config::set('services.openai.api_key', 'sk-test');
    Config::set('services.openai.base_url', 'https://api.openai.com/v1');

    app()->bind(RetrievalService::class, function () {
        return new class extends RetrievalService
        {
            public function __construct() {}

            public function retrieveContext(int $agencyId, string $query, int $limit = 5): array
            {
                $row = new stdClass;
                $row->document_id = 12;
                $row->chunk_index = 0;
                $row->content = 'Cancellation can be processed by support.';
                $row->distance = 0.95;

                return [$row];
            }
        };
    });

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => 'You can contact support to process cancellation.',
                    ],
                ],
            ],
        ], 200),
    ]);

    $agency = Agency::factory()->create([
        'ai_confidence_threshold' => 0.95,
        'ai_auto_handoff' => false,
    ]);

    $conversation = Conversation::factory()->create(['agency_id' => $agency->id]);
    $message = Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
    ]);

    app()->call([new HandleIncomingMessageJob($message->id), 'handle']);

    $conversation->refresh();
    expect($conversation->status)->toBe('ai_handled');

    $aiMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', 'ai')
        ->latest('id')
        ->first();

    expect($aiMessage?->metadata['confidence_threshold'])->toBe(0.95);
    expect($aiMessage?->metadata['auto_handoff'])->toBeFalse();
});

test('job uses tenant rule based provider preference', function () {
    Config::set('services.openai.api_key', 'sk-test');
    Http::fake();

    $agency = Agency::factory()->create([
        'ai_provider' => 'rule_based',
    ]);
    $conversation = Conversation::factory()->create(['agency_id' => $agency->id]);
    $message = Message::factory()->create([
        'agency_id' => $agency->id,
        'conversation_id' => $conversation->id,
        'sender_type' => 'user',
        'content' => 'Can I request a refund?',
    ]);

    app()->call([new HandleIncomingMessageJob($message->id), 'handle']);

    $aiMessage = Message::query()
        ->where('conversation_id', $conversation->id)
        ->where('sender_type', 'ai')
        ->latest('id')
        ->first();

    expect($aiMessage)->not->toBeNull();
    expect($aiMessage?->metadata['provider'])->toBe('rule_based');
    expect($aiMessage?->metadata['mode'])->toBe('rule_based_provider');

    Http::assertNothingSent();
});
