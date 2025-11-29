<?php

use App\Actions\ValidateGitRepository;
use App\Models\Repository;
use Livewire\Component;

new class extends Component
{
    public bool $showModal = false;

    public string $path = '';

    public string $name = '';

    public array $branches = [];

    public string $selectedBranch = '';

    public bool $pathValidated = false;

    public function openModal(): void
    {
        $this->reset('path', 'name', 'branches', 'selectedBranch', 'pathValidated');
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset('path', 'name', 'branches', 'selectedBranch', 'pathValidated');
    }

    public function validatePath(): void
    {
        $this->validate([
            'path' => ['required', 'string'],
        ]);

        $result = app(ValidateGitRepository::class)->handle($this->path);

        $this->path = $result['path'];
        $this->branches = $result['branches'];
        $this->pathValidated = true;

        // Set default name from directory
        if (empty($this->name)) {
            $this->name = basename($this->path);
        }

        // Pre-select first branch
        if (! empty($this->branches)) {
            $this->selectedBranch = $this->branches[0];
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'path' => ['required', 'string'],
            'selectedBranch' => ['required', 'string'],
        ]);

        Repository::query()->create([
            'name' => $this->name,
            'path' => $this->path,
            'selected_branch' => $this->selectedBranch,
            'is_active' => false,
        ]);

        $this->dispatch('repository-added');
        $this->closeModal();
    }
};
?>

<div>
    <flux:button variant="primary" wire:click="openModal">
        {{ __('Add Repository') }}
    </flux:button>

    <flux:modal :name="'add-repository'" wire:model="showModal" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Add Git Repository') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Add a local git repository to track changes') }}</flux:text>
        </div>

        @if (! $pathValidated)
            <div class="space-y-4">
                <div class="space-y-2">
                    <flux:input
                        wire:model.defer="path"
                        :label="__('Repository Path')"
                        placeholder="/path/to/repository"
                        autocomplete="off"
                    />
                    @error('path')
                        <flux:text color="red">{{ $message }}</flux:text>
                    @enderror
                    <flux:text variant="subtle">{{ __('Enter the full path to your git repository') }}</flux:text>
                </div>

                <div class="flex gap-3">
                    <flux:button
                        variant="primary"
                        wire:click="validatePath"
                        wire:loading.attr="disabled"
                        wire:target="validatePath"
                    >
                        <span wire:loading.remove wire:target="validatePath">{{ __('Validate') }}</span>
                        <span wire:loading wire:target="validatePath">{{ __('Validating...') }}</span>
                    </flux:button>
                    <flux:button variant="ghost" wire:click="closeModal">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        @else
            <div class="space-y-4">
                <div class="space-y-2">
                    <flux:input
                        wire:model.defer="name"
                        :label="__('Repository Name')"
                        placeholder="My Project"
                        autocomplete="off"
                    />
                    @error('name')
                        <flux:text color="red">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="space-y-2">
                    <flux:input
                        :value="$path"
                        :label="__('Path')"
                        disabled
                    />
                </div>

                <div class="space-y-2">
                    <flux:select
                        wire:model.defer="selectedBranch"
                        :label="__('Compare Against Branch')"
                        placeholder="{{ __('Select a branch') }}"
                    >
                        @foreach ($branches as $branch)
                            <option value="{{ $branch }}">{{ $branch }}</option>
                        @endforeach
                    </flux:select>
                    @error('selectedBranch')
                        <flux:text color="red">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div class="flex gap-3">
                    <flux:button
                        variant="primary"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">{{ __('Add Repository') }}</span>
                        <span wire:loading wire:target="save">{{ __('Adding...') }}</span>
                    </flux:button>
                    <flux:button variant="ghost" wire:click="closeModal">
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>