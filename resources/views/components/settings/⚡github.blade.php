<?php

use App\Actions\ValidateGithubToken;
use App\Enums\Integrations;
use App\Models\Credential;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Livewire\Component;

new class extends Component
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

        $user = app(ValidateGithubToken::class)->handle($this->token);

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

    public function render(): \Illuminate\View\View
    {
        return view('components.settings.⚡github');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('GitHub Integration')" :subheading="__('Connect your GitHub account using a Personal Access Token (PAT)')">
        @if (! $isConnected)
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:badge color="zinc">{{ __('Not Connected') }}</flux:badge>
                </div>

                <flux:text>
                    {{ __('Enter a GitHub Personal Access Token (PAT) to connect. You can create a token from your GitHub account settings.') }}
                </flux:text>

                <flux:link href="https://github.com/settings/tokens?type=beta" target="_blank" rel="noopener" variant="subtle">
                    {{ __('Create a new token on GitHub') }}
                </flux:link>

                <div class="space-y-2">
                    <flux:input
                        wire:model.defer="token"
                        type="password"
                        :label="__('Personal Access Token')"
                        placeholder="github_pat_..."
                        autocomplete="off"
                    />
                    @error('token')
                        <flux:text color="red">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <flux:button
                        variant="primary"
                        wire:click="connect"
                        wire:loading.attr="disabled"
                        wire:target="connect"
                    >
                        <span wire:loading.remove wire:target="connect">{{ __('Connect GitHub') }}</span>
                        <span wire:loading wire:target="connect">{{ __('Validating...') }}</span>
                    </flux:button>
                </div>
            </div>
        @else
            <div class="space-y-6">
                <div class="flex items-center gap-3">
                    <flux:badge color="green">{{ __('Connected') }}</flux:badge>
                </div>

                <div class="flex items-center gap-4">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="GitHub avatar" class="w-12 h-12 rounded-full" />
                    @endif
                    <div>
                        <flux:heading size="md">{{ $username }}</flux:heading>
                        @if ($connectedAt)
                            <flux:text variant="subtle">{{ __('Connected') }} {{ \Illuminate\Support\Carbon::parse($connectedAt)->diffForHumans() }}</flux:text>
                        @endif
                    </div>
                </div>

                <div>
                    <flux:button
                        variant="danger"
                        icon="folder-git-2"
                        icon:variant="outline"
                        wire:click="disconnect"
                        wire:loading.attr="disabled"
                        wire:target="disconnect"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </x-settings.layout>

</section>
