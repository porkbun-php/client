<?php

declare(strict_types=1);

use Porkbun\DTO\DnssecRecord;
use Porkbun\DTO\DnssecRecordCollection;

test('fromArray creates collection from array data', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '12345', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'abc123'],
        ['keyTag' => '67890', 'algorithm' => '8', 'digestType' => '1', 'digest' => 'def456'],
    ]);

    expect($dnssecRecordCollection)->toHaveCount(2)
        ->and($dnssecRecordCollection->all())->toHaveCount(2)
        ->and($dnssecRecordCollection->all()[0])->toBeInstanceOf(DnssecRecord::class)
        ->and($dnssecRecordCollection->all()[0]->keyTag)->toBe(12345)
        ->and($dnssecRecordCollection->all()[1]->keyTag)->toBe(67890);
});

test('first returns first record', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'bbb'],
    ]);

    $first = $dnssecRecordCollection->first();

    expect($first)->toBeInstanceOf(DnssecRecord::class)
        ->and($first?->keyTag)->toBe(111);
});

test('first returns null for empty collection', function (): void {
    $collection = new DnssecRecordCollection();

    expect($collection->first())->toBeNull();
});

test('last returns last record', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'bbb'],
    ]);

    $last = $dnssecRecordCollection->last();

    expect($last)->toBeInstanceOf(DnssecRecord::class)
        ->and($last?->keyTag)->toBe(222);
});

test('last returns null for empty collection', function (): void {
    $collection = new DnssecRecordCollection();

    expect($collection->last())->toBeNull();
});

test('find returns matching record by keyTag', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '8', 'digestType' => '1', 'digest' => 'bbb'],
    ]);

    $found = $dnssecRecordCollection->find(222);

    expect($found)->toBeInstanceOf(DnssecRecord::class)
        ->and($found?->keyTag)->toBe(222)
        ->and($found?->algorithm)->toBe(8);
});

test('find returns null when not found', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
    ]);

    expect($dnssecRecordCollection->find(999))->toBeNull();
});

test('filter applies callback', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '8', 'digestType' => '1', 'digest' => 'bbb'],
        ['keyTag' => '333', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'ccc'],
    ]);

    $filtered = $dnssecRecordCollection->filter(fn (DnssecRecord $dnssecRecord): bool => $dnssecRecord->algorithm === 13);

    expect($filtered)->toHaveCount(2)
        ->and($filtered[0]->keyTag)->toBe(111)
        ->and($filtered[1]->keyTag)->toBe(333);
});

test('isEmpty and isNotEmpty', function (): void {
    $empty = new DnssecRecordCollection();
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
    ]);

    expect($empty->isEmpty())->toBeTrue()
        ->and($empty->isNotEmpty())->toBeFalse()
        ->and($dnssecRecordCollection->isEmpty())->toBeFalse()
        ->and($dnssecRecordCollection->isNotEmpty())->toBeTrue();
});

test('collection is countable', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '8', 'digestType' => '1', 'digest' => 'bbb'],
    ]);

    expect($dnssecRecordCollection->count())->toBe(2)
        ->and(count($dnssecRecordCollection))->toBe(2);
});

test('collection is iterable', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
        ['keyTag' => '222', 'algorithm' => '8', 'digestType' => '1', 'digest' => 'bbb'],
    ]);

    $items = [];
    foreach ($dnssecRecordCollection as $record) {
        $items[] = $record;
    }

    expect($items)->toHaveCount(2)
        ->and($items[0])->toBeInstanceOf(DnssecRecord::class);
});

test('toArray and jsonSerialize return same data', function (): void {
    $dnssecRecordCollection = DnssecRecordCollection::fromArray([
        ['keyTag' => '111', 'algorithm' => '13', 'digestType' => '2', 'digest' => 'aaa'],
    ]);

    expect($dnssecRecordCollection->toArray())->toBe($dnssecRecordCollection->jsonSerialize())
        ->and($dnssecRecordCollection->toArray()[0])->toHaveKey('keyTag')
        ->and($dnssecRecordCollection->toArray()[0])->toHaveKey('alg');
});
