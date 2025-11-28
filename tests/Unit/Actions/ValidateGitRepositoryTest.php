<?php

use App\Actions\ValidateGitRepository;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->action = new ValidateGitRepository;

    // Create a temporary git repository for testing
    $this->tempDir = sys_get_temp_dir().'/test-git-repo-'.uniqid();
    mkdir($this->tempDir);
    exec("git -C {$this->tempDir} init 2>&1");
    exec("git -C {$this->tempDir} config user.email 'test@example.com' 2>&1");
    exec("git -C {$this->tempDir} config user.name 'Test User' 2>&1");
    exec("git -C {$this->tempDir} checkout -b main 2>&1");
    file_put_contents("{$this->tempDir}/test.txt", 'test');
    exec("git -C {$this->tempDir} add . 2>&1");
    exec("git -C {$this->tempDir} commit -m 'Initial commit' 2>&1");
});

afterEach(function () {
    // Clean up temporary directory
    if (file_exists($this->tempDir)) {
        exec("rm -rf {$this->tempDir}");
    }
});

it('validates a real git repository', function () {
    $result = $this->action->handle($this->tempDir);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['path', 'branches'])
        ->and($result['path'])->toBe(realpath($this->tempDir))
        ->and($result['branches'])->toBeArray()
        ->and($result['branches'])->toContain('main');
});

it('throws exception for non-existent path', function () {
    $this->action->handle('/path/that/does/not/exist');
})->throws(ValidationException::class);

it('throws exception for non-git directory', function () {
    $nonGitDir = sys_get_temp_dir().'/non-git-'.uniqid();
    mkdir($nonGitDir);

    try {
        $this->action->handle($nonGitDir);
    } finally {
        rmdir($nonGitDir);
    }
})->throws(ValidationException::class);

it('returns branches correctly', function () {
    // Create additional branches
    exec("git -C {$this->tempDir} checkout -b develop 2>&1");
    exec("git -C {$this->tempDir} checkout -b feature/test 2>&1");
    exec("git -C {$this->tempDir} checkout main 2>&1");

    $result = $this->action->handle($this->tempDir);

    expect($result['branches'])
        ->toBeArray()
        ->toContain('main')
        ->toContain('develop')
        ->toContain('feature/test');
});

it('canonicalizes path to prevent path traversal', function () {
    $result = $this->action->handle($this->tempDir.'/.');

    expect($result['path'])->toBe(realpath($this->tempDir));
});
