# GitHub Personal Access Token Integration Plan

## Overview
Implement a GitHub Personal Access Token (PAT) management feature in the settings that allows users to connect their GitHub account by entering a PAT, validates it against the GitHub API, stores it encrypted in the Credential model, and displays connection status.

## Key Requirements
1. Check if a GitHub Credential exists in the database
2. Display text input for entering a Personal Access Token when not connected
3. Validate the PAT against GitHub's API
4. Store the validated token in the encrypted Credential model
5. Display connected state with user information

## Implementation Approach

### 1. Livewire Component Structure

**Component:** `App\Livewire\Settings\GitHub`
**Location:** `app/Livewire/Settings/GitHub.php`

**Properties:**
```php
public string $token = '';  // For PAT input
public bool $isConnected = false;  // Connection state
public ?string $username = null;  // GitHub username
public ?string $avatarUrl = null;  // GitHub avatar URL
```

**Methods:**
- `mount()` - Check for existing credential and load connection state
- `connect()` - Validate token and save to database
- `disconnect()` - Remove credential from database
- `validateGitHubToken(string $token)` - Private method to validate PAT via GitHub API

**Validation Rules:**
```php
$this->validate([
    'token' => [
        'required',
        'string',
        'min:20',
        'regex:/^(ghp_|github_pat_)[a-zA-Z0-9_]+$/',
    ],
]);
```

### 2. GitHub API Validation

**Endpoint:** `GET https://api.github.com/user`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/vnd.github+json
X-GitHub-Api-Version: 2022-11-28
User-Agent: DevDashboard-App
```

**Validation Logic:**
1. Make authenticated request to `/user` endpoint
2. Success (200): Token is valid, extract user data
3. Unauthorized (401/403): Invalid or revoked token
4. Handle network errors and rate limiting gracefully

**Error Handling:**
- 401: "Invalid GitHub token. Please check your token and try again."
- 403: "Token lacks required permissions or has been revoked."
- 429: "GitHub API rate limit exceeded. Please try again later."
- Network errors: "Unable to connect to GitHub. Please check your internet connection."

### 3. Data Storage Structure

Store in `Credential` model with `integration = Integrations::GitHub`:

```php
[
    'token' => 'ghp_xxxxxxxxxxxxxxxxxxxx',
    'user' => [
        'id' => 12345,
        'login' => 'username',
        'name' => 'User Name',
        'email' => 'user@example.com',
        'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
    ],
    'validated_at' => '2025-11-27T10:30:00Z',
]
```

**Security:** The entire `data` attribute is automatically encrypted via the Credential model's accessor/mutator.

### 4. UI States

#### State 1: Not Connected
- Display "GitHub Integration" heading
- Badge: `<flux:badge color="zinc">Not Connected</flux:badge>`
- Informational text about what the PAT is for
- Link to GitHub PAT creation page
- Password input field for token entry
- "Connect GitHub" button

#### State 2: Connecting (Loading)
- Button shows "Validating..." with disabled state
- Input field disabled
- Loading indicator active

#### State 3: Connected
- Badge: `<flux:badge color="green">Connected</flux:badge>`
- User card displaying:
  - Avatar image (rounded)
  - GitHub username
  - Connected timestamp
- "Disconnect" button (danger variant)

#### State 4: Error
- Error message displayed below input
- Token value retained (not cleared)
- Button re-enabled for retry

### 5. Component Implementation Pattern

Following existing codebase patterns:
- **Separate files:** PHP class in `app/Livewire/Settings/` + Blade view in `resources/views/livewire/settings/`
- **Use `mount()`** for initialization
- **Inline validation** in action methods
- **Flux UI components** for all UI elements
- **Use `<x-settings.layout>`** wrapper for consistent styling
- **Use `wire:model`** for two-way data binding
- **Use `wire:loading`** for loading states

### 6. Testing Strategy

**Test File:** `tests/Feature/Settings/GitHubIntegrationTest.php`

**Key Test Cases:**
- User can view GitHub settings page
- User can connect with valid token
- User cannot connect with invalid token
- User cannot connect with empty/malformed token
- Token is validated against GitHub API
- Handles API errors gracefully (401, 403, 429, network errors)
- Stores encrypted credential correctly
- Displays connected state when credential exists
- User can disconnect GitHub account
- Disconnecting removes credential from database
- Token is encrypted in database
- Enforces unique constraint on GitHub integration

**Testing Pattern (from existing tests):**
```php
test('user can connect github account with valid token', function () {
    Http::fake([
        'api.github.com/user' => Http::response([
            'login' => 'testuser',
            'id' => 12345,
            'avatar_url' => 'https://avatars.githubusercontent.com/u/12345',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], 200),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(GitHub::class)
        ->set('token', 'ghp_validtoken123456789')
        ->call('connect')
        ->assertHasNoErrors();

    expect(Credential::where('integration', Integrations::GitHub)->exists())->toBeTrue();
});
```

## Files to Create

1. **`app/Livewire/Settings/GitHub.php`**
   - Livewire component class with connection logic
   - GitHub API validation
   - Credential management

2. **`resources/views/livewire/settings/github.blade.php`**
   - Component template with all UI states
   - Forms, buttons, badges, user card
   - Loading and error states

3. **`tests/Feature/Settings/GitHubIntegrationTest.php`**
   - Comprehensive feature tests
   - HTTP mocking for GitHub API
   - Credential database assertions

4. **`database/factories/CredentialFactory.php`** (optional but recommended)
   - Factory for creating test credentials
   - Helpful for seeding and testing

## Files to Modify

1. **`resources/views/components/settings/⚡github.blade.php`**
   - Currently a placeholder single-file component
   - Will be replaced with `<livewire:settings.github/>` tag or removed entirely

2. **`resources/views/components/settings/layout.blade.php`**
   - Needs to be restored from git (was deleted)
   - Add "GitHub" link to navigation sidebar

## Implementation Steps

1. **Restore settings layout component** from git history
2. **Create Livewire component class** with properties, mount(), connect(), disconnect() methods
3. **Implement GitHub API validation** using Laravel HTTP facade
4. **Create Blade template** with all UI states (not connected, connecting, connected, error)
5. **Write comprehensive tests** covering all flows and error cases
6. **Run tests** to ensure everything works
7. **Run Pint** to format code according to project standards

## Security Considerations

- Token never logged or displayed in plain text
- Use `type="password"` for token input field
- Entire `data` attribute encrypted via Credential model
- Token only decrypted when needed for API calls
- Unique constraint ensures one GitHub credential per system
- Validate token format before making API request
- Handle all API errors gracefully without leaking sensitive info

## Critical Files for Reference

- `app/Models/Credential.php` - Encrypted data attribute pattern
- `app/Enums/Integrations.php` - GitHub integration enum
- `app/Livewire/Settings/Profile.php` - Livewire component pattern
- `resources/views/livewire/settings/profile.blade.php` - Blade template pattern
- `tests/Feature/Settings/ProfileUpdateTest.php` - Testing pattern
- `resources/views/components/settings/layout.blade.php` - Settings layout (from git)
