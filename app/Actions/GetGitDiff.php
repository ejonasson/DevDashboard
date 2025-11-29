<?php

namespace App\Actions;

use App\Models\Repository;

class GetGitDiff
{
    /**
     * Generate git diff between working tree and selected branch.
     *
     * @return array{files: array<int, array{filename: string, diff: string, insertions: int, deletions: int}>, stats: array{files: int, insertions: int, deletions: int}}
     */
    public function handle(Repository $repository): array
    {
        $escapedPath = escapeshellarg($repository->path);
        $escapedBranch = escapeshellarg($repository->selected_branch);

        // Get list of changed files with their stats
        $numstatCommand = "git -C {$escapedPath} diff {$escapedBranch} --numstat 2>&1";
        exec($numstatCommand, $numstatOutput, $numstatReturnCode);

        $files = [];
        foreach ($numstatOutput as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse: "45\t12\tpath/to/file.php"
            $parts = preg_split('/\t/', $line, 3);
            if (count($parts) === 3) {
                $filename = $parts[2];
                $escapedFilename = escapeshellarg($filename);

                // Get diff for this specific file
                $fileDiffCommand = "git -C {$escapedPath} diff {$escapedBranch} -- {$escapedFilename} 2>&1";
                exec($fileDiffCommand, $fileDiffOutput, $fileDiffReturnCode);

                $files[] = [
                    'filename' => $filename,
                    'diff' => implode("\n", $fileDiffOutput),
                    'insertions' => $parts[0] === '-' ? 0 : (int) $parts[0],
                    'deletions' => $parts[1] === '-' ? 0 : (int) $parts[1],
                ];

                $fileDiffOutput = [];
            }
        }

        // Calculate total stats
        $stats = [
            'files' => count($files),
            'insertions' => array_sum(array_column($files, 'insertions')),
            'deletions' => array_sum(array_column($files, 'deletions')),
        ];

        return [
            'files' => $files,
            'stats' => $stats,
        ];
    }
}
