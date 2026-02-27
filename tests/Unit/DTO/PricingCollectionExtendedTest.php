<?php

declare(strict_types=1);

use Porkbun\DTO\PricingCollection;

test('last returns last item', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '9.99', 'renewal' => '9.99'],
        'net' => ['registration' => '10.99', 'renewal' => '10.99'],
        'org' => ['registration' => '11.99', 'renewal' => '11.99'],
    ]);

    expect($pricingCollection->last()?->tld)->toBe('org');
});

test('last returns null for empty collection', function (): void {
    $collection = new PricingCollection();

    expect($collection->last())->toBeNull();
});
