<?php

namespace App\Actions;

use App\Models\Repository;

class GetGitDiff
{
    /**
     * Generate git diff between working tree and selected branch.
     *
     * @return array{diff: string, stats: array{files: int, insertions: int, deletions: int}}
     */
    public function handle(Repository $repository): array
    {
        $escapedPath = escapeshellarg($repository->path);
        $escapedBranch = escapeshellarg($repository->selected_branch);

        // Get diff content
        $diffCommand = "git -C {$escapedPath} diff {$escapedBranch} 2>&1";
        exec($diffCommand, $diffOutput, $diffReturnCode);

        $diff = implode("\n", $diffOutput);

        // Get diff statistics
        $statsCommand = "git -C {$escapedPath} diff {$escapedBranch} --shortstat 2>&1";
        exec($statsCommand, $statsOutput, $statsReturnCode);

        $stats = $this->parseStats($statsOutput[0] ?? '');

        return [
            'diff' => $diff,
            'stats' => $stats,
        ];
    }

    /**
     * Parse git diff statistics.
     *
     * @return array{files: int, insertions: int, deletions: int}
     */
    protected function parseStats(string $statsLine): array
    {
        $stats = [
            'files' => 0,
            'insertions' => 0,
            'deletions' => 0,
        ];

        // Parse: " 3 files changed, 45 insertions(+), 12 deletions(-)"
        if (preg_match('/(\d+)\s+files?\s+changed/', $statsLine, $matches)) {
            $stats['files'] = (int) $matches[1];
        }

        if (preg_match('/(\d+)\s+insertions?\(\+\)/', $statsLine, $matches)) {
            $stats['insertions'] = (int) $matches[1];
        }

        if (preg_match('/(\d+)\s+deletions?\(-\)/', $statsLine, $matches)) {
            $stats['deletions'] = (int) $matches[1];
        }

        return $stats;
    }
}
