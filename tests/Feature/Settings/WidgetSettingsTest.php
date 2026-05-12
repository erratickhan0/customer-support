<?php

use App\Models\AgencyApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('widget settings page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('widget-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Widget')
            ->where('widget.has_api_key', false)
            ->where('widget.api_key', null)
            ->where('widget.embed_code', null),
        );
});

test('widget key can be generated and shown once', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('widget-settings.store'));

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('widget_api_key')
        ->assertRedirect(route('widget-settings.edit'));

    $plainApiKey = session('widget_api_key');

    expect($plainApiKey)->toStartWith('wk_live_');

    $this->assertDatabaseHas('agency_api_keys', [
        'agency_id' => $user->agency_id,
        'name' => 'Website Widget',
        'key_hash' => hash('sha256', $plainApiKey),
        'is_active' => true,
    ]);

    $this->get(route('widget-settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('widget.api_key', $plainApiKey)
            ->where('widget.embed_code', '<script src="'.url('/widget.js').'" data-api-key="'.$plainApiKey.'" async></script>'),
        );
});

test('generating a widget key rotates previous active keys', function () {
    $user = User::factory()->create();
    $oldKey = AgencyApiKey::factory()->create([
        'agency_id' => $user->agency_id,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('widget-settings.store'))
        ->assertRedirect(route('widget-settings.edit'));

    expect($oldKey->refresh()->is_active)->toBeFalse();

    expect(AgencyApiKey::query()
        ->where('agency_id', $user->agency_id)
        ->where('is_active', true)
        ->count())->toBe(1);
});
