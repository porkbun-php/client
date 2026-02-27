<?php

declare(strict_types=1);

use Porkbun\DTO\NameserverCollection;

test('constructor creates collection from array', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    expect($collection)->toHaveCount(2)
        ->and($collection->all())->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});

test('first returns first nameserver', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    expect($collection->first())->toBe('ns1.porkbun.com');
});

test('first returns null for empty collection', function (): void {
    expect(new NameserverCollection()->first())->toBeNull();
});

test('last returns last nameserver', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    expect($collection->last())->toBe('ns2.porkbun.com');
});

test('last returns null for empty collection', function (): void {
    expect(new NameserverCollection()->last())->toBeNull();
});

test('has checks for nameserver existence', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    expect($collection->has('ns1.porkbun.com'))->toBeTrue()
        ->and($collection->has('ns3.porkbun.com'))->toBeFalse();
});

test('isEmpty and isNotEmpty', function (): void {
    $empty = new NameserverCollection();
    $nonEmpty = new NameserverCollection(['ns1.porkbun.com']);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($nonEmpty->isEmpty())->toBeFalse()
        ->and($nonEmpty->isNotEmpty())->toBeTrue();
});

test('collection is countable', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com', 'ns3.porkbun.com']);

    expect($collection->count())->toBe(3)
        ->and(count($collection))->toBe(3);
});

test('collection is iterable', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    $items = [];
    foreach ($collection as $ns) {
        $items[] = $ns;
    }

    expect($items)->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});

test('toArray and jsonSerialize return same data', function (): void {
    $collection = new NameserverCollection(['ns1.porkbun.com', 'ns2.porkbun.com']);

    expect($collection->toArray())->toBe($collection->jsonSerialize())
        ->and($collection->toArray())->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});

test('constructor reindexes array values', function (): void {
    $collection = new NameserverCollection([2 => 'ns1.porkbun.com', 5 => 'ns2.porkbun.com']);

    expect($collection->all())->toBe(['ns1.porkbun.com', 'ns2.porkbun.com']);
});
