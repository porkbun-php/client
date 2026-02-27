<?php

declare(strict_types=1);

use Porkbun\DTO\PricingCollection;
use Porkbun\DTO\PricingItem;

test('it creates collection from array', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
        'net' => ['registration' => '9.99', 'renewal' => '11.99'],
    ]);

    expect($pricingCollection->count())->toBe(2)
        ->and($pricingCollection->has('com'))->toBeTrue()
        ->and($pricingCollection->has('net'))->toBeTrue();
});

test('all returns associative array', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);

    $all = $pricingCollection->all();

    expect($all)->toBeArray()
        ->and(array_keys($all))->toBe(['com'])
        ->and($all['com'])->toBeInstanceOf(PricingItem::class);
});

test('items returns indexed array', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
        'net' => ['registration' => '9.99', 'renewal' => '11.99'],
    ]);

    $items = $pricingCollection->items();

    expect($items)->toBeArray()
        ->and(array_keys($items))->toBe([0, 1])
        ->and($items[0])->toBeInstanceOf(PricingItem::class);
});

test('find returns item or null', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);

    expect($pricingCollection->find('com'))->toBeInstanceOf(PricingItem::class)
        ->and($pricingCollection->find('xyz'))->toBeNull();
});

test('has checks for tld existence', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);

    expect($pricingCollection->has('com'))->toBeTrue()
        ->and($pricingCollection->has('xyz'))->toBeFalse();
});

test('tlds returns all tld keys', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
        'net' => ['registration' => '9.99', 'renewal' => '11.99'],
        'org' => ['registration' => '10.99', 'renewal' => '12.99'],
    ]);

    expect($pricingCollection->tlds())->toBe(['com', 'net', 'org']);
});

test('find returns typed item with accessible prices', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99', 'transfer' => '7.99'],
    ]);

    expect($pricingCollection->find('com')?->registrationPrice)->toBe(8.99)
        ->and($pricingCollection->find('com')?->renewalPrice)->toBe(10.99)
        ->and($pricingCollection->find('com')?->transferPrice)->toBe(7.99)
        ->and($pricingCollection->find('xyz')?->registrationPrice)->toBeNull();
});

test('cheapest returns sorted items', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'expensive' => ['registration' => '50.00', 'renewal' => '50.00'],
        'cheap' => ['registration' => '1.99', 'renewal' => '10.00'],
        'medium' => ['registration' => '10.00', 'renewal' => '10.00'],
    ]);

    $cheapest = $pricingCollection->cheapest(2);

    expect($cheapest)->toHaveCount(2)
        ->and($cheapest[0]->tld)->toBe('cheap')
        ->and($cheapest[1]->tld)->toBe('medium');
});

test('first returns first item or null', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);
    $empty = new PricingCollection();

    expect($pricingCollection->first())->toBeInstanceOf(PricingItem::class)
        ->and($empty->first())->toBeNull();
});

test('isEmpty and isNotEmpty work correctly', function (): void {
    $empty = new PricingCollection();
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($pricingCollection->isEmpty())->toBeFalse()
        ->and($pricingCollection->isNotEmpty())->toBeTrue();
});

test('collection is iterable', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
        'net' => ['registration' => '9.99', 'renewal' => '11.99'],
    ]);

    $tlds = [];
    foreach ($pricingCollection as $tld => $item) {
        $tlds[] = $tld;
    }

    expect($tlds)->toBe(['com', 'net']);
});

test('collection is countable', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
        'net' => ['registration' => '9.99', 'renewal' => '11.99'],
    ]);

    expect(count($pricingCollection))->toBe(2);
});

test('jsonSerialize and toArray return same data', function (): void {
    $pricingCollection = PricingCollection::fromArray([
        'com' => ['registration' => '8.99', 'renewal' => '10.99'],
    ]);

    expect($pricingCollection->jsonSerialize())->toBe($pricingCollection->toArray())
        ->and($pricingCollection->toArray()['com'])->toBeArray();
});
