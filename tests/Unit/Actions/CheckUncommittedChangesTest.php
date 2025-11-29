<?php

use App\Actions\CheckUncommittedChanges;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new CheckUncommittedChanges;

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

it('returns no changes when working directory is clean', function () {
    $result = $this->action->handle($this->repository);

    expect($result)->toBeArray()
        ->and($result['hasChanges'])->toBeFalse()
        ->and($result['staged'])->toBe(0)
        ->and($result['unstaged'])->toBe(0);
});

it('detects unstaged changes', function () {
    // Modify a file without staging
    file_put_contents("{$this->tempDir}/test.txt", 'modified content');

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['staged'])->toBe(0)
        ->and($result['unstaged'])->toBe(1);
});

it('detects staged changes', function () {
    // Modify and stage a file
    file_put_contents("{$this->tempDir}/test.txt", 'modified content');
    exec("git -C {$this->tempDir} add test.txt 2>&1");

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['staged'])->toBe(1)
        ->and($result['unstaged'])->toBe(0);
});

it('detects both staged and unstaged changes', function () {
    // Create new file and stage it
    file_put_contents("{$this->tempDir}/new.txt", 'new file');
    exec("git -C {$this->tempDir} add new.txt 2>&1");

    // Modify existing file without staging
    file_put_contents("{$this->tempDir}/test.txt", 'modified');

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['staged'])->toBe(1)
        ->and($result['unstaged'])->toBe(1);
});

it('detects multiple unstaged files', function () {
    // Modify multiple files
    file_put_contents("{$this->tempDir}/test.txt", 'modified 1');
    file_put_contents("{$this->tempDir}/test2.txt", 'new file 2');
    file_put_contents("{$this->tempDir}/test3.txt", 'new file 3');

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['unstaged'])->toBe(3);
});

it('detects multiple staged files', function () {
    // Create and stage multiple files
    file_put_contents("{$this->tempDir}/new1.txt", 'file 1');
    file_put_contents("{$this->tempDir}/new2.txt", 'file 2');
    exec("git -C {$this->tempDir} add . 2>&1");

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['staged'])->toBe(2);
});

it('handles partially staged files', function () {
    // Modify a file
    file_put_contents("{$this->tempDir}/test.txt", 'first change');
    exec("git -C {$this->tempDir} add test.txt 2>&1");

    // Modify it again without staging
    file_put_contents("{$this->tempDir}/test.txt", 'second change');

    $result = $this->action->handle($this->repository);

    expect($result['hasChanges'])->toBeTrue()
        ->and($result['staged'])->toBe(1)
        ->and($result['unstaged'])->toBe(1);
});
