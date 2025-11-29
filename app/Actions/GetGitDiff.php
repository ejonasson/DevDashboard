<?php

namespace App\Actions;

use App\Models\Repository;
use SebastianBergmann\Diff\Line;
use SebastianBergmann\Diff\Parser;

class GetGitDiff
{
    /**
     * Generate git diff between working tree and selected branch.
     *
     * @return array{files: array<int, array{filename: string, lines: array<int, array{type: string, content: string}>, insertions: int, deletions: int, binary?: bool}>, stats: array{files: int, insertions: int, deletions: int}}
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

                $diffOutput = implode("\n", $fileDiffOutput);

                // Check if this is a binary file
                if (str_contains($diffOutput, 'Binary files')) {
                    $files[] = [
                        'filename' => $filename,
                        'insertions' => 0,
                        'deletions' => 0,
                        'binary' => true,
                    ];
                } else {
                    $files[] = [
                        'filename' => $filename,
                        'lines' => $this->parseDiff($diffOutput),
                        'insertions' => $parts[0] === '-' ? 0 : (int) $parts[0],
                        'deletions' => $parts[1] === '-' ? 0 : (int) $parts[1],
                    ];
                }

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

    /**
     * Parse a unified diff string into structured line data.
     *
     * @return array<int, array{type: string, content: string}>
     */
    private function parseDiff(string $unifiedDiff): array
    {
        if (empty(trim($unifiedDiff))) {
            return [];
        }

        $parser = new Parser;
        $diffs = $parser->parse($unifiedDiff);

        $lines = [];
        foreach ($diffs as $diff) {
            foreach ($diff->chunks() as $chunk) {
                foreach ($chunk->lines() as $line) {
                    $lines[] = [
                        'type' => $this->mapLineType($line->type()),
                        'content' => $line->content(),
                    ];
                }
            }
        }

        return $lines;
    }

    /**
     * Map sebastianbergmann/diff line type constants to string types.
     */
    private function mapLineType(int $type): string
    {
        return match ($type) {
            Line::ADDED => 'added',
            Line::REMOVED => 'removed',
            Line::UNCHANGED => 'unchanged',
        };
    }
}
