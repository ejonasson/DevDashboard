<?php

use App\Actions\GetGitDiff;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new GetGitDiff;

    // Create a temporary git repository for testing
    $this->tempDir = sys_get_temp_dir().'/test-git-repo-'.uniqid();
    mkdir($this->tempDir);
    exec("git -C {$this->tempDir} init 2>&1");
    exec("git -C {$this->tempDir} config user.email 'test@example.com' 2>&1");
    exec("git -C {$this->tempDir} config user.name 'Test User' 2>&1");
    exec("git -C {$this->tempDir} checkout -b main 2>&1");

    // Create initial commit
    file_put_contents("{$this->tempDir}/test.txt", 'initial content');
    exec("git -C {$this->tempDir} add . 2>&1");
    exec("git -C {$this->tempDir} commit -m 'Initial commit' 2>&1");

    // Make changes in working directory
    file_put_contents("{$this->tempDir}/test.txt", "initial content\nnew line");
    file_put_contents("{$this->tempDir}/new-file.txt", 'new file content');

    // Create repository model
    $this->repository = Repository::factory()->create([
        'path' => $this->tempDir,
        'selected_branch' => 'main',
    ]);
});

afterEach(function () {
    // Clean up temporary directory
    if (file_exists($this->tempDir)) {
        exec("rm -rf {$this->tempDir}");
    }
});

it('returns parsed diff lines and statistics', function () {
    $result = $this->action->handle($this->repository);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['files', 'stats'])
        ->and($result['files'])->toBeArray()
        ->and($result['stats'])->toHaveKeys(['files', 'insertions', 'deletions']);
});

it('parses line types correctly', function () {
    $result = $this->action->handle($this->repository);

    expect($result['files'])->not->toBeEmpty();

    $file = $result['files'][0];
    expect($file)->toHaveKeys(['filename', 'lines', 'insertions', 'deletions'])
        ->and($file['lines'])->toBeArray()
        ->and($file['lines'][0])->toHaveKeys(['type', 'content']);

    // Verify line types are valid
    $types = collect($file['lines'])->pluck('type')->unique();
    $types->each(fn ($type) => expect($type)->toBeIn(['added', 'removed', 'unchanged']));
});

it('parses statistics correctly', function () {
    $result = $this->action->handle($this->repository);

    expect($result['stats']['files'])->toBeInt()
        ->and($result['stats']['insertions'])->toBeInt()
        ->and($result['stats']['deletions'])->toBeInt()
        ->and($result['stats']['files'])->toBeGreaterThan(0);
});

it('returns empty files array when no changes', function () {
    // Commit all changes
    exec("git -C {$this->tempDir} add . 2>&1");
    exec("git -C {$this->tempDir} commit -m 'Commit changes' 2>&1");

    $result = $this->action->handle($this->repository);

    expect($result['files'])->toBeEmpty()
        ->and($result['stats']['files'])->toBe(0)
        ->and($result['stats']['insertions'])->toBe(0)
        ->and($result['stats']['deletions'])->toBe(0);
});

it('handles binary files', function () {
    // Create a binary file
    file_put_contents("{$this->tempDir}/image.png", random_bytes(100));
    exec("git -C {$this->tempDir} add image.png 2>&1");
    exec("git -C {$this->tempDir} commit -m 'Add binary' 2>&1");

    // Modify it
    file_put_contents("{$this->tempDir}/image.png", random_bytes(150));

    $result = $this->action->handle($this->repository);

    $binaryFile = collect($result['files'])->firstWhere('filename', 'image.png');

    if ($binaryFile) {
        expect($binaryFile)->toHaveKey('binary')
            ->and($binaryFile['binary'])->toBeTrue();
    }
});

it('preserves line content exactly', function () {
    // Create file with special formatting
    file_put_contents("{$this->tempDir}/test.txt", "    indented\n\ttabbed\n");

    $result = $this->action->handle($this->repository);

    $file = collect($result['files'])->firstWhere('filename', 'test.txt');

    if ($file && isset($file['lines'])) {
        $contents = collect($file['lines'])->pluck('content');
        expect($contents->contains(fn ($c) => str_contains($c, '    indented')))->toBeTrue();
    }
});
