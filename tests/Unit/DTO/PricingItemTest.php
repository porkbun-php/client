<?php

declare(strict_types=1);

use Porkbun\DTO\PricingItem;

test('it creates pricing item from array', function (): void {
    $pricingItem = PricingItem::fromArray(['tld' => 'com',
        'registration' => '8.99',
        'renewal' => '10.99',
        'transfer' => '7.99',
        'coupons' => [],
        'specialType' => null,
    ]);

    expect($pricingItem->tld)->toBe('com')
        ->and($pricingItem->registrationPrice)->toBe(8.99)
        ->and($pricingItem->renewalPrice)->toBe(10.99)
        ->and($pricingItem->transferPrice)->toBe(7.99)
        ->and($pricingItem->coupons)->toBe([])
        ->and($pricingItem->specialType)->toBeNull();
});

test('it creates handshake domain pricing', function (): void {
    $pricingItem = PricingItem::fromArray(['tld' => 'den',
        'registration' => '17.82',
        'renewal' => '17.82',
        'transfer' => '17.82',
        'coupons' => [],
        'specialType' => 'handshake',
    ]);

    expect($pricingItem->tld)->toBe('den')
        ->and($pricingItem->specialType)->toBe('handshake')
        ->and($pricingItem->isHandshake)->toBeTrue()
        ->and($pricingItem->hasCoupons)->toBeFalse();
});

test('it handles missing optional fields', function (): void {
    $pricingItem = PricingItem::fromArray(['tld' => 'net',
        'registration' => '9.99',
        'renewal' => '11.99',
    ]);

    expect($pricingItem->transferPrice)->toBeNull()
        ->and($pricingItem->coupons)->toBe([])
        ->and($pricingItem->specialType)->toBeNull()
        ->and($pricingItem->isHandshake)->toBeFalse();
});

test('it parses comma-formatted prices', function (): void {
    $pricingItem = PricingItem::fromArray(['tld' => 'pr',
        'registration' => '1,039.98',
        'renewal' => '1,039.98',
        'transfer' => '1,030.18',
        'coupons' => [],
    ]);

    expect($pricingItem->registrationPrice)->toBe(1039.98)
        ->and($pricingItem->renewalPrice)->toBe(1039.98)
        ->and($pricingItem->transferPrice)->toBe(1030.18);
});

test('it handles missing required fields with defaults', function (): void {
    $pricingItem = PricingItem::fromArray(['tld' => 'org']);

    expect($pricingItem->registrationPrice)->toBe(0.0)
        ->and($pricingItem->renewalPrice)->toBe(0.0);
});

test('toArray serializes correctly', function (): void {
    $item = new PricingItem(
        tld: 'com',
        registrationPrice: 8.99,
        renewalPrice: 10.99,
        transferPrice: 7.99,
    );

    $array = $item->toArray();

    expect($array)->toBe([
        'tld' => 'com',
        'registrationPrice' => 8.99,
        'renewalPrice' => 10.99,
        'transferPrice' => 7.99,
    ]);
});

test('toArray includes specialType when present', function (): void {
    $item = new PricingItem(
        tld: 'den',
        registrationPrice: 17.82,
        renewalPrice: 17.82,
        transferPrice: 17.82,
        specialType: 'handshake',
    );

    $array = $item->toArray();

    expect($array['specialType'])->toBe('handshake');
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
        'registrationPrice' => 8.99,
        'renewalPrice' => 10.99,
    ]);
});

test('fromArray accepts both API wire names and PHP property names', function (): void {
    $fromApi = PricingItem::fromArray([
        'tld' => 'com',
        'registration' => '8.99',
        'renewal' => '10.99',
        'transfer' => '7.99',
    ]);
    $fromPhp = PricingItem::fromArray([
        'tld' => 'com',
        'registrationPrice' => 8.99,
        'renewalPrice' => 10.99,
        'transferPrice' => 7.99,
    ]);

    expect($fromApi->registrationPrice)->toBe($fromPhp->registrationPrice)
        ->and($fromApi->renewalPrice)->toBe($fromPhp->renewalPrice)
        ->and($fromApi->transferPrice)->toBe($fromPhp->transferPrice);
});

test('round-trip through fromArray and toArray', function (): void {
    $original = new PricingItem(
        tld: 'com',
        registrationPrice: 8.99,
        renewalPrice: 10.99,
        transferPrice: 7.99,
        specialType: 'handshake',
    );

    $roundTripped = PricingItem::fromArray($original->toArray());

    expect($roundTripped->tld)->toBe($original->tld)
        ->and($roundTripped->registrationPrice)->toBe($original->registrationPrice)
        ->and($roundTripped->renewalPrice)->toBe($original->renewalPrice)
        ->and($roundTripped->transferPrice)->toBe($original->transferPrice)
        ->and($roundTripped->specialType)->toBe($original->specialType);
});
