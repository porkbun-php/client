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

test('filter applies callback', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '9.99', 'renewal' => '9.99'],
        'net' => ['registration' => '10.99', 'renewal' => '10.99'],
        'org' => ['registration' => '11.99', 'renewal' => '11.99'],
    ]);

    $filtered = $pricingCollection->filter(fn ($item): bool => $item->registrationPrice < 11.0);

    expect($filtered)->toBeInstanceOf(PricingCollection::class)
        ->and($filtered)->toHaveCount(2)
        ->and($filtered->has('com'))->toBeTrue()
        ->and($filtered->has('net'))->toBeTrue()
        ->and($filtered->has('org'))->toBeFalse();
});
