<?php

use App\Models\Repository;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Collection;


new class extends Component {
    protected $listeners = [
        'repository-added' => '$refresh',
        'repository-deleted' => '$refresh',
    ];

    #[Computed]
    public function repositories(): Collection
    {
        return Repository::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Version Control') }}</flux:heading>
            <flux:text variant="subtle">{{ __('Manage local git repositories and view changes') }}</flux:text>
        </div>
        <livewire:version-control.add-repository-modal/>
    </div>

    @if ($this->repositories->isEmpty())
        <div class="flex flex-col items-center justify-center py-12 space-y-4">
            <flux:icon.folder-git-2 variant="outline" class="w-16 h-16 text-zinc-400 dark:text-zinc-600"/>
            <div class="text-center">
                <flux:heading size="lg">{{ __('No repositories yet') }}</flux:heading>
                <flux:text variant="subtle">{{ __('Add a local git repository to get started') }}</flux:text>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->repositories as $repository)
                <livewire:version-control.repository-card :repository="$repository" :key="$repository->id"/>
            @endforeach
        </div>
    @endif
</div>
