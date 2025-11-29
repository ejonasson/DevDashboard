<?php

namespace App\Actions;

use App\Models\Repository;

class CheckUncommittedChanges
{
    /**
     * Check if repository has uncommitted changes.
     *
     * @return array{hasChanges: bool, staged: int, unstaged: int}
     */
    public function handle(Repository $repository): array
    {
        $escapedPath = escapeshellarg($repository->path);

        // Get git status in porcelain format for easy parsing
        $statusCommand = "git -C {$escapedPath} status --porcelain 2>&1";
        exec($statusCommand, $statusOutput, $statusReturnCode);

        if ($statusReturnCode !== 0) {
            return [
                'hasChanges' => false,
                'staged' => 0,
                'unstaged' => 0,
            ];
        }

        $staged = 0;
        $unstaged = 0;

        foreach ($statusOutput as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Porcelain format: XY PATH
            // X = staged status, Y = unstaged status
            // ' ' = unmodified, M = modified, A = added, D = deleted, R = renamed, C = copied, U = updated but unmerged
            $x = $line[0] ?? ' ';
            $y = $line[1] ?? ' ';

            // Check staged status (first character)
            if ($x !== ' ' && $x !== '?') {
                $staged++;
            }

            // Check unstaged status (second character)
            if ($y !== ' ') {
                $unstaged++;
            }
        }

        return [
            'hasChanges' => $staged > 0 || $unstaged > 0,
            'staged' => $staged,
            'unstaged' => $unstaged,
        ];
    }
}
