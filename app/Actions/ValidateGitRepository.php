<?php

namespace App\Actions;

use Illuminate\Validation\ValidationException;

class ValidateGitRepository
{
    /**
     * Validate the git repository path and return available branches.
     *
     * @return array{path: string, branches: array<string>}
     *
     * @throws ValidationException
     */
    public function handle(string $path): array
    {
        // Canonicalize the path to prevent path traversal
        $realPath = realpath($path);

        if ($realPath === false || ! is_dir($realPath)) {
            throw ValidationException::withMessages([
                'path' => __('The specified path does not exist or is not accessible.'),
            ]);
        }

        if (! is_readable($realPath)) {
            throw ValidationException::withMessages([
                'path' => __('The specified path is not readable. Please check permissions.'),
            ]);
        }

        // Check if it's a git repository
        $gitDir = $realPath.'/.git';
        if (! is_dir($gitDir)) {
            throw ValidationException::withMessages([
                'path' => __('The specified path is not a git repository.'),
            ]);
        }

        // Get available branches
        $escapedPath = escapeshellarg($realPath);
        $format = escapeshellarg('%(refname:short)');
        $command = "git -C {$escapedPath} branch -a --format={$format} 2>&1";

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw ValidationException::withMessages([
                'path' => __('Failed to read git repository. Please ensure git is installed and the repository is valid.'),
            ]);
        }

        // Filter out remote tracking branches and deduplicate
        $branches = collect($output)
            ->map(fn ($branch) => trim($branch))
            ->filter(fn ($branch) => ! empty($branch))
            ->map(fn ($branch) => str_starts_with($branch, 'origin/')
                ? substr($branch, 7)
                : $branch
            )
            ->unique()
            ->values()
            ->toArray();

        if (empty($branches)) {
            throw ValidationException::withMessages([
                'path' => __('No branches found in the repository.'),
            ]);
        }

        return [
            'path' => $realPath,
            'branches' => $branches,
        ];
    }
}
