<?php

namespace App\Livewire\Settings;

use App\Enums\Integrations;
use App\Models\Credential;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GitHub extends Component
{
    public string $token = '';

    public bool $isConnected = false;

    public ?string $username = null;

    public ?string $avatarUrl = null;

    public ?string $connectedAt = null;

    public function mount(): void
    {
        $credential = Credential::query()->where('integration', Integrations::GitHub)->first();

        if ($credential !== null) {
            $this->isConnected = true;
            $this->username = Arr::get($credential->data, 'user.login');
            $this->avatarUrl = Arr::get($credential->data, 'user.avatar_url');
            $this->connectedAt = Arr::get($credential->data, 'validated_at');
        }
    }

    public function connect(): void
    {
        $this->validate([
            'token' => [
                'required',
                'string',
                'min:20',
                'regex:/^(ghp_|github_pat_)[a-zA-Z0-9_]+$/',
            ],
        ]);

        $user = $this->validateGitHubToken($this->token);

        $data = [
            'token' => $this->token,
            'user' => [
                'id' => Arr::get($user, 'id'),
                'login' => Arr::get($user, 'login'),
                'name' => Arr::get($user, 'name'),
                'email' => Arr::get($user, 'email'),
                'avatar_url' => Arr::get($user, 'avatar_url'),
            ],
            'validated_at' => Carbon::now()->toIso8601String(),
        ];

        Credential::query()->updateOrCreate(
            ['integration' => Integrations::GitHub],
            ['data' => $data],
        );

        $this->isConnected = true;
        $this->username = Arr::get($data, 'user.login');
        $this->avatarUrl = Arr::get($data, 'user.avatar_url');
        $this->connectedAt = Arr::get($data, 'validated_at');
    }

    public function disconnect(): void
    {
        Credential::query()->where('integration', Integrations::GitHub)->delete();

        $this->reset('isConnected', 'username', 'avatarUrl', 'connectedAt');
    }

    private function validateGitHubToken(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/vnd.github+json',
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'DevDashboard-App',
            ])->get('https://api.github.com/user');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'token' => __('Unable to connect to GitHub. Please check your internet connection.'),
            ]);
        }

        if ($response->status() === 200) {
            return $response->json();
        }

        if ($response->status() === 401) {
            throw ValidationException::withMessages([
                'token' => __('Invalid GitHub token. Please check your token and try again.'),
            ]);
        }

        if ($response->status() === 403) {
            throw ValidationException::withMessages([
                'token' => __('Token lacks required permissions or has been revoked.'),
            ]);
        }

        if ($response->status() === 429) {
            throw ValidationException::withMessages([
                'token' => __('GitHub API rate limit exceeded. Please try again later.'),
            ]);
        }

        throw ValidationException::withMessages([
            'token' => __('Failed to validate token. Please try again.'),
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.github');
    }
}
