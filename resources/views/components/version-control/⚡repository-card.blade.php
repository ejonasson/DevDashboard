<?php

use App\Actions\CheckUncommittedChanges;
use App\Actions\GetGitDiff;
use App\Models\Repository;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public Repository $repository;

    public bool $showDiff = false;

    public ?array $diffData = null;

    public int $selectedFileIndex = 0;

    public function mount(): void
    {
        // Check for uncommitted changes on mount
        $this->checkUncommittedChanges();
    }

    #[On('repository-added')]
    public function refresh(): void
    {
        $this->repository = $this->repository->fresh();
        $this->checkUncommittedChanges();
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
            $this->selectedFileIndex = 0;

            return;
        }

        // Load diff data
        $this->diffData = app(GetGitDiff::class)->handle($this->repository);
        $this->selectedFileIndex = 0;
        $this->showDiff = true;
    }

    public function delete(): void
    {
        $id = $this->repository->id;
        $this->repository->delete();

        $this->dispatch('repository-deleted', id: $id);
    }

    public function selectFile(int $index): void
    {
        if (! $this->diffData || ! isset($this->diffData['files'])) {
            return;
        }

        if ($index >= 0 && $index < count($this->diffData['files'])) {
            $this->selectedFileIndex = $index;
        }
    }

    public function checkUncommittedChanges(): array
    {
        return app(CheckUncommittedChanges::class)->handle($this->repository);
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

                @php
                    $uncommittedStatus = $this->checkUncommittedChanges();
                @endphp

                @if ($uncommittedStatus['hasChanges'])
                    <flux:callout variant="warning" size="sm" class="mt-2">
                        <strong>{{ __('This branch has uncommitted changes') }}</strong>
                        <div class="text-xs mt-1">
                            @if ($uncommittedStatus['staged'] > 0)
                                <span>{{ __(':count staged', ['count' => $uncommittedStatus['staged']]) }}</span>
                            @endif
                            @if ($uncommittedStatus['staged'] > 0 && $uncommittedStatus['unstaged'] > 0)
                                <span> • </span>
                            @endif
                            @if ($uncommittedStatus['unstaged'] > 0)
                                <span>{{ __(':count unstaged', ['count' => $uncommittedStatus['unstaged']]) }}</span>
                            @endif
                        </div>
                    </flux:callout>
                @endif
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

            @if (isset($diffData['files']) && count($diffData['files']) > 0)
                <div class="flex gap-4 mt-6">
                    {{-- Aside Navigation --}}
                    <aside class="w-[280px] shrink-0 flex flex-col gap-2 max-h-[600px] overflow-y-auto p-3 rounded-lg bg-zinc-50 dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-800">
                        <div class="px-3 py-2">
                            <flux:text variant="subtle" class="text-xs font-semibold uppercase tracking-wider">
                                {{ __('Files Changed') }}
                            </flux:text>
                        </div>

                        @foreach ($diffData['files'] as $index => $file)
                            <button
                                wire:click="selectFile({{ $index }})"
                                class="w-full px-3 py-2 rounded-md text-left transition-colors {{ $loop->index === $selectedFileIndex ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' : 'hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-900 dark:text-zinc-100' }}"
                                wire:loading.attr="disabled"
                                wire:target="selectFile"
                            >
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium truncate">{{ $file['filename'] }}</span>
                                    <div class="flex gap-2 text-xs">
                                        <span class="text-green-600 dark:text-green-400">+{{ $file['insertions'] }}</span>
                                        <span class="text-red-600 dark:text-red-400">-{{ $file['deletions'] }}</span>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </aside>

                    {{-- Content Area --}}
                    @if (isset($diffData['files'][$selectedFileIndex]))
                        <div class="flex-1 min-w-0 flex flex-col gap-4">
                            {{-- File Header --}}
                            <div class="border-b border-zinc-200 dark:border-zinc-700 pb-3">
                                <flux:heading size="md" class="break-all">
                                    {{ $diffData['files'][$selectedFileIndex]['filename'] }}
                                </flux:heading>
                                <flux:text variant="subtle" class="mt-1">
                                    <span class="text-green-600 dark:text-green-400">
                                        +{{ $diffData['files'][$selectedFileIndex]['insertions'] }}
                                    </span>
                                    <span class="text-red-600 dark:text-red-400">
                                        -{{ $diffData['files'][$selectedFileIndex]['deletions'] }}
                                    </span>
                                </flux:text>
                            </div>

                            {{-- Diff Content --}}
                            <div class="rounded-lg bg-zinc-50 dark:bg-zinc-950 overflow-x-auto border border-zinc-200 dark:border-zinc-800">
                                @if (isset($diffData['files'][$selectedFileIndex]['binary']) && $diffData['files'][$selectedFileIndex]['binary'])
                                    <div class="p-4 text-zinc-500 dark:text-zinc-400 italic text-sm">
                                        {{ __('Binary file changed') }}
                                    </div>
                                @else
                                    <div class="font-mono text-xs">
                                        @foreach ($diffData['files'][$selectedFileIndex]['lines'] as $index => $line)
                                            <div class="flex group hover:bg-zinc-100/50 dark:hover:bg-zinc-800/50 transition-colors
                                                {{ $line['type'] === 'added' ? 'bg-green-50 dark:bg-green-950/20' : '' }}
                                                {{ $line['type'] === 'removed' ? 'bg-red-50 dark:bg-red-950/20' : '' }}
                                            ">
                                                {{-- Line number gutter --}}
                                                <div class="w-12 shrink-0 text-right pr-3 py-1 select-none text-zinc-400 dark:text-zinc-600 bg-zinc-100 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
                                                    {{ $index + 1 }}
                                                </div>

                                                {{-- Type indicator (+/-/space) --}}
                                                <div class="w-8 shrink-0 text-center py-1
                                                    {{ $line['type'] === 'added' ? 'text-green-600 dark:text-green-400 font-semibold' : '' }}
                                                    {{ $line['type'] === 'removed' ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}
                                                    {{ $line['type'] === 'unchanged' ? 'text-zinc-400 dark:text-zinc-600' : '' }}
                                                ">
                                                    @if ($line['type'] === 'added')+@endif
                                                    @if ($line['type'] === 'removed')-@endif
                                                    @if ($line['type'] === 'unchanged')&nbsp;@endif
                                                </div>

                                                {{-- Code content --}}
                                                <div class="flex-1 py-1 pr-4
                                                    {{ $line['type'] === 'added' ? 'text-green-900 dark:text-green-100' : '' }}
                                                    {{ $line['type'] === 'removed' ? 'text-red-900 dark:text-red-100' : '' }}
                                                    {{ $line['type'] === 'unchanged' ? 'text-zinc-800 dark:text-zinc-300' : '' }}
                                                ">{{ $line['content'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                        {{-- Navigation Buttons --}}
                        <div class="flex gap-2 mt-4">
                            <flux:button
                                wire:click="selectFile({{ $selectedFileIndex - 1 }})"
                                :disabled="$selectedFileIndex === 0"
                                variant="ghost"
                                size="sm"
                            >
                                {{ __('Previous') }}
                            </flux:button>

                            <flux:text variant="subtle" class="flex-1 text-center py-2">
                                {{ $selectedFileIndex + 1 }} {{ __('of') }} {{ count($diffData['files']) }}
                            </flux:text>

                            <flux:button
                                wire:click="selectFile({{ $selectedFileIndex + 1 }})"
                                :disabled="$selectedFileIndex === count($diffData['files']) - 1"
                                variant="ghost"
                                size="sm"
                            >
                                {{ __('Next') }}
                            </flux:button>
                        </div>
                        </div>
                    @endif
                </div>
            @else
                <flux:text variant="subtle">{{ __('No changes detected') }}</flux:text>
            @endif
        </div>
    @endif
</div>
