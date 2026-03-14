<?php

declare(strict_types=1);

use Porkbun\DTO\Domain;
use Porkbun\DTO\DomainCollection;

test('fromArray creates collection from array data', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
        ['domain' => 'example.org', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection)->toHaveCount(2)
        ->and($domainCollection->items())->toHaveCount(2)
        ->and($domainCollection->items()[0])->toBeInstanceOf(Domain::class)
        ->and($domainCollection->items()[0]->domain)->toBe('example.com');
});

test('first returns first domain', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'first.com', 'status' => 'ACTIVE'],
        ['domain' => 'second.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->first()?->domain)->toBe('first.com');
});

test('first returns null for empty collection', function (): void {
    expect(new DomainCollection()->first())->toBeNull();
});

test('last returns last domain', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'first.com', 'status' => 'ACTIVE'],
        ['domain' => 'last.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->last()?->domain)->toBe('last.com');
});

test('last returns null for empty collection', function (): void {
    expect(new DomainCollection()->last())->toBeNull();
});

test('find finds domain by name', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
        ['domain' => 'example.org', 'status' => 'ACTIVE'],
    ]);

    $found = $domainCollection->find('example.org');

    expect($found)->toBeInstanceOf(Domain::class)
        ->and($found?->domain)->toBe('example.org');
});

test('find is case-insensitive', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'Example.COM', 'status' => 'ACTIVE'],
    ]);

    $found = $domainCollection->find('example.com');

    expect($found)->toBeInstanceOf(Domain::class)
        ->and($found?->domain)->toBe('Example.COM');
});

test('has is case-insensitive', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'Example.COM', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->has('example.com'))->toBeTrue()
        ->and($domainCollection->has('EXAMPLE.COM'))->toBeTrue();
});

test('find returns null for unknown domain', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->find('unknown.com'))->toBeNull();
});

test('has checks domain existence', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->has('example.com'))->toBeTrue()
        ->and($domainCollection->has('unknown.com'))->toBeFalse();
});

test('expiringSoon returns domains expiring within threshold', function (): void {
    $soon = new DateTimeImmutable()->modify('+10 days')->format('Y-m-d H:i:s');
    $later = new DateTimeImmutable()->modify('+90 days')->format('Y-m-d H:i:s');

    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'expiring.com', 'status' => 'ACTIVE', 'expireDate' => $soon],
        ['domain' => 'safe.com', 'status' => 'ACTIVE', 'expireDate' => $later],
    ]);

    $expiring = $domainCollection->expiringSoon(30);

    expect($expiring)->toHaveCount(1)
        ->and($expiring->first()?->domain)->toBe('expiring.com');
});

test('filter applies callback', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE', 'autoRenew' => '1'],
        ['domain' => 'example.org', 'status' => 'ACTIVE', 'autoRenew' => '0'],
        ['domain' => 'example.net', 'status' => 'ACTIVE', 'autoRenew' => '1'],
    ]);

    $filtered = $domainCollection->filter(fn (Domain $domain): bool => $domain->autoRenew === true);

    expect($filtered)->toBeInstanceOf(DomainCollection::class)
        ->and($filtered)->toHaveCount(2);
});

test('isEmpty and isNotEmpty', function (): void {
    $empty = new DomainCollection();
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
    ]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($domainCollection->isEmpty())->toBeFalse()
        ->and($domainCollection->isNotEmpty())->toBeTrue();
});

test('collection is countable', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'a.com', 'status' => 'ACTIVE'],
        ['domain' => 'b.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->count())->toBe(2)
        ->and(count($domainCollection))->toBe(2);
});

test('collection is iterable', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'a.com', 'status' => 'ACTIVE'],
        ['domain' => 'b.com', 'status' => 'ACTIVE'],
    ]);

    $items = [];
    foreach ($domainCollection as $domain) {
        $items[] = $domain;
    }

    expect($items)->toHaveCount(2)
        ->and($items[0])->toBeInstanceOf(Domain::class);
});

test('toArray and jsonSerialize return same data', function (): void {
    $domainCollection = DomainCollection::fromArray([
        ['domain' => 'example.com', 'status' => 'ACTIVE'],
    ]);

    expect($domainCollection->toArray())->toBe($domainCollection->jsonSerialize())
        ->and($domainCollection->toArray()[0])->toHaveKey('domain')
        ->and($domainCollection->toArray()[0]['domain'])->toBe('example.com');
});
