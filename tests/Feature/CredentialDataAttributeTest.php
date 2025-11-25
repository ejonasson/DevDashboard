<?php

declare(strict_types=1);

use App\Enums\Integrations;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

it('encrypts data when setting the data attribute', function () {
    $testData = ['api_token' => 'secret-token-123', 'username' => 'testuser'];

    $credential = new Credential([
        'integration' => Integrations::GitHub,
        'data' => $testData,
    ]);
    $credential->save();

    // Refresh to get the raw database value
    $credential->refresh();

    // The raw 'encrypted_data' column should be encrypted (not plaintext)
    $rawData = $credential->getAttributes()['encrypted_data'];
    expect($rawData)->not->toContain('secret-token-123');
    expect($rawData)->not->toContain('testuser');
});

it('decrypts data when getting the data attribute', function () {
    $testData = ['api_token' => 'secret-token-456', 'username' => 'anotheruser'];

    $credential = Credential::create([
        'integration' => Integrations::Jira,
        'data' => $testData,
    ]);

    // Retrieve the credential and access the data attribute
    $retrieved = Credential::find($credential->id);

    expect($retrieved->data)->toBe($testData);
});

it('handles complex nested data structures in data attribute', function () {
    $complexData = [
        'credentials' => [
            'token' => 'ghp_1234567890',
            'expires_at' => '2025-12-31T23:59:59Z',
        ],
        'metadata' => [
            'scopes' => ['repo', 'user', 'admin:org'],
            'permissions' => [
                'read' => true,
                'write' => true,
            ],
        ],
        'user_info' => [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ],
    ];

    $credential = Credential::create([
        'integration' => Integrations::GitHub,
        'data' => $complexData,
    ]);

    $retrieved = Credential::find($credential->id);

    expect($retrieved->data)->toBe($complexData);
    expect($retrieved->data['credentials']['token'])->toBe('ghp_1234567890');
    expect($retrieved->data['metadata']['scopes'])->toHaveCount(3);
});

it('handles empty data arrays in data attribute', function () {
    $emptyData = [];

    $credential = Credential::create([
        'integration' => Integrations::Aws,
        'data' => $emptyData,
    ]);

    $retrieved = Credential::find($credential->id);

    expect($retrieved->data)->toBe($emptyData);
});

it('properly encrypts sensitive data so it cannot be read without decryption', function () {
    $secretData = [
        'api_token' => 'super-secret-token-12345',
        'password' => 'MySecurePassword123!',
        'private_key' => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASC...',
    ];

    $credential = Credential::create([
        'integration' => Integrations::GitHub,
        'data' => $secretData,
    ]);

    // Get the raw encrypted value from the database
    $rawData = $credential->getAttributes()['encrypted_data'];

    // The encrypted data should not contain any of the plaintext secrets
    expect($rawData)->not->toContain('super-secret-token-12345');
    expect($rawData)->not->toContain('MySecurePassword123!');
    expect($rawData)->not->toContain('private_key');
    expect($rawData)->not->toContain('api_token');
    expect($rawData)->not->toContain('password');
});

it('updates encrypted data when changing the data attribute', function () {
    $initialData = ['token' => 'initial-token'];

    $credential = Credential::create([
        'integration' => Integrations::Jira,
        'data' => $initialData,
    ]);

    expect($credential->data)->toBe($initialData);

    // Update the data
    $updatedData = ['token' => 'updated-token', 'new_field' => 'new-value'];
    $credential->data = $updatedData;
    $credential->save();

    // Retrieve fresh from database
    $retrieved = Credential::find($credential->id);

    expect($retrieved->data)->toBe($updatedData);
    expect($retrieved->data['token'])->toBe('updated-token');
    expect($retrieved->data['new_field'])->toBe('new-value');
});

it('can decrypt data that was manually encrypted using Crypt', function () {
    $testData = ['key' => 'value', 'secret' => 'password123'];
    $encrypted = Crypt::encryptString(json_encode($testData));

    // Manually insert encrypted data into database
    $credential = new Credential([
        'integration' => Integrations::Aws,
    ]);
    $credential->setRawAttributes([
        'integration' => Integrations::Aws->value,
        'encrypted_data' => $encrypted,
    ]);
    $credential->save();

    // The data attribute should decrypt it properly
    $retrieved = Credential::find($credential->id);

    expect($retrieved->data)->toBe($testData);
});
