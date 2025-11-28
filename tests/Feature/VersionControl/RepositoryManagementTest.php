<?php

use App\Models\Repository;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('version control page renders', function () {
    $response = $this->get('/version-control');

    $response->assertSuccessful()
        ->assertSee('Version Control');
});

it('displays empty state when no repositories', function () {
    $response = $this->get('/version-control');

    $response->assertSee('No repositories yet')
        ->assertSee('Add a local git repository to get started');
});

it('displays repositories list', function () {
    Repository::factory()->count(3)->create();

    $response = $this->get('/version-control');

    $response->assertSuccessful()
        ->assertDontSee('No repositories yet');
});

it('displays active repository badge', function () {
    Repository::factory()->active()->create(['name' => 'Active Repo']);
    Repository::factory()->create(['name' => 'Inactive Repo']);

    $response = $this->get('/version-control');

    $response->assertSee('Active Repo')
        ->assertSee('Active');
});

it('can delete repository', function () {
    $repository = Repository::factory()->create();

    Livewire::test('version-control.repository-card', ['repository' => $repository])
        ->call('delete');

    expect(Repository::count())->toBe(0);
});

it('can make repository active', function () {
    $activeRepo = Repository::factory()->active()->create();
    $inactiveRepo = Repository::factory()->create();

    Livewire::test('version-control.repository-card', ['repository' => $inactiveRepo])
        ->call('makeActive');

    $inactiveRepo->refresh();
    $activeRepo->refresh();

    expect($inactiveRepo->is_active)->toBeTrue()
        ->and($activeRepo->is_active)->toBeFalse();
});

it('only allows one active repository at a time', function () {
    $repo1 = Repository::factory()->active()->create();
    $repo2 = Repository::factory()->create();
    $repo3 = Repository::factory()->create();

    Livewire::test('version-control.repository-card', ['repository' => $repo2])
        ->call('makeActive');

    $repo1->refresh();
    $repo2->refresh();
    $repo3->refresh();

    expect($repo1->is_active)->toBeFalse()
        ->and($repo2->is_active)->toBeTrue()
        ->and($repo3->is_active)->toBeFalse();
});
