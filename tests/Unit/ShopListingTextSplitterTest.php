<?php

use App\Support\ShopListingTextSplitter;

it('splits leading quoted flavor from mechanical description', function () {
    $raw = "\"Unlike other buckthorns, the Alder Buckthorn does not have thorns.\"\n\n \n\nConsuming this plant permanently increases Intelligence by +10.";

    $result = ShopListingTextSplitter::split($raw);

    expect($result['flavor'])->toBe('Unlike other buckthorns, the Alder Buckthorn does not have thorns.')
        ->and($result['description'])->toBe('Consuming this plant permanently increases Intelligence by +10.');
});

it('returns no flavor when first paragraph does not start with a quote', function () {
    $raw = 'Scrub Oak Leaves will cause a pregnancy to fail.';

    $result = ShopListingTextSplitter::split($raw);

    expect($result['flavor'])->toBeNull()
        ->and($result['description'])->toBe('Scrub Oak Leaves will cause a pregnancy to fail.');
});

it('returns no flavor for prefix before quote pattern', function () {
    $raw = "Not available in Welcome Packages.\n\n\"The blossoms resemble a monkey's face!\"\n\nMonkeyflower cures all ailments.";

    $result = ShopListingTextSplitter::split($raw);

    expect($result['flavor'])->toBeNull()
        ->and($result['description'])->toContain('Not available in Welcome Packages.')
        ->and($result['description'])->toContain('The blossoms resemble a monkey\'s face!')
        ->and($result['description'])->toContain('Monkeyflower cures all ailments.');
});

it('falls back to full text when opening quote is not closed', function () {
    $raw = "\"Unclosed flavor line\n\nMechanical text here.";

    $result = ShopListingTextSplitter::split($raw);

    expect($result['flavor'])->toBeNull()
        ->and($result['description'])->toContain('Unclosed flavor line')
        ->and($result['description'])->toContain('Mechanical text here.');
});
