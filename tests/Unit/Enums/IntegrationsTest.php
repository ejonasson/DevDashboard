<?php

use App\Enums\Integrations;

test('it has all expected integration cases', function () {
    $cases = array_column(Integrations::cases(), 'name');

    expect($cases)->toBe(['GitHub', 'Jira', 'Aws']);
});

test('GitHub case has correct value', function () {
    expect(Integrations::GitHub->value)->toBe('github');
});

test('Jira case has correct value', function () {
    expect(Integrations::Jira->value)->toBe('jira');
});

test('Aws case has correct value', function () {
    expect(Integrations::Aws->value)->toBe('aws');
});

test('it can be instantiated from string value', function () {
    expect(Integrations::from('github'))->toBe(Integrations::GitHub)
        ->and(Integrations::from('jira'))->toBe(Integrations::Jira)
        ->and(Integrations::from('aws'))->toBe(Integrations::Aws);
});

test('tryFrom returns null for invalid value', function () {
    expect(Integrations::tryFrom('invalid'))->toBeNull();
});

test('from throws exception for invalid value', function () {
    Integrations::from('invalid');
})->throws(ValueError::class);
