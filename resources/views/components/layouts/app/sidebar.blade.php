<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <!-- Sidebar - Hover Expandable -->
        <aside class="group fixed left-0 top-0 z-40 flex h-screen w-16 flex-col overflow-hidden border-r border-zinc-200 bg-zinc-50 transition-all duration-300 ease-in-out dark:border-zinc-700 dark:bg-zinc-900">
            <!-- Logo -->
            <div class="flex h-16 shrink-0 items-center px-4">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3" wire:navigate>
                    <div class="shrink-0">
                        <x-app-logo-icon class="h-8 w-8" />
                    </div>
                </a>
            </div>

            <!-- Navigation Items -->
            <nav class="flex-1 space-y-1 px-2 py-4">
                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:hover:bg-zinc-800 {{ request()->routeIs('dashboard') ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}"
                   wire:navigate
                   aria-label="Dashboard">
                    <flux:icon.home class="h-6 w-6 shrink-0" />
                </a>

                <!-- Version Control -->
                <a href="{{ route('version-control') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:hover:bg-zinc-800 {{ request()->routeIs('version-control') ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}"
                   wire:navigate
                   aria-label="Version Control">
                    <flux:icon.code-bracket class="h-6 w-6 shrink-0" />
                </a>

                <!-- Planning -->
                <a href="{{ route('planning') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:hover:bg-zinc-800 {{ request()->routeIs('planning') ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}"
                   wire:navigate
                   aria-label="Planning">
                    <flux:icon.clipboard-document-list class="h-6 w-6 shrink-0" />
                </a>

                <!-- Deployment -->
                <a href="{{ route('deployment') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:hover:bg-zinc-800 {{ request()->routeIs('deployment') ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}"
                   wire:navigate
                   aria-label="Deployment">
                    <flux:icon.rocket-launch class="h-6 w-6 shrink-0" />
                </a>
            </nav>

            <!-- Spacer -->
            <div class="flex-1"></div>

            <!-- Settings at Bottom -->
            <div class="px-2 pb-4">
                <a href="{{ route('settings') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:hover:bg-zinc-800 {{ request()->routeIs('profile.edit') || request()->routeIs('user-password.edit') || request()->routeIs('appearance.edit') || request()->routeIs('two-factor.show') ? 'bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-700 dark:text-zinc-300' }}"
                   wire:navigate
                   aria-label="Settings">
                    <flux:icon.cog-6-tooth class="h-6 w-6 shrink-0" />
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="ml-16">
            {{ $slot }}
        </div>

        @fluxScripts
    </body>
</html>
