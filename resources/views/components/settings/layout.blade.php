@props([
    'heading' => null,
    'subheading' => null,
])

<div class="w-full py-6">
    <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
        <div class="space-y-2">
            @if ($heading)
                <flux:heading size="lg">{{ $heading }}</flux:heading>
            @endif
            @if ($subheading)
                <flux:text variant="subtle">{{ $subheading }}</flux:text>
            @endif
        </div>

        <div class="md:col-span-2">
            <div class="p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

