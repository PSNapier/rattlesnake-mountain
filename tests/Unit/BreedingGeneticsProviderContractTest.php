<?php

use App\Data\BreedingGeneticsRequest;
use App\Services\Genetics\BreedingGeneticsParser;
use App\Services\Genetics\FakeExternalGeneticsProvider;
use App\Services\Genetics\LocalPunnettGeneticsProvider;

it('local provider returns exactly two normalized options', function () {
    $provider = app(LocalPunnettGeneticsProvider::class);
    $result = $provider->roll(new BreedingGeneticsRequest(
        sireGeno: 'Ee Aa',
        damGeno: 'ee aa',
        idempotencyKey: 'stable-key-1',
    ));

    expect($result->provider)->toBe('local')
        ->and($result->options)->toHaveCount(2)
        ->and($result->providerRequestId)->toBe('stable-key-1');

    $parser = app(BreedingGeneticsParser::class);
    foreach ($result->options as $option) {
        expect($parser->isValid($option->geno))->toBeTrue();
    }

    $again = $provider->roll(new BreedingGeneticsRequest(
        sireGeno: 'Ee Aa',
        damGeno: 'ee aa',
        idempotencyKey: 'stable-key-1',
    ));

    expect($again->optionsToArray())->toBe($result->optionsToArray());
});

it('fake external provider fails closed on timeout and malformed payloads', function (string $key, string $message) {
    $provider = app(FakeExternalGeneticsProvider::class);

    expect(fn () => $provider->roll(new BreedingGeneticsRequest(
        sireGeno: 'Ee Aa',
        damGeno: 'ee aa',
        idempotencyKey: $key,
    )))->toThrow(RuntimeException::class, $message);
})->with([
    'timeout' => ['timeout-case', 'Genetics provider timed out.'],
    'malformed' => ['malformed-case', 'Genetics provider returned a malformed payload.'],
    'unsupported' => ['unsupported-case', 'Genetics provider returned an unsupported genotype.'],
]);

it('fake external provider returns valid normalized results', function () {
    $provider = app(FakeExternalGeneticsProvider::class);
    $result = $provider->roll(new BreedingGeneticsRequest(
        sireGeno: 'EE AA',
        damGeno: 'ee aa',
        idempotencyKey: 'ok-case',
    ));

    expect($result->provider)->toBe('fake_external')
        ->and($result->options)->toHaveCount(2)
        ->and($result->providerRequestId)->toStartWith('fake-');
});
