<?php

declare(strict_types=1);

use Porkbun\DTO\GlueRecord;
use Porkbun\DTO\GlueRecordCollection;

test('fromArray creates collection from array data', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2']],
    ]);

    expect($glueRecordCollection)->toHaveCount(2)
        ->and($glueRecordCollection->all())->toHaveCount(2)
        ->and($glueRecordCollection->all()[0])->toBeInstanceOf(GlueRecord::class)
        ->and($glueRecordCollection->all()[0]->host)->toBe('ns1');
});

test('first returns first record', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2']],
    ]);

    expect($glueRecordCollection->first()?->host)->toBe('ns1');
});

test('first returns null for empty collection', function (): void {
    expect(new GlueRecordCollection()->first())->toBeNull();
});

test('last returns last record', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2']],
    ]);

    expect($glueRecordCollection->last()?->host)->toBe('ns2');
});

test('last returns null for empty collection', function (): void {
    expect(new GlueRecordCollection()->last())->toBeNull();
});

test('find returns matching record by host', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2']],
    ]);

    $found = $glueRecordCollection->find('ns2');

    expect($found)->toBeInstanceOf(GlueRecord::class)
        ->and($found?->host)->toBe('ns2');
});

test('find returns null when not found', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
    ]);

    expect($glueRecordCollection->find('ns99'))->toBeNull();
});

test('filter applies callback', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2', '192.0.2.3']],
        ['host' => 'ns3', 'ips' => ['192.0.2.4', '192.0.2.5']],
    ]);

    $filtered = $glueRecordCollection->filter(fn (GlueRecord $glueRecord): bool => count($glueRecord->ips) > 1);

    expect($filtered)->toHaveCount(2)
        ->and($filtered[0]->host)->toBe('ns2');
});

test('isEmpty and isNotEmpty', function (): void {
    $empty = new GlueRecordCollection();
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
    ]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($glueRecordCollection->isEmpty())->toBeFalse()
        ->and($glueRecordCollection->isNotEmpty())->toBeTrue();
});

test('collection is countable', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
        ['host' => 'ns2', 'ips' => ['192.0.2.2']],
    ]);

    expect($glueRecordCollection->count())->toBe(2)
        ->and(count($glueRecordCollection))->toBe(2);
});

test('collection is iterable', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
    ]);

    $items = [];
    foreach ($glueRecordCollection as $record) {
        $items[] = $record;
    }

    expect($items)->toHaveCount(1)
        ->and($items[0])->toBeInstanceOf(GlueRecord::class);
});

test('toArray and jsonSerialize return same data', function (): void {
    $glueRecordCollection = GlueRecordCollection::fromArray([
        ['host' => 'ns1', 'ips' => ['192.0.2.1']],
    ]);

    expect($glueRecordCollection->toArray())->toBe($glueRecordCollection->jsonSerialize())
        ->and($glueRecordCollection->toArray()[0])->toHaveKey('host');
});
