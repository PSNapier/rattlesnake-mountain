<?php

use App\Services\Genetics\BreedingGeneticsParser;

beforeEach(function () {
    $this->parser = new BreedingGeneticsParser;
});

it('parses and normalizes required loci', function () {
    expect($this->parser->normalize('Ee Aa'))->toBe('Ee Aa')
        ->and($this->parser->normalize('ee A+a nCr'))->toBe('ee A+a nCr')
        ->and($this->parser->normalize('EE aa CrCr nPrl'))->toBe('EE aa CrCr nPrl');
});

it('rejects malformed and unknown tokens', function (string $geno) {
    expect($this->parser->isValid($geno))->toBeFalse();
})->with([
    'empty' => [''],
    'unknown modifier' => ['Ee Aa nXx'],
    'flaxen unsupported' => ['Ee Aa Flaxen'],
    'duplicate locus' => ['Ee Ee aa'],
    'missing required' => ['nCr'],
    'bad case collapse' => ['EE AA Gg'],
]);

it('enumerates punnett offspring for extension locus', function () {
    $possible = $this->parser->possibleOffspringGenos('EE aa', 'ee aa');

    expect($possible)->toContain('Ee aa')
        ->and($possible)->not->toContain('EE aa')
        ->and($possible)->not->toContain('ee aa');
});

it('handles cream and pearl combinations', function () {
    $possible = $this->parser->possibleOffspringGenos('Ee Aa nCr', 'Ee Aa nPrl');

    expect($possible)->toContain('Ee Aa')
        ->and($possible)->toContain('Ee Aa nCr')
        ->and($possible)->toContain('Ee Aa nPrl')
        ->and($possible)->toContain('Ee Aa nCr nPrl');
});

it('orders heterozygous optional modifiers as nDominant', function () {
    expect($this->parser->normalize('Ee aa Crn'))->toBe('Ee aa nCr');
});
