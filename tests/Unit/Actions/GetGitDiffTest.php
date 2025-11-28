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

it('returns diff content and statistics', function () {
    $result = $this->action->handle($this->repository);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['diff', 'stats'])
        ->and($result['diff'])->toBeString()
        ->and($result['stats'])->toBeArray()
        ->and($result['stats'])->toHaveKeys(['files', 'insertions', 'deletions']);
});

it('parses statistics correctly', function () {
    $result = $this->action->handle($this->repository);

    expect($result['stats']['files'])->toBeInt()
        ->and($result['stats']['insertions'])->toBeInt()
        ->and($result['stats']['deletions'])->toBeInt();
});

it('returns empty diff when no changes', function () {
    // Commit all changes
    exec("git -C {$this->tempDir} add . 2>&1");
    exec("git -C {$this->tempDir} commit -m 'Commit changes' 2>&1");

    $result = $this->action->handle($this->repository);

    expect($result['diff'])->toBe('')
        ->and($result['stats']['files'])->toBe(0)
        ->and($result['stats']['insertions'])->toBe(0)
        ->and($result['stats']['deletions'])->toBe(0);
});
