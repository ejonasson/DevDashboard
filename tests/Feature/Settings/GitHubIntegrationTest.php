<?php

declare(strict_types=1);

use App\Enums\Integrations;
use App\Models\Credential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('user can view GitHub settings on settings page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings')->assertOk()->assertSee('GitHub Integration');
});

test('user can connect with valid token', function () {
    Http::fake([
        'api.github.com/user' => Http::response([
            'login' => 'testuser',
            'id' => 12345,
            'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], 200),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_validtoken123456789')
        ->call('connect')
        ->assertHasNoErrors();

    $credential = Credential::query()->where('integration', Integrations::GitHub)->first();
    expect($credential)->not->toBeNull();

    // ensure token stored is not visible in raw encrypted data
    $raw = $credential->getAttributes()['encrypted_data'];
    expect($raw)->not->toContain('ghp_validtoken123456789');

    // decrypted data contains expected structure
    expect($credential->data['user']['login'] ?? null)->toBe('testuser');
});

test('user cannot connect with invalid token', function () {
    Http::fake([
        'api.github.com/user' => Http::response([], 401),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_invalidtoken123456789')
        ->call('connect')
        ->assertHasErrors(['token']);
});

test('handles 403 forbidden from GitHub API', function () {
    Http::fake([
        'api.github.com/user' => Http::response([], 403),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_validlookingtoken123456789')
        ->call('connect')
        ->assertHasErrors(['token']);
});

test('handles 429 rate limited from GitHub API', function () {
    Http::fake([
        'api.github.com/user' => Http::response([], 429),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_validlookingtoken123456789')
        ->call('connect')
        ->assertHasErrors(['token']);
});

test('handles network error gracefully', function () {
    Http::fake(function () {
        throw new Exception('Network error');
    });

    $this->actingAs(User::factory()->create());

    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_validlookingtoken123456789')
        ->call('connect')
        ->assertHasErrors(['token']);
});

test('displays connected state when credential exists', function () {
    $this->actingAs(User::factory()->create());

    Credential::factory()->create([
        'integration' => Integrations::GitHub,
    ]);

    Livewire::test('settings.⚡github')
        ->assertSet('isConnected', true)
        ->assertSee('Connected');
});

test('user can disconnect GitHub account', function () {
    $this->actingAs(User::factory()->create());

    Credential::factory()->create([
        'integration' => Integrations::GitHub,
    ]);

    Livewire::test('settings.⚡github')
        ->call('disconnect')
        ->assertHasNoErrors()
        ->assertSet('isConnected', false);

    expect(Credential::query()->where('integration', Integrations::GitHub)->exists())->toBeFalse();
});

test('unique constraint enforced by updating existing credential on reconnect', function () {
    Http::fake([
        'api.github.com/user' => Http::response([
            'login' => 'testuser',
            'id' => 12345,
            'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], 200),
    ]);

    $this->actingAs(User::factory()->create());

    // First connect
    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_token_one_123456789')
        ->call('connect')
        ->assertHasNoErrors();

    // Second connect (should update, not create another)
    Livewire::test('settings.⚡github')
        ->set('token', 'ghp_token_two_123456789')
        ->call('connect')
        ->assertHasNoErrors();

    expect(Credential::query()->where('integration', Integrations::GitHub)->count())->toBe(1);
});
