<?php

namespace App\Services\Genetics;

use App\Data\BreedingGeneticsOption;
use App\Data\BreedingGeneticsRequest;
use App\Data\BreedingGeneticsResult;
use App\Services\Contracts\BreedingGeneticsProvider;
use RuntimeException;

/**
 * Contract-test double for a future external genetics API.
 * Not used in production; selected via BREEDING_GENETICS_PROVIDER=fake_external.
 */
class FakeExternalGeneticsProvider implements BreedingGeneticsProvider
{
    public function __construct(private BreedingGeneticsParser $parser) {}

    public function name(): string
    {
        return 'fake_external';
    }

    public function roll(BreedingGeneticsRequest $request): BreedingGeneticsResult
    {
        if (str_contains($request->idempotencyKey, 'timeout')) {
            throw new RuntimeException('Genetics provider timed out.');
        }

        if (str_contains($request->idempotencyKey, 'malformed')) {
            throw new RuntimeException('Genetics provider returned a malformed payload.');
        }

        $local = app(LocalPunnettGeneticsProvider::class)->roll($request);
        $options = $local->options;

        if (str_contains($request->idempotencyKey, 'unsupported')) {
            $options = [
                new BreedingGeneticsOption(geno: 'INVALID TOKEN'),
                new BreedingGeneticsOption(geno: 'EE aa'),
            ];
        }

        foreach ($options as $option) {
            if (! $this->parser->isValid($option->geno)) {
                throw new RuntimeException('Genetics provider returned an unsupported genotype.');
            }
        }

        if (count($options) !== (int) config('breeding.result_option_count', 2)) {
            throw new RuntimeException('Genetics provider did not return the required option count.');
        }

        return new BreedingGeneticsResult(
            options: array_map(
                fn (BreedingGeneticsOption $option): BreedingGeneticsOption => new BreedingGeneticsOption(
                    geno: $this->parser->normalize($option->geno),
                    phenotypeLabel: (string) config('breeding.phenotype_placeholder'),
                ),
                $options,
            ),
            provider: $this->name(),
            providerRequestId: 'fake-'.$request->idempotencyKey,
            metadata: ['source' => 'fake_external'],
        );
    }
}
