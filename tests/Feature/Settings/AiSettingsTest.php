<?php

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('ai settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('ai-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/AiSettings')
            ->where('settings.ai_provider', 'openai')
            ->where('settings.ai_confidence_threshold', 0.5)
            ->where('settings.ai_auto_handoff', true),
        );
});

test('ai settings can be updated for users agency', function () {
    $user = User::factory()->create();
    $otherAgency = Agency::factory()->create([
        'ai_provider' => 'openai',
        'ai_confidence_threshold' => 0.5,
        'ai_auto_handoff' => true,
    ]);

    $response = $this->actingAs($user)->put(route('ai-settings.update'), [
        'ai_provider' => 'rule_based',
        'ai_confidence_threshold' => 0.78,
        'ai_auto_handoff' => false,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('ai-settings.edit'));

    $user->agency->refresh();
    $otherAgency->refresh();

    expect($user->agency->ai_provider)->toBe('rule_based');
    expect($user->agency->ai_confidence_threshold)->toBe(0.78);
    expect($user->agency->ai_auto_handoff)->toBeFalse();

    expect($otherAgency->ai_provider)->toBe('openai');
    expect($otherAgency->ai_confidence_threshold)->toBe(0.5);
    expect($otherAgency->ai_auto_handoff)->toBeTrue();
});

test('ai settings update validates confidence threshold bounds', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('ai-settings.edit'))
        ->put(route('ai-settings.update'), [
            'ai_provider' => 'openai',
            'ai_confidence_threshold' => 1.5,
            'ai_auto_handoff' => true,
        ]);

    $response
        ->assertSessionHasErrors('ai_confidence_threshold')
        ->assertRedirect(route('ai-settings.edit'));
});
