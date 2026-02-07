<?php

declare(strict_types=1);

use Porkbun\DTO\PricingItem;

test('it creates pricing item from array', function (): void {
    $pricingItem = PricingItem::fromArray('com', [
        'registration' => '8.99',
        'renewal' => '10.99',
        'transfer' => '7.99',
        'years' => '1',
    ]);

    expect($pricingItem->tld)->toBe('com')
        ->and($pricingItem->registrationPrice)->toBe(8.99)
        ->and($pricingItem->renewalPrice)->toBe(10.99)
        ->and($pricingItem->transferPrice)->toBe(7.99)
        ->and($pricingItem->years)->toBe(1);
});

test('it handles missing optional fields', function (): void {
    $pricingItem = PricingItem::fromArray('net', [
        'registration' => '9.99',
        'renewal' => '11.99',
    ]);

    expect($pricingItem->transferPrice)->toBeNull()
        ->and($pricingItem->years)->toBeNull();
});

test('it handles missing required fields with defaults', function (): void {
    $pricingItem = PricingItem::fromArray('org', []);

    expect($pricingItem->registrationPrice)->toBe(0.0)
        ->and($pricingItem->renewalPrice)->toBe(0.0);
});

test('toArray serializes correctly', function (): void {
    $item = new PricingItem(
        tld: 'com',
        registrationPrice: 8.99,
        renewalPrice: 10.99,
        transferPrice: 7.99,
        years: 2,
    );

    $array = $item->toArray();

    expect($array)->toBe([
        'tld' => 'com',
        'registration' => '8.99',
        'renewal' => '10.99',
        'transfer' => '7.99',
        'years' => 2,
    ]);
});

test('toArray omits null optional fields', function (): void {
    $item = new PricingItem(
        tld: 'com',
        registrationPrice: 8.99,
        renewalPrice: 10.99,
    );

    $array = $item->toArray();

    expect($array)->toBe([
        'tld' => 'com',
        'registration' => '8.99',
        'renewal' => '10.99',
    ]);
});
