<?php

use App\Actions\GetGitDiff;
use App\Models\Repository;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Repository $repository;

    public bool $showDiff = false;

    public ?array $diffData = null;

    #[On('repository-added')]
    public function refresh(): void
    {
        $this->repository = $this->repository->fresh();
    }

    public function makeActive(): void
    {
        // Deactivate all others
        Repository::query()->where('is_active', true)->update(['is_active' => false]);

        // Activate this one
        $this->repository->update(['is_active' => true]);
        $this->repository = $this->repository->fresh();

        $this->dispatch('repository-activated', id: $this->repository->id);
    }

    public function toggleDiff(): void
    {
        if ($this->showDiff) {
            $this->showDiff = false;
            $this->diffData = null;

            return;
        }

        // Load diff data
        $this->diffData = app(GetGitDiff::class)->handle($this->repository);
        $this->showDiff = true;
    }

    public function delete(): void
    {
        $id = $this->repository->id;
        $this->repository->delete();

        $this->dispatch('repository-deleted', id: $id);
    }
};
?>

<div class="p-6 border rounded-lg border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
    <div class="flex items-start justify-between gap-4">
        <div class="flex-1 space-y-2">
            <div class="flex items-center gap-3">
                <flux:heading size="lg">{{ $repository->name }}</flux:heading>
                @if ($repository->is_active)
                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                @endif
            </div>

            <div class="space-y-1">
                <flux:text variant="subtle" class="font-mono text-sm">{{ $repository->path }}</flux:text>
                <flux:text variant="subtle">
                    {{ __('Comparing against:') }} <span class="font-semibold">{{ $repository->selected_branch }}</span>
                </flux:text>
            </div>
        </div>

        <div class="flex gap-2">
            @if (! $repository->is_active)
                <flux:button
                    variant="ghost"
                    size="sm"
                    wire:click="makeActive"
                    wire:loading.attr="disabled"
                    wire:target="makeActive"
                >
                    {{ __('Make Active') }}
                </flux:button>
            @endif

            <flux:button
                variant="ghost"
                size="sm"
                wire:click="toggleDiff"
                wire:loading.attr="disabled"
                wire:target="toggleDiff"
            >
                <span wire:loading.remove wire:target="toggleDiff">
                    {{ $showDiff ? __('Hide Diff') : __('Show Diff') }}
                </span>
                <span wire:loading wire:target="toggleDiff">{{ __('Loading...') }}</span>
            </flux:button>

            <flux:button
                variant="danger"
                size="sm"
                wire:click="delete"
                wire:confirm="{{ __('Are you sure you want to remove this repository?') }}"
                wire:loading.attr="disabled"
                wire:target="delete"
            >
                {{ __('Remove') }}
            </flux:button>
        </div>
    </div>

    @if ($showDiff && $diffData)
        <div class="pt-6 mt-6 space-y-4 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex gap-6">
                <div>
                    <flux:text variant="subtle">{{ __('Files changed') }}</flux:text>
                    <flux:heading size="md">{{ $diffData['stats']['files'] }}</flux:heading>
                </div>
                <div>
                    <flux:text variant="subtle">{{ __('Insertions') }}</flux:text>
                    <flux:heading size="md" class="text-green-600 dark:text-green-400">+{{ $diffData['stats']['insertions'] }}</flux:heading>
                </div>
                <div>
                    <flux:text variant="subtle">{{ __('Deletions') }}</flux:text>
                    <flux:heading size="md" class="text-red-600 dark:text-red-400">-{{ $diffData['stats']['deletions'] }}</flux:heading>
                </div>
            </div>

            @if (! empty($diffData['diff']))
                <div class="p-4 overflow-x-auto rounded-lg bg-zinc-50 dark:bg-zinc-950">
                    <pre class="text-xs font-mono text-zinc-900 dark:text-zinc-100">{{ $diffData['diff'] }}</pre>
                </div>
            @else
                <flux:text variant="subtle">{{ __('No changes detected') }}</flux:text>
            @endif
        </div>
    @endif
</div>